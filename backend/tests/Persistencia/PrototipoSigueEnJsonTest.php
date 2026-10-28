<?php

declare(strict_types=1);

namespace Tests\Persistencia;

use App\Auth\MockUserAuthenticator;
use App\Contracts\UserAuthenticator;
use App\Models\User;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\DemoCredentials;

final class PrototipoSigueEnJsonTest extends PersistenciaTestCase
{
    #[TestDox('El login del prototipo sigue usando MockUserAuthenticator, no Eloquent')]
    public function test_el_autenticador_sigue_siendo_el_simulado(): void
    {
        $this->assertInstanceOf(
            MockUserAuthenticator::class,
            $this->app->make(UserAuthenticator::class)
        );
        $this->assertSame(0, User::query()->count());
    }

    #[TestDox('Un login de demostración no crea filas en users')]
    public function test_login_demo_no_escribe_en_la_base(): void
    {
        $this->postJson('/api/auth/login', DemoCredentials::for('student'))->assertOk();

        $this->assertSame(0, User::query()->count());
    }
}
