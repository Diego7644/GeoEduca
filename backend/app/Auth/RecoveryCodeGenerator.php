<?php

namespace App\Auth;

/**
 * Código numérico de 6 dígitos con generador CSPRNG.
 * 10^6 combinaciones; la defensa real es caducidad, un solo uso y el límite de intentos.
 */
class RecoveryCodeGenerator
{
    public function generate(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
