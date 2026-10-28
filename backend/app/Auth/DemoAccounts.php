<?php

namespace App\Auth;

/**
 * Cuentas temporales (sin BD). Al conectar Eloquent, esta clase deja de usarse.
 */
class DemoAccounts
{
    public static function all(): array
    {
        return [
            [
                'dni' => '71405255',
                'password' => 'estudiante123',
                'role' => 'student',
                'id' => 1,
                'name' => 'Mateo Valenzuela',
                'roleLabel' => 'Estudiante 4to Secundaria B',
            ],
            [
                'dni' => '71405255',
                'password' => 'padre123',
                'role' => 'parent',
                'id' => 2,
                'name' => 'Rosa Valenzuela',
                'roleLabel' => 'Padre / Tutor',
            ],
            [
                'dni' => '40111222',
                'password' => 'docente123',
                'role' => 'staff',
                'id' => 3,
                'name' => 'Aux. Ricardo Paz',
                'roleLabel' => 'Auxiliar de Puerta',
            ],
        ];
    }
}
