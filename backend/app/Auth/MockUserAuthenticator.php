<?php

namespace App\Auth;

use App\Contracts\UserAuthenticator;
use App\Services\Asistencia\AsistenciaStore;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Autenticación simulada contra DemoAccounts.
 * Reemplazar el binding en AppServiceProvider cuando exista la BD.
 */
class MockUserAuthenticator implements UserAuthenticator
{
    public function __construct(private AsistenciaStore $store)
    {
    }

    public function authenticate(array $credentials): array
    {
        $dni = preg_replace('/\D/', '', $credentials['identifier'] ?? '');
        $password = $credentials['password'] ?? '';
        $role = $credentials['role'] ?? '';

        foreach (DemoAccounts::all() as $account) {
            $expected = $this->store->passwordFor((int) $account['id'], $account['password']);
            if ($account['dni'] === $dni && $account['role'] === $role && hash_equals($expected, $password)) {
                return $this->issue($account);
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

    public function authenticateCredential(array $credentials): array
    {
        $dni = preg_replace('/\D/', '', $credentials['credential'] ?? '') ?? '';
        $role = $credentials['role'] ?? '';

        foreach (DemoAccounts::all() as $account) {
            if ($account['dni'] === $dni && $account['role'] === $role) {
                return $this->issue($account);
            }
        }

        throw new HttpResponseException(response()->json([
            'message' => 'El carnet no corresponde a una cuenta de este perfil.',
        ], 401));
    }

    public function detectCredential(array $payload): array
    {
        foreach (DemoAccounts::all() as $account) {
            if ($account['role'] === ($payload['role'] ?? '')) {
                return [
                    'method' => $payload['method'],
                    'credential' => $account['dni'],
                    'preview' => [
                        'name' => $account['name'],
                        'roleLabel' => $account['roleLabel'],
                        'identifier' => $account['dni'],
                    ],
                ];
            }
        }

        throw new HttpResponseException(response()->json([
            'message' => 'No hay un carnet de demostración para ese perfil.',
        ], 404));
    }

    private function issue(array $account): array
    {
        $token = 'mock-'.bin2hex(random_bytes(16));
        $user = [
            'id' => $account['id'],
            'name' => $account['name'],
            'roleLabel' => $account['roleLabel'],
            'identifier' => $account['dni'],
            'role' => $account['role'],
        ];
        $this->store->rememberSession($token, $user);

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ];
    }
}
