<?php

namespace App\Auth;

use App\Contracts\UserAuthenticator;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;

/**
 * Autenticación real contra `users`. La cuenta se identifica por DNI + rol
 * (un mismo DNI puede ser estudiante y tutor). No hay fallback a DemoAccounts.
 */
class EloquentUserAuthenticator implements UserAuthenticator
{
    public function authenticate(array $credentials): array
    {
        $dni = preg_replace('/\D/', '', (string) ($credentials['identifier'] ?? '')) ?? '';
        $password = (string) ($credentials['password'] ?? '');
        $role = (string) ($credentials['role'] ?? '');

        $user = $this->findAccount($dni, $role);

        if ($user === null || ! Hash::check($password, $user->getAuthPassword())) {
            $this->reject('DNI o contraseña incorrectos para este perfil.', [
                'identifier' => ['Revise el DNI.'],
                'password' => ['Revise la contraseña.'],
            ], 401);
        }

        return $this->issue($user);
    }

    public static function payload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'roleLabel' => $user->role_label,
            'identifier' => $user->dni,
            'role' => $user->role,
            'staffKind' => $user->staffProfile?->kind,
        ];
    }

    private function findAccount(string $dni, string $role): ?User
    {
        if ($dni === '' || $role === '') {
            return null;
        }

        return User::query()->where('dni', $dni)->where('role', $role)->first();
    }

    private function issue(User $user): array
    {
        $minutes = (int) config('sanctum.expiration', 120);
        $token = $user->createToken(
            'geoeduca',
            ['*'],
            now()->addMinutes(max($minutes, 1))
        )->plainTextToken;

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => self::payload($user),
        ];
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function reject(string $message, array $errors, int $status): never
    {
        $cuerpo = ['message' => $message];
        if ($errors !== []) {
            $cuerpo['errors'] = $errors;
        }

        throw new HttpResponseException(response()->json($cuerpo, $status));
    }
}
