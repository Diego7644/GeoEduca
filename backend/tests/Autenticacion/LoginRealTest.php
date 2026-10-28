<?php

declare(strict_types=1);

namespace Tests\Autenticacion;

use App\Auth\EloquentUserAuthenticator;
use App\Contracts\UserAuthenticator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\DemoCredentials;
use Tests\TestCase;

final class LoginRealTest extends TestCase
{
    #[TestDox('El contenedor usa EloquentUserAuthenticator, no el mock')]
    public function test_el_binding_es_eloquent(): void
    {
        $this->assertInstanceOf(
            EloquentUserAuthenticator::class,
            $this->app->make(UserAuthenticator::class)
        );
    }

    #[TestDox('Login correcto emite un token Sanctum y no devuelve la contraseña')]
    public function test_login_correcto_emite_token_sanctum(): void
    {
        $credenciales = DemoCredentials::for('student');

        $respuesta = $this->postJson('/api/auth/login', $credenciales)->assertOk();

        $token = $respuesta->json('token');
        $this->assertIsString($token);
        $this->assertMatchesRegularExpression('/^\d+\|.+/', $token);
        $this->assertSame('Bearer', $respuesta->json('token_type'));
        $this->assertSame('student', $respuesta->json('user.role'));
        $this->assertSame($credenciales['identifier'], $respuesta->json('user.identifier'));
        $this->assertArrayNotHasKey('password', $respuesta->json('user'));
        $this->assertStringNotContainsString($credenciales['password'], $respuesta->getContent());

        $user = User::query()->where('dni', $credenciales['identifier'])->where('role', 'student')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check($credenciales['password'], $user->getAuthPassword()));
    }

    #[TestDox('Contraseña incorrecta responde 401 y no emite token')]
    public function test_contrasena_incorrecta_responde_401(): void
    {
        $this->postJson('/api/auth/login', array_merge(DemoCredentials::for('student'), ['password' => 'Clave-incorrecta-01']))
            ->assertUnauthorized()
            ->assertJsonMissingPath('token');
    }

    #[TestDox('DNI inexistente responde 401 genérico')]
    public function test_dni_inexistente_responde_401(): void
    {
        $this->postJson('/api/auth/login', [
            'identifier' => '19999999',
            'password' => 'Clave-valida-01',
            'role' => 'student',
        ])->assertUnauthorized()->assertJsonMissingPath('token');
    }

    #[TestDox('El mismo DNI con el rol incorrecto no entra a la otra cuenta')]
    public function test_rol_incorrecto_no_abre_la_otra_cuenta(): void
    {
        $estudiante = DemoCredentials::for('student');

        $this->postJson('/api/auth/login', array_merge($estudiante, ['role' => 'parent']))
            ->assertUnauthorized();
    }

    #[TestDox('Un login no crea filas nuevas en users')]
    public function test_login_no_inserta_usuarios(): void
    {
        $antes = User::query()->count();
        $this->postJson('/api/auth/login', DemoCredentials::for('student'))->assertOk();
        $this->assertSame($antes, User::query()->count());
    }
}
