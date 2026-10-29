<?php

declare(strict_types=1);

namespace Tests\Funcional;

use App\Models\QrToken;
use App\Models\SchoolSetting;
use App\Models\StaffMember;
use App\Models\Student;
use App\Models\User;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\IniciaSesion;
use Tests\TestCase;

final class PermisosPorRolTest extends TestCase
{
    use IniciaSesion;

    #[TestDox('Estudiante y apoderado no operan la puerta ni la administración')]
    public function test_familia_no_opera_la_puerta_ni_administracion(): void
    {
        $student = $this->tokenDe('student');
        $parent = $this->tokenDe('parent');

        foreach ([$student, $parent] as $token) {
            $this->withToken($token)->postJson('/api/asistencia/salida', ['student_id' => '71405255'])->assertForbidden();
            $this->withToken($token)->postJson('/api/asistencia/jornada/cerrar')->assertForbidden();
            $this->withToken($token)->postJson('/api/asistencia/qr/validar', ['value' => str_repeat('ab', 16)])->assertForbidden();
            $this->withToken($token)->postJson('/api/admin/estudiantes', [])->assertForbidden();
            $this->withToken($token)->getJson('/api/admin/actividad')->assertForbidden();
        }

        $this->withToken($student)->getJson('/api/asistencia/justificaciones')->assertForbidden();
        $this->withToken($parent)->postJson('/api/asistencia/justificaciones/1/revisar', ['decision' => 'approved'])->assertForbidden();
    }

    #[TestDox('El docente no cierra la jornada y el auxiliar no entra al aula')]
    public function test_docente_y_auxiliar_tienen_puertas_distintas(): void
    {
        $docenteUser = User::query()->create([
            'dni' => '30000013',
            'role' => User::ROLE_STAFF,
            'name' => 'Prof. de permisos',
            'role_label' => 'Docente',
            'password' => 'Clave-docente-1',
        ]);
        StaffMember::query()->create([
            'user_id' => $docenteUser->id,
            'name' => $docenteUser->name,
            'kind' => StaffMember::KIND_DOCENTE,
        ]);
        $docente = $this->postJson('/api/auth/login', [
            'identifier' => '30000013',
            'password' => 'Clave-docente-1',
            'role' => 'staff',
        ])->json('token');

        $this->withToken($docente)->postJson('/api/asistencia/salida', ['student_id' => '71405255'])->assertForbidden();
        $this->withToken($docente)->postJson('/api/asistencia/jornada/cerrar')->assertForbidden();
        $this->withToken($docente)->postJson('/api/asistencia/qr/validar', ['value' => str_repeat('cd', 16)])->assertForbidden();
        $this->withToken($docente)->getJson('/api/asistencia/docente')->assertOk();

        $auxiliar = $this->tokenDe('staff');
        $this->withToken($auxiliar)->getJson('/api/asistencia/docente')->assertForbidden();
        $this->withToken($auxiliar)->getJson('/api/admin/directorio')->assertForbidden();
    }

    #[TestDox('Un código vencido se rechaza sin registrar asistencia')]
    public function test_qr_vencido_no_marca_asistencia(): void
    {
        $student = Student::query()->where('dni', '74829104')->first();
        QrToken::query()->create([
            'student_id' => $student->id,
            'value' => str_repeat('e', 32),
            'expires_at' => now()->subMinute(),
        ]);

        $this->withToken($this->tokenDe('staff'))
            ->postJson('/api/asistencia/qr/validar', ['value' => str_repeat('e', 32)])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Ese código QR ya expiró.');

        $this->assertSame(0, \App\Models\AttendanceRecord::query()->where('student_id', $student->id)->count());
    }

    #[TestDox('Administración restablece la clave sin devolverla y puede guardar el horario')]
    public function test_admin_restablece_clave_y_guarda_horario(): void
    {
        $admin = User::query()->create([
            'dni' => '90000019',
            'role' => User::ROLE_ADMIN,
            'name' => 'Admin de permisos',
            'role_label' => 'Administrador',
            'password' => 'Clave-admin-01',
        ]);
        $token = $this->postJson('/api/auth/login', [
            'identifier' => '90000019',
            'password' => 'Clave-admin-01',
            'role' => 'admin',
        ])->json('token');

        $target = User::query()->where('role', User::ROLE_PARENT)->first();
        $response = $this->withToken($token)->postJson('/api/admin/usuarios/'.$target->id.'/clave', [
            'password' => 'Clave-nueva-02',
        ])->assertOk();
        $this->assertStringNotContainsString('Clave-nueva-02', $response->getContent());

        $this->postJson('/api/auth/login', [
            'identifier' => $target->dni,
            'password' => 'Clave-nueva-02',
            'role' => 'parent',
        ])->assertOk();

        $settings = SchoolSetting::query()->first();
        $this->withToken($token)->putJson('/api/asistencia/configuracion', [
            'school' => $settings->school,
            'campus' => $settings->campus,
            'address' => $settings->address,
            'year' => (string) $settings->year,
            'entry_limit' => '08:05',
            'exit_time' => '14:40',
            'geofence_m' => (int) $settings->geofence_m,
            'promotion_min' => (int) $settings->promotion_min,
            'tolerance_min' => (int) $settings->tolerance_min,
        ])->assertOk();

        $this->assertSame('08:05', substr((string) $settings->fresh()->entry_limit, 0, 5));
        $this->withToken($this->tokenDe('student'))->putJson('/api/asistencia/configuracion', [])->assertForbidden();
    }
}
