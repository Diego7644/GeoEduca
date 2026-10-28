<?php

namespace App\Contracts;

/**
 * Contrato de autenticación. El controlador y las validaciones no dependen
 * de cómo se resuelven las credenciales (mock hoy, Eloquent/Sanctum mañana).
 */
interface UserAuthenticator
{
    /**
     * @param  array{identifier: string, password: string, role: string}  $credentials
     * @return array{token: string, token_type: string, user: array<string, mixed>}
     */
    public function authenticate(array $credentials): array;
}
