<?php

declare(strict_types=1);

namespace Tests\Persistencia;

use App\Auth\EloquentUserAuthenticator;
use App\Auth\RecoveryCodeGenerator;
use App\Contracts\UserAuthenticator;
use App\Models\PasswordResetCode;
use App\Models\User;
use App\Services\Asistencia\AsistenciaStore;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\DemoCredentials;
use Tests\Support\FakeRecoveryCodeGenerator;

final class PrototipoSigueEnJsonTest extends PersistenciaTestCase
{
    #[TestDox('El login usa EloquentUserAuthenticator contra la tabla users')]
    public function test_el_autenticador_es_eloquent(): void
    {
        $this->assertInstanceOf(
            EloquentUserAuthenticator::class,
            $this->app->make(UserAuthenticator::class)
        );
    }

    #[TestDox('Un login válido no crea filas en users y no guarda la sesión en el JSON')]
    public function test_login_no_escribe_sesion_en_json(): void
    {
        $antes = User::query()->count();
        $token = $this->postJson('/api/auth/login', DemoCredentials::for('student'))->assertOk()->json('token');

        $this->assertSame($antes, User::query()->count());
        $this->assertNull(app(AsistenciaStore::class)->userForToken((string) $token));
    }

    #[TestDox('La recuperación escribe password_reset_codes y no el JSON')]
    public function test_recuperacion_no_usa_json(): void
    {
        $this->app->instance(RecoveryCodeGenerator::class, new FakeRecoveryCodeGenerator('123456'));
        $credenciales = DemoCredentials::for('student');

        $this->postJson('/api/auth/recuperar', [
            'identifier' => $credenciales['identifier'],
            'role' => 'student',
        ])->assertOk()->assertJsonMissingPath('dev_code');

        $this->assertSame(1, PasswordResetCode::query()->count());
        $ruta = storage_path('app/geoeduca-state.json');
        $json = is_file($ruta) ? (json_decode((string) file_get_contents($ruta), true) ?? []) : [];
        $this->assertSame([], $json['recovery'] ?? []);
        $this->assertSame([], $json['passwords'] ?? []);
    }
}
