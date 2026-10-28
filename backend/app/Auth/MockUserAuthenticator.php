<?php

namespace App\Auth;

use App\Contracts\UserAuthenticator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Autenticación simulada contra DemoAccounts.
 * Reemplazar el binding en AppServiceProvider cuando exista la BD.
 */
class MockUserAuthenticator implements UserAuthenticator
{
    public function authenticate(array $credentials): array
    {
        $dni = preg_replace('/\D/', '', $credentials['identifier'] ?? '');
        $password = $credentials['password'] ?? '';
        $role = $credentials['role'] ?? '';

        foreach (DemoAccounts::all() as $account) {
            if ($account['dni'] === $dni && $account['role'] === $role && hash_equals($account['password'], $password)) {
                return [
                    'token' => 'mock-'.bin2hex(random_bytes(16)),
                    'token_type' => 'Bearer',
                    'user' => [
                        'id' => $account['id'],
                        'name' => $account['name'],
                        'roleLabel' => $account['roleLabel'],
                        'identifier' => $account['dni'],
                        'role' => $account['role'],
                    ],
                ];
            }
        }

        throw new HttpResponseException(response()->json([
            'message' => 'DNI o contraseña incorrectos para este perfil.',
            'errors' => [
                'identifier' => ['Revise el DNI.'],
                'password' => ['Revise la contraseña.'],
            ],
        ], 401));
    }
}
