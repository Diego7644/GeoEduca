<?php

declare(strict_types=1);

namespace Tests\Caracterizacion\Unit;

use App\Contracts\UserAuthenticator;
use App\Services\Auth\AuthService;
use App\Auth\MockUserAuthenticator;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\DemoCredentials;
use Tests\TestCase;

/**
 * Caracterización: AuthService delega en el contrato UserAuthenticator
 * (la costura que permitirá reemplazar el mock por la base de datos).
 */
#[Group('F-02')]
final class AuthServiceTest extends TestCase
{
    #[TestDox('AuthService delega en UserAuthenticator exactamente una vez y devuelve su resultado sin modificarlo')]
    public function test_delega_en_el_autenticador(): void
    {
        $credenciales = DemoCredentials::for('student');
        $esperado = ['token' => 'mock-doble', 'token_type' => 'Bearer', 'user' => ['role' => 'student']];

        $autenticador = Mockery::mock(UserAuthenticator::class);
        $autenticador->shouldReceive('authenticate')->once()->with($credenciales)->andReturn($esperado);

        $this->assertSame($esperado, (new AuthService($autenticador))->attempt($credenciales));
    }

    #[TestDox('El contenedor resuelve UserAuthenticator como MockUserAuthenticator (binding actual)')]
    public function test_el_binding_actual_es_el_mock(): void
    {
        $this->assertInstanceOf(MockUserAuthenticator::class, $this->app->make(UserAuthenticator::class));
    }
}
