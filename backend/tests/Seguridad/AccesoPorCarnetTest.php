<?php

declare(strict_types=1);

namespace Tests\Seguridad;

use App\Models\AuditLog;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\Cargas;
use Tests\Support\IniciaSesion;
use Tests\TestCase;

#[Group('F1.3')]
final class AccesoPorCarnetTest extends TestCase
{
    use IniciaSesion;

    #[TestDox('POST /api/auth/acceso sin autenticación responde 401 y no emite token')]
    public function test_acceso_sin_autenticacion_responde_401(): void
    {
        $this->postJson('/api/auth/acceso', Cargas::acceso())
            ->assertUnauthorized()
            ->assertJsonMissingPath('token')
            ->assertJsonMissingPath('user');

        $this->assertSame(0, PersonalAccessToken::query()->count());
    }

    #[TestDox('POST /api/auth/acceso con token inválido responde 401')]
    public function test_acceso_con_token_invalido_responde_401(): void
    {
        $this->withToken('token.falso.inventado')
            ->postJson('/api/auth/acceso', Cargas::acceso())
            ->assertUnauthorized()
            ->assertJsonMissingPath('token');
    }

    #[TestDox('Un estudiante autenticado no puede usar el acceso por carnet')]
    public function test_estudiante_recibe_403_en_acceso(): void
    {
        $this->withToken($this->tokenDe('student'))
            ->postJson('/api/auth/acceso', Cargas::acceso())
            ->assertForbidden()
            ->assertJsonMissingPath('token');
    }

    #[TestDox('El personal identifica un DNI en puerta sin crear sesión nueva')]
    public function test_personal_identifica_sin_emitir_sesion(): void
    {
        $token = $this->tokenDe('staff');
        $antes = PersonalAccessToken::query()->count();

        $this->withToken($token)
            ->postJson('/api/auth/acceso', Cargas::acceso())
            ->assertOk()
            ->assertJsonPath('identified', true)
            ->assertJsonPath('student.dni', '71405255')
            ->assertJsonMissingPath('token')
            ->assertJsonMissingPath('user');

        $this->assertSame($antes, PersonalAccessToken::query()->count());
        $this->assertSame(1, AuditLog::query()->where('action', 'gate_identify')->count());
    }

    #[TestDox('detectar exige personal autenticado y no revela un DNI ajeno')]
    public function test_detectar_no_enumera_cuentas(): void
    {
        $this->postJson('/api/auth/acceso/detectar', ['method' => 'nfc', 'role' => 'student'])
            ->assertUnauthorized();

        $this->withToken($this->tokenDe('staff'))
            ->postJson('/api/auth/acceso/detectar', ['method' => 'nfc', 'role' => 'student'])
            ->assertOk()
            ->assertJsonPath('connected', false)
            ->assertJsonMissingPath('credential')
            ->assertJsonMissingPath('preview')
            ->assertJsonMissingPath('token');
    }
}
