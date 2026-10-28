<?php

declare(strict_types=1);

namespace Tests\Seguridad;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\IniciaSesion;
use Tests\TestCase;

/**
 * REQUISITO DE SEGURIDAD: el token queda inválido después de cerrar sesión.
 *
 * Con el código actual FALLAN: logout no revoca nada y los tokens "mock-*" nunca se validan.
 *
 * Matriz manual: S-06.
 */
#[Group('requisito-incumplido')]
#[Group('S-06')]
final class SesionYLogoutTest extends TestCase
{
    use IniciaSesion;

    #[TestDox('Tras cerrar sesión, /api/auth/me con el mismo token responde 401')]
    public function test_me_tras_logout_responde_401(): void
    {
        $token = $this->tokenDe('student');

        $this->withToken($token)->postJson('/api/auth/logout')->assertSuccessful();

        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
    }

    #[TestDox('Tras cerrar sesión, una ruta de asistencia con el mismo token responde 401')]
    public function test_asistencia_tras_logout_responde_401(): void
    {
        $token = $this->tokenDe('student');

        $this->withToken($token)->postJson('/api/auth/logout')->assertSuccessful();

        $this->withToken($token)->getJson('/api/asistencia/estudiante')->assertUnauthorized();
    }

    #[TestDox('Con la sesión abierta, /api/auth/me identifica al usuario del token')]
    public function test_me_con_token_valido_identifica_al_usuario(): void
    {
        $token = $this->tokenDe('student');

        $respuesta = $this->withToken($token)->getJson('/api/auth/me')->assertOk();

        $this->assertNotNull($respuesta->json('user'), 'Un token válido debe identificar a su usuario; devolvió user=null.');
    }
}
