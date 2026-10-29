<?php

declare(strict_types=1);

namespace Tests\Funcional;

use App\Models\AttendanceRecord;
use App\Models\StaffMember;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\IniciaSesion;
use Tests\TestCase;

final class AsistenciaRealTest extends TestCase
{
    use IniciaSesion;

    #[TestDox('El estudiante se resuelve por user_id, no porque coincidan los ids')]
    public function test_la_cuenta_no_se_confunde_con_el_estudiante(): void
    {
        $user = User::query()->where('dni', '71405255')->where('role', 'student')->first();
        $student = Student::query()->where('dni', '71405255')->first();

        $this->assertNotNull($user);
        $this->assertNotNull($student);
        $this->assertSame($user->id, $student->user_id);
        $this->assertNotSame($user->id, Student::query()->where('dni', '74829104')->value('id'));
    }

    #[TestDox('Un apoderado no justifica ni ve a un estudiante que no está a su cargo')]
    public function test_aislamiento_del_apoderado(): void
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

        $this->withToken($token)->getJson('/api/asistencia/padre')->assertOk()->assertJsonPath('children', []);
        $this->withToken($token)->postJson('/api/asistencia/padre/vincular', ['dni' => '71405255'])->assertStatus(422);
        $this->withToken($token)->postJson('/api/asistencia/justificacion', [
            'child_id' => Student::query()->where('dni', '71405255')->value('id'),
            'type' => 'medical',
            'event_date' => '2026-10-28',
            'reason' => 'Motivo suficiente para la prueba',
        ])->assertStatus(422);
    }

    #[TestDox('El QR se valida una sola vez y el GPS no crea asistencia')]
    public function test_qr_de_un_solo_uso_y_gps_sin_marcacion(): void
    {
        $studentToken = $this->tokenDe('student');
        $qr = $this->withToken($studentToken)->getJson('/api/asistencia/estudiante')->assertOk()->json('token.value');

        $this->withToken($studentToken)->postJson('/api/asistencia/estudiante/ubicacion', [
            'latitude' => -12.0862,
            'longitude' => -77.0543,
            'accuracy_m' => 5,
        ])->assertOk()->assertJsonPath('marked_attendance', false);

        $this->assertSame(0, AttendanceRecord::query()->count());

        $staff = $this->tokenDe('staff');
        $this->withToken($staff)->postJson('/api/asistencia/qr/validar', ['value' => $qr])
            ->assertOk()
            ->assertJsonPath('attendance.source', 'qr');

        $this->withToken($staff)->postJson('/api/asistencia/qr/validar', ['value' => $qr])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Ese código QR ya fue utilizado.');
        $this->assertSame(1, \App\Models\AttendanceAttempt::query()->where('result', 'reused')->count());

        $this->assertSame(1, AttendanceRecord::query()->count());
    }

    #[TestDox('Un docente no opera la puerta y un administrador vincula por DNI')]
    public function test_docente_y_administrador(): void
    {
        $docenteUser = User::query()->create([
            'dni' => '30000003',
            'role' => User::ROLE_STAFF,
            'name' => 'Prof. de desarrollo',
            'role_label' => 'Docente',
            'password' => 'Clave-docente-1',
        ]);
        $docente = StaffMember::query()->create([
            'user_id' => $docenteUser->id,
            'name' => $docenteUser->name,
            'kind' => StaffMember::KIND_DOCENTE,
        ]);
        Student::query()->where('dni', '71405255')->update(['homeroom_staff_id' => $docente->id]);

        $token = $this->postJson('/api/auth/login', [
            'identifier' => '30000003',
            'password' => 'Clave-docente-1',
            'role' => 'staff',
        ])->json('token');

        $this->withToken($token)->getJson('/api/asistencia/auxiliar/casos')->assertForbidden();
        $this->withToken($token)->getJson('/api/asistencia/docente')->assertOk()->assertJsonPath('students.0.dni', '71405255');

        User::query()->create([
            'dni' => '90000009',
            'role' => User::ROLE_ADMIN,
            'name' => 'Administración',
            'role_label' => 'Administrador',
            'password' => 'Clave-admin-01',
        ]);
        $adminToken = $this->postJson('/api/auth/login', [
            'identifier' => '90000009',
            'password' => 'Clave-admin-01',
            'role' => 'admin',
        ])->assertOk()->json('token');

        $this->withToken($adminToken)->postJson('/api/admin/apoderados', [
            'guardian_dni' => '71405255',
            'student_dni' => '74829104',
        ])->assertOk();

        $this->assertTrue(
            User::query()->where('dni', '71405255')->where('role', 'parent')->first()->wards()->where('dni', '74829104')->exists()
        );
    }

    #[TestDox('El administrador registra un estudiante y no se marca ausencia antes del cierre')]
    public function test_alta_y_cierre_de_jornada(): void
    {
        $admin = User::query()->create([
            'dni' => '90000008',
            'role' => User::ROLE_ADMIN,
            'name' => 'Admin de prueba',
            'role_label' => 'Administrador',
            'password' => 'Clave-admin-02',
        ]);
        $token = $this->postJson('/api/auth/login', [
            'identifier' => '90000008',
            'password' => 'Clave-admin-02',
            'role' => 'admin',
        ])->assertOk()->json('token');

        $this->withToken($token)->postJson('/api/admin/estudiantes', [
            'dni' => '55554444',
            'code' => 'NEW-1001',
            'name' => 'Alumno Nuevo',
            'grade' => '1ro A',
            'password' => 'Clave-alumno-1',
        ])->assertCreated();

        $this->assertNotSame($admin->id, Student::query()->where('dni', '55554444')->value('id'));
        \App\Models\SchoolSetting::query()->first()->forceFill(['exit_time' => '23:59'])->save();
        $this->withToken($token)->postJson('/api/asistencia/jornada/cerrar')->assertStatus(422);
    }

    #[TestDox('La importación en seco no escribe estudiantes nuevos')]
    public function test_importacion_en_seco(): void
    {
        $antes = Student::query()->count();
        Artisan::call('geoeduca:import-json', ['--dry-run' => true]);
        $this->assertSame($antes, Student::query()->count());
    }
}
