<?php

declare(strict_types=1);

namespace Tests\Seguridad;

use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\Cargas;
use Tests\Support\IniciaSesion;
use Tests\TestCase;

#[Group('F1.3')]
final class AislamientoRecursosTest extends TestCase
{
    use IniciaSesion;

    #[TestDox('Un padre no ve ni justifica estudiantes que no están a su cargo')]
    public function test_padre_no_accede_a_hijos_ajenos(): void
    {
        User::query()->create([
            'dni' => '18888888',
            'role' => User::ROLE_PARENT,
            'name' => 'Tutor ajeno',
            'role_label' => 'Padre / Tutor',
            'password' => 'Clave-ajena-01',
        ]);

        $token = $this->postJson('/api/auth/login', [
            'identifier' => '18888888',
            'password' => 'Clave-ajena-01',
            'role' => 'parent',
        ])->assertOk()->json('token');

        $panel = $this->withToken($token)->getJson('/api/asistencia/padre')->assertOk();
        $this->assertSame([], $panel->json('children'));

        $this->withToken($token)
            ->postJson('/api/asistencia/justificacion', Cargas::justificacion())
            ->assertStatus(422)
            ->assertJsonPath('message', 'El estudiante no está vinculado a su cuenta.');

        $this->withToken($token)
            ->postJson('/api/asistencia/padre/vincular', ['dni' => '71405255'])
            ->assertStatus(422);
    }

    #[TestDox('El rol enviado en el cuerpo no cambia los permisos del token')]
    public function test_rol_del_cuerpo_no_impersona(): void
    {
        $this->withToken($this->tokenDe('student'))
            ->postJson('/api/auth/acceso', Cargas::acceso(['role' => 'staff']))
            ->assertForbidden();

        $this->withToken($this->tokenDe('parent'))
            ->getJson('/api/asistencia/auxiliar/casos')
            ->assertForbidden();
    }

    #[TestDox('Un estudiante no abre el panel del auxiliar ni el del padre')]
    public function test_estudiante_no_abre_recursos_de_otros_roles(): void
    {
        $token = $this->tokenDe('student');

        $this->withToken($token)->getJson('/api/asistencia/auxiliar/casos')->assertForbidden();
        $this->withToken($token)->getJson('/api/asistencia/padre')->assertForbidden();
        $this->withToken($token)->getJson('/api/asistencia/estudiante')->assertOk();
    }
}
