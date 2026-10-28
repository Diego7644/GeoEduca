<?php

namespace App\Services\Auth;

use App\Contracts\UserAuthenticator;

/**
 * Orquesta el login. Recibe credenciales YA validadas (LoginRequest).
 */
class AuthService
{
    public function __construct(private UserAuthenticator $authenticator)
    {
    }

    /**
     * @param  array{identifier: string, password: string, role: string}  $credentials
     */
    public function attempt(array $credentials): array
    {
        return $this->authenticator->authenticate($credentials);
    }
}
