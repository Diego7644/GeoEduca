<?php

declare(strict_types=1);

namespace Tests\Caracterizacion\Feature;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\DemoCredentials;
use Tests\TestCase;

/**
 * Caracterización: rechazo de credenciales incorrectas.
 * Matriz manual: N-01, N-02, N-03.
 */
final class LoginRechazoTest extends TestCase
{
    #[Group('N-01')]
    #[TestDox('Contraseña incorrecta: 401 con mensaje genérico y sin token')]
    public function test_contrasena_incorrecta_se_rechaza(): void
    {
        $credenciales = DemoCredentials::for('student');

        $this->postJson('/api/auth/login', array_merge($credenciales, ['password' => 'Clave-incorrecta-01']))
            ->assertUnauthorized()
            ->assertJsonPath('message', 'DNI o contraseña incorrectos para este perfil.')
            ->assertJsonStructure(['message', 'errors' => ['identifier', 'password']])
            ->assertJsonMissingPath('token');
    }

    #[Group('N-02')]
    #[TestDox('La contraseña de otro perfil no abre el perfil de estudiante')]
    public function test_credencial_de_otro_perfil_se_rechaza(): void
    {
        $estudiante = DemoCredentials::for('student');
        $padre = DemoCredentials::for('parent');

        $this->postJson('/api/auth/login', array_merge($estudiante, ['password' => $padre['password']]))
            ->assertUnauthorized()
            ->assertJsonMissingPath('token');
    }

    #[Group('N-02')]
    #[TestDox('El DNI del personal no abre el perfil de estudiante, aunque la contraseña sea la del personal')]
    public function test_dni_y_contrasena_de_personal_no_abren_perfil_de_estudiante(): void
    {
        $personal = DemoCredentials::for('staff');

        $this->postJson('/api/auth/login', array_merge($personal, ['role' => 'student']))
            ->assertUnauthorized()
            ->assertJsonMissingPath('token');
    }

    #[Group('N-03')]
    #[TestDox('No se puede enumerar usuarios: DNI inexistente y contraseña errónea dan la misma respuesta')]
    public function test_la_respuesta_no_revela_si_el_dni_existe(): void
    {
        $credenciales = DemoCredentials::for('student');

        $contrasenaErronea = $this->postJson('/api/auth/login', array_merge($credenciales, ['password' => 'Clave-incorrecta-01']));
        $dniInexistente = $this->postJson('/api/auth/login', array_merge($credenciales, ['identifier' => '99999999']));

        $dniInexistente->assertUnauthorized();
        $this->assertSame($contrasenaErronea->getContent(), $dniInexistente->getContent());
    }
}
