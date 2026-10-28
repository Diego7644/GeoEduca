<?php

namespace App\Services\Auth;

use App\Contracts\UserAuthenticator;

/**
 * Orquesta el login. Recibe credenciales YA validadas (LoginRequest).
 * Cambiar de mock a BD = cambiar el UserAuthenticator inyectado, no este servicio.
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

    public function attemptCredential(array $credentials): array
    {
        return $this->authenticator->authenticateCredential($credentials);
    }

    public function detect(array $payload): array
    {
        return $this->authenticator->detectCredential($payload);
    }
}
