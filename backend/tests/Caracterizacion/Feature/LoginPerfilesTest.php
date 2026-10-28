<?php

declare(strict_types=1);

namespace Tests\Caracterizacion\Feature;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\DemoCredentials;
use Tests\TestCase;

/**
 * Caracterización: inicio de sesión correcto con los tres perfiles de demostración.
 * Matriz manual: F-02, F-03, F-04 (y I-02 en lo que respecta al contrato de la API).
 */
final class LoginPerfilesTest extends TestCase
{
    #[Group('F-02')]
    #[TestDox('Estudiante: 200, token Bearer y usuario con rol student')]
    public function test_login_de_estudiante(): void
    {
        $this->assertLoginCorrecto('student');
    }

    #[Group('F-03')]
    #[TestDox('Padre/tutor: 200, token Bearer y usuario con rol parent')]
    public function test_login_de_padre(): void
    {
        $this->assertLoginCorrecto('parent');
    }

    #[Group('F-04')]
    #[TestDox('Personal (docente/auxiliar): 200, token Bearer y usuario con rol staff')]
    public function test_login_de_personal(): void
    {
        $this->assertLoginCorrecto('staff');
    }

    #[Group('F-02')]
    #[TestDox('Cada login emite un token distinto')]
    public function test_cada_login_emite_un_token_distinto(): void
    {
        $credenciales = DemoCredentials::for('student');

        $primero = $this->postJson('/api/auth/login', $credenciales)->json('token');
        $segundo = $this->postJson('/api/auth/login', $credenciales)->json('token');

        $this->assertNotSame($primero, $segundo);
    }

    #[Group('complementaria')]
    #[TestDox('El DNI con separadores se normaliza a dígitos antes de validar (LoginRequest)')]
    public function test_dni_con_separadores_se_normaliza(): void
    {
        $credenciales = DemoCredentials::for('student');
        $conGuion = substr($credenciales['identifier'], 0, 4).'-'.substr($credenciales['identifier'], 4);

        $this->postJson('/api/auth/login', array_merge($credenciales, ['identifier' => $conGuion]))
            ->assertOk()
            ->assertJsonPath('user.identifier', $credenciales['identifier']);
    }

    private function assertLoginCorrecto(string $perfil): void
    {
        $credenciales = DemoCredentials::for($perfil);

        $respuesta = $this->postJson('/api/auth/login', $credenciales);

        $respuesta->assertOk()
            ->assertJsonStructure([
                'token',
                'token_type',
                'user' => ['id', 'name', 'roleLabel', 'identifier', 'role'],
            ])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.role', $perfil)
            ->assertJsonPath('user.identifier', $credenciales['identifier']);

        // Contrato F1.2: token Sanctum `{id}|{secreto}`.
        $this->assertMatchesRegularExpression('/^\d+\|.+/', $respuesta->json('token'));

        // La respuesta nunca devuelve la contraseña.
        $this->assertArrayNotHasKey('password', $respuesta->json('user'));
        $this->assertStringNotContainsString($credenciales['password'], $respuesta->getContent());
    }
}
