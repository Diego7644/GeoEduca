<?php

declare(strict_types=1);

namespace Tests\Autenticacion;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\DemoCredentials;
use Tests\Support\IniciaSesion;
use Tests\TestCase;

final class TokensSanctumTest extends TestCase
{
    use IniciaSesion;

    #[TestDox('Sin token, /api/auth/me responde 401')]
    public function test_me_sin_token_responde_401(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    #[TestDox('Con un token inventado, /api/auth/me responde 401')]
    public function test_me_con_token_inventado_responde_401(): void
    {
        $this->withToken('token.falso.inventado')->getJson('/api/auth/me')->assertUnauthorized();
    }

    #[TestDox('Logout revoca el token: /me y asistencia responden 401')]
    public function test_logout_revoca_el_token(): void
    {
        $token = $this->tokenDe('student');

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();

        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
        $this->withToken($token)->getJson('/api/asistencia/estudiante')->assertUnauthorized();
        $this->assertSame(0, PersonalAccessToken::query()->count());
    }

    #[TestDox('Un token expirado responde 401')]
    public function test_token_expirado_responde_401(): void
    {
        $credenciales = DemoCredentials::for('student');
        $user = User::query()->where('dni', $credenciales['identifier'])->where('role', 'student')->first();
        $this->assertNotNull($user);

        $nuevo = $user->createToken('caducado', ['*'], now()->subMinute());
        $this->withToken($nuevo->plainTextToken)->getJson('/api/auth/me')->assertUnauthorized();
    }

    #[TestDox('El token de un estudiante no abre el panel del padre')]
    public function test_aislamiento_entre_roles(): void
    {
        $token = $this->tokenDe('student');

        $this->withToken($token)->getJson('/api/asistencia/padre')->assertForbidden();
        $this->withToken($token)->getJson('/api/asistencia/estudiante')->assertOk();
    }
}
