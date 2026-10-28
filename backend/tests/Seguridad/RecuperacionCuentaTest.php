<?php

declare(strict_types=1);

namespace Tests\Seguridad;

use App\Auth\RecoveryCodeGenerator;
use App\Models\PasswordResetCode;
use App\Models\User;
use App\Services\Asistencia\AsistenciaStore;
use App\Services\Auth\AccountRecoveryService;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\DemoCredentials;
use Tests\Support\FakeRecoveryCodeGenerator;
use Tests\Support\IniciaSesion;
use Tests\TestCase;

#[Group('F1.3')]
final class RecuperacionCuentaTest extends TestCase
{
    use IniciaSesion;

    private const CODE = '123456';

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(RecoveryCodeGenerator::class, new FakeRecoveryCodeGenerator(self::CODE));
    }

    #[TestDox('Una cuenta existente y una inexistente reciben el mismo mensaje, sin código')]
    public function test_cuenta_inexistente_no_se_revela(): void
    {
        $existente = $this->postJson('/api/auth/recuperar', [
            'identifier' => DemoCredentials::for('student')['identifier'],
            'role' => 'student',
        ])->assertOk();

        $inexistente = $this->postJson('/api/auth/recuperar', [
            'identifier' => '19999999',
            'role' => 'student',
        ])->assertOk();

        $this->assertSame($existente->json('message'), $inexistente->json('message'));
        $this->assertSame(AccountRecoveryService::REQUEST_MESSAGE, $existente->json('message'));
        $this->assertArrayNotHasKey('dev_code', $existente->json());
        $this->assertArrayNotHasKey('demo_code', $existente->json());
        $this->assertArrayNotHasKey('code', $existente->json());
        $this->assertSame(1, PasswordResetCode::query()->count());
        $this->assertNull(app(AsistenciaStore::class)->userForToken('no-aplica'));
    }

    #[TestDox('El código correcto actualiza el hash, es de un solo uso y revoca tokens')]
    public function test_codigo_correcto_actualiza_contrasena_y_no_se_reutiliza(): void
    {
        $credenciales = DemoCredentials::for('student');
        $token = $this->tokenDe('student');

        $this->postJson('/api/auth/recuperar', [
            'identifier' => $credenciales['identifier'],
            'role' => 'student',
        ])->assertOk();

        $fila = PasswordResetCode::query()->first();
        $this->assertNotNull($fila);
        $this->assertNotSame(self::CODE, $fila->code_hash);
        $this->assertTrue(Hash::check(self::CODE, $fila->code_hash));

        $nueva = 'Clave-nueva-99';
        $this->postJson('/api/auth/recuperar/confirmar', [
            'identifier' => $credenciales['identifier'],
            'role' => 'student',
            'code' => self::CODE,
            'password' => $nueva,
        ])->assertOk()->assertJsonPath('message', AccountRecoveryService::CONFIRM_SUCCESS);

        $user = User::query()->where('dni', $credenciales['identifier'])->where('role', 'student')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check($nueva, $user->getAuthPassword()));
        $this->assertFalse(Hash::check($credenciales['password'], $user->getAuthPassword()));
        $this->assertNotNull($fila->fresh()->used_at);
        $this->assertSame(0, PersonalAccessToken::query()->count());

        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();

        $this->postJson('/api/auth/recuperar/confirmar', [
            'identifier' => $credenciales['identifier'],
            'role' => 'student',
            'code' => self::CODE,
            'password' => 'Otra-clave-00',
        ])->assertStatus(422)->assertJsonPath('message', AccountRecoveryService::CONFIRM_FAILURE);
    }

    #[TestDox('Un código incorrecto responde el error genérico y no cambia la contraseña')]
    public function test_codigo_incorrecto_no_cambia_la_contrasena(): void
    {
        $credenciales = DemoCredentials::for('student');
        $this->solicitar($credenciales);

        $this->postJson('/api/auth/recuperar/confirmar', [
            'identifier' => $credenciales['identifier'],
            'role' => 'student',
            'code' => '000000',
            'password' => 'Clave-nueva-99',
        ])->assertStatus(422)->assertJsonPath('message', AccountRecoveryService::CONFIRM_FAILURE);

        $user = User::query()->where('dni', $credenciales['identifier'])->where('role', 'student')->first();
        $this->assertTrue(Hash::check($credenciales['password'], $user->getAuthPassword()));
    }

    #[TestDox('Un código expirado responde el error genérico')]
    public function test_codigo_expirado_no_se_acepta(): void
    {
        $credenciales = DemoCredentials::for('student');
        $this->solicitar($credenciales);
        PasswordResetCode::query()->update(['expires_at' => now()->subMinute()]);

        $this->postJson('/api/auth/recuperar/confirmar', [
            'identifier' => $credenciales['identifier'],
            'role' => 'student',
            'code' => self::CODE,
            'password' => 'Clave-nueva-99',
        ])->assertStatus(422)->assertJsonPath('message', AccountRecoveryService::CONFIRM_FAILURE);
    }

    #[TestDox('Tras el máximo de intentos el código queda inutilizable')]
    public function test_limite_de_intentos_invalida_el_codigo(): void
    {
        $credenciales = DemoCredentials::for('student');
        $this->solicitar($credenciales);

        for ($i = 1; $i <= 5; $i++) {
            $this->postJson('/api/auth/recuperar/confirmar', [
                'identifier' => $credenciales['identifier'],
                'role' => 'student',
                'code' => '111111',
                'password' => 'Clave-nueva-99',
            ])->assertStatus(422)->assertJsonPath('message', AccountRecoveryService::CONFIRM_FAILURE);
        }

        $this->postJson('/api/auth/recuperar/confirmar', [
            'identifier' => $credenciales['identifier'],
            'role' => 'student',
            'code' => self::CODE,
            'password' => 'Clave-nueva-99',
        ])->assertStatus(422)->assertJsonPath('message', AccountRecoveryService::CONFIRM_FAILURE);

        $user = User::query()->where('dni', $credenciales['identifier'])->where('role', 'student')->first();
        $this->assertTrue(Hash::check($credenciales['password'], $user->getAuthPassword()));
    }

    #[TestDox('La validación estricta rechaza entradas inválidas con 422 y sin secretos')]
    public function test_validaciones_de_entrada(): void
    {
        $this->postJson('/api/auth/recuperar', ['identifier' => ['71405255'], 'role' => 'student'])
            ->assertStatus(422)
            ->assertJsonMissingPath('dev_code');

        $this->postJson('/api/auth/recuperar/confirmar', [
            'identifier' => '71405255',
            'role' => 'admin',
            'code' => '12',
            'password' => 'corta',
        ])->assertStatus(422)->assertJsonMissingPath('dev_code');
    }

    #[TestDox('GEO_RECOVERY_DEV_REVEAL no opera en producción')]
    public function test_no_revela_codigo_en_produccion(): void
    {
        config(['geoeduca.recovery.dev_reveal' => true]);
        $this->app['env'] = 'production';

        $this->postJson('/api/auth/recuperar', [
            'identifier' => DemoCredentials::for('student')['identifier'],
            'role' => 'student',
        ])->assertOk()->assertJsonMissingPath('dev_code')->assertJsonMissingPath('demo_code');
    }

    #[TestDox('El límite de solicitudes de recuperación responde 429')]
    public function test_limite_de_solicitudes_de_recuperacion(): void
    {
        $payload = [
            'identifier' => DemoCredentials::for('student')['identifier'],
            'role' => 'student',
        ];
        $estados = [];
        for ($i = 1; $i <= 12; $i++) {
            $estados[] = $this->postJson('/api/auth/recuperar', $payload)->getStatusCode();
        }

        $this->assertContains(429, $estados, 'Estados: '.implode(', ', $estados));
    }

    /**
     * @param  array{identifier: string, role?: string}  $credenciales
     */
    private function solicitar(array $credenciales): void
    {
        $this->postJson('/api/auth/recuperar', [
            'identifier' => $credenciales['identifier'],
            'role' => 'student',
        ])->assertOk();
    }
}
