<?php

declare(strict_types=1);

namespace Tests\Seguridad;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\DemoCredentials;
use Tests\TestCase;

/**
 * REQUISITO DE SEGURIDAD: limitación de intentos de login (protección contra fuerza bruta).
 *
 * Con el código actual FALLA: 12 intentos fallidos seguidos reciben siempre 401.
 *
 * Matriz manual: S-08.
 */
#[Group('requisito-incumplido')]
#[Group('S-08')]
final class LimiteIntentosLoginTest extends TestCase
{
    #[TestDox('Tras varios intentos fallidos seguidos el servidor responde 429 Too Many Requests')]
    public function test_intentos_fallidos_repetidos_reciben_429(): void
    {
        $credenciales = DemoCredentials::for('student');
        $estados = [];

        for ($i = 1; $i <= 12; $i++) {
            $estados[] = $this->postJson('/api/auth/login', array_merge($credenciales, ['password' => "Intento-fallido-{$i}"]))
                ->getStatusCode();
        }

        $this->assertContains(
            429,
            $estados,
            'Se esperaba al menos un 429 en 12 intentos fallidos seguidos. Estados obtenidos: '.implode(', ', $estados)
        );
    }
}
