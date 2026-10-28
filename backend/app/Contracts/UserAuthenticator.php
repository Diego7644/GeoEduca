<?php

namespace App\Contracts;

/**
 * Contrato de autenticación. Solo el login con contraseña emite sesión.
 * El acceso por carnet no autentica: vive en GateAccessService.
 */
interface UserAuthenticator
{
    /**
     * @param  array{identifier: string, password: string, role: string}  $credentials
     * @return array{token: string, token_type: string, user: array<string, mixed>}
     */
    public function authenticate(array $credentials): array;
}
