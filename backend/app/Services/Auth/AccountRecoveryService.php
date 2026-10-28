<?php

namespace App\Services\Auth;

use App\Auth\RecoveryCodeGenerator;
use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AccountRecoveryService
{
    public const REQUEST_MESSAGE = 'Si existe una cuenta con esos datos, se registró una solicitud de recuperación. GEOEDUCA aún no envía el código por correo ni SMS.';

    public const CONFIRM_FAILURE = 'No se pudo confirmar la recuperación.';

    public const CONFIRM_SUCCESS = 'Contraseña actualizada. Ya puede iniciar sesión.';

    public function __construct(
        private RecoveryCodeGenerator $codes,
        private SecurityEventLogger $events
    ) {
    }

    /**
     * @param  array{identifier: string, role: string}  $payload
     * @return array{message: string, dev_code?: string}
     */
    public function request(array $payload): array
    {
        $dni = $payload['identifier'];
        $role = $payload['role'];
        $user = $this->findAccount($dni, $role);

        $response = ['message' => self::REQUEST_MESSAGE];

        if ($user === null) {
            Hash::make(bin2hex(random_bytes(8)));
            $this->events->record(null, 'recovery_requested', 'unknown', [
                'role' => $role,
                'dni_suffix' => substr($dni, -2),
            ]);

            return $response;
        }

        $plain = $this->codes->generate();

        DB::transaction(function () use ($user, $plain) {
            PasswordResetCode::query()
                ->where('dni', $user->dni)
                ->where('role', $user->role)
                ->whereNull('used_at')
                ->lockForUpdate()
                ->get()
                ->each(function (PasswordResetCode $row): void {
                    $row->forceFill(['used_at' => now()])->save();
                });

            PasswordResetCode::query()->create([
                'dni' => $user->dni,
                'role' => $user->role,
                'code_hash' => Hash::make($plain),
                'expires_at' => now()->addMinutes($this->ttlMinutes()),
                'used_at' => null,
                'attempts' => 0,
            ]);
        });

        $this->events->record($user->id, 'recovery_requested', 'issued', [
            'role' => $role,
            'dni_suffix' => substr($dni, -2),
        ]);

        if ($this->mayRevealCode()) {
            Log::debug('geoeduca.recovery.dev_code_issued', ['user_id' => $user->id]);
            $response['dev_code'] = $plain;
        }

        return $response;
    }

    /**
     * @param  array{identifier: string, role: string, code: string, password: string}  $payload
     * @return array{message: string}
     */
    public function confirm(array $payload): array
    {
        $dni = $payload['identifier'];
        $role = $payload['role'];
        $code = $payload['code'];
        $password = $payload['password'];

        $outcome = DB::transaction(function () use ($dni, $role, $code, $password) {
            $row = PasswordResetCode::query()
                ->where('dni', $dni)
                ->where('role', $role)
                ->whereNull('used_at')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $user = $this->findAccount($dni, $role);

            if ($row === null || $user === null) {
                Hash::check($code, Hash::make('geoeduca-recovery-miss'));

                return 'missing';
            }

            if ($row->expires_at === null || $row->expires_at->isPast()) {
                $row->forceFill(['used_at' => now()])->save();

                return 'expired';
            }

            if ((int) $row->attempts >= $this->maxAttempts()) {
                $row->forceFill(['used_at' => now()])->save();

                return 'locked';
            }

            if (! Hash::check($code, $row->code_hash)) {
                $attempts = (int) $row->attempts + 1;
                $row->forceFill([
                    'attempts' => $attempts,
                    'used_at' => $attempts >= $this->maxAttempts() ? now() : $row->used_at,
                ])->save();

                return 'mismatch';
            }

            $row->forceFill([
                'used_at' => now(),
                'attempts' => (int) $row->attempts + 1,
            ])->save();

            $user->forceFill(['password' => $password])->save();
            $user->tokens()->delete();
            Auth::forgetGuards();

            return 'ok';
        });

        if ($outcome !== 'ok') {
            $this->events->record(
                $this->findAccount($dni, $role)?->id,
                'recovery_failed',
                $outcome,
                ['role' => $role, 'dni_suffix' => substr($dni, -2)]
            );

            throw new \RuntimeException(self::CONFIRM_FAILURE);
        }

        $this->events->record(
            $this->findAccount($dni, $role)?->id,
            'recovery_confirmed',
            'ok',
            ['role' => $role, 'dni_suffix' => substr($dni, -2)]
        );

        return ['message' => self::CONFIRM_SUCCESS];
    }

    public function mayRevealCode(): bool
    {
        if (app()->environment('production')) {
            return false;
        }

        return (bool) config('geoeduca.recovery.dev_reveal', false);
    }

    private function findAccount(string $dni, string $role): ?User
    {
        if ($dni === '' || $role === '') {
            return null;
        }

        return User::query()->where('dni', $dni)->where('role', $role)->first();
    }

    private function ttlMinutes(): int
    {
        return max(1, (int) config('geoeduca.recovery.ttl_minutes', 15));
    }

    private function maxAttempts(): int
    {
        return max(1, (int) config('geoeduca.recovery.max_attempts', 5));
    }
}
