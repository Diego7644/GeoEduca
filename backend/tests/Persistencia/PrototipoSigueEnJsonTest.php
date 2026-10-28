<?php

declare(strict_types=1);

namespace Tests\Persistencia;

use App\Auth\EloquentUserAuthenticator;
use App\Contracts\UserAuthenticator;
use App\Models\User;
use App\Services\Asistencia\AsistenciaStore;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\DemoCredentials;

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
}
