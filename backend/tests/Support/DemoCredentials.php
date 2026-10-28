<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Auth\DemoAccounts;
use RuntimeException;

/**
 * Credenciales de demostración leídas de la propia aplicación (DemoAccounts),
 * para no duplicar contraseñas dentro de los archivos de prueba.
 */
final class DemoCredentials
{
    public const ROLES = ['student', 'parent', 'staff'];

    /**
     * @return array{identifier: string, password: string, role: string}
     */
    public static function for(string $role): array
    {
        foreach (DemoAccounts::all() as $account) {
            if ($account['role'] === $role) {
                return [
                    'identifier' => $account['dni'],
                    'password' => $account['password'],
                    'role' => $role,
                ];
            }
        }

        throw new RuntimeException("No existe cuenta de demostración para el perfil {$role}");
    }
}
