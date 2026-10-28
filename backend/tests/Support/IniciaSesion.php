<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Inicia sesión con una cuenta de demostración a través del endpoint real
 * y devuelve el token emitido. Solo para clases que extienden Tests\TestCase.
 */
trait IniciaSesion
{
    protected function tokenDe(string $perfil): string
    {
        $respuesta = $this->postJson('/api/auth/login', DemoCredentials::for($perfil));
        $respuesta->assertOk();

        $token = $respuesta->json('token');
        $this->assertIsString($token);

        return $token;
    }
}
