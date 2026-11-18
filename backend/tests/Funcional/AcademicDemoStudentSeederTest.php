<?php

declare(strict_types=1);

namespace Tests\Funcional;

use App\Models\AttendanceRecord;
use App\Models\StaffMember;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\AcademicDemoStudentSeeder;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

final class AcademicDemoStudentSeederTest extends TestCase
{
    private const PASSWORD = 'Clave-demo-21';

    protected function setUp(): void
    {
        parent::setUp();
        putenv('GEO_DEV_PASSWORD='.self::PASSWORD);
        $_ENV['GEO_DEV_PASSWORD'] = self::PASSWORD;
        $_SERVER['GEO_DEV_PASSWORD'] = self::PASSWORD;
    }

    #[TestDox('La instalación crea tres estudiantes sintéticos, cada uno con su QR')]
    public function test_crea_tres_estudiantes_con_qr_distintos(): void
    {
        Artisan::call('db:seed', ['--class' => AcademicDemoStudentSeeder::class, '--force' => true]);

        $tokens = [];
        foreach (AcademicDemoStudentSeeder::STUDENTS as $row) {
            $login = $this->postJson('/api/auth/login', [
                'identifier' => $row['dni'],
                'password' => self::PASSWORD,
                'role' => 'student',
            ])->assertOk();
            $this->assertSame($row['name'], $login->json('user.name'));

            $panel = $this->withToken($login->json('token'))->getJson('/api/asistencia/estudiante')->assertOk();
            $this->assertSame($row['grade_long'], $panel->json('profile.grade'));
            $tokens[] = $panel->json('token.value');
        }

        $this->assertCount(3, array_unique($tokens));
    }

    #[TestDox('Repetir el seeder no cambia una identidad existente ni borra asistencias')]
    public function test_no_pisa_un_dni_existente_ni_las_asistencias(): void
    {
        User::query()->create([
            'dni' => '10000021',
            'role' => User::ROLE_STUDENT,
            'name' => 'Otra identidad',
            'role_label' => 'Estudiante',
            'password' => 'Clave-ajena-21',
        ]);
        $antes = AttendanceRecord::query()->count();
        AttendanceRecord::query()->create([
            'student_id' => Student::query()->where('dni', '71405255')->value('id'),
            'attended_on' => '2026-11-17',
            'status' => AttendanceRecord::STATUS_ON_TIME,
            'source' => 'manual',
            'entry_time' => '07:40:00',
        ]);

        Artisan::call('db:seed', ['--class' => AcademicDemoStudentSeeder::class, '--force' => true]);
        Artisan::call('db:seed', ['--class' => AcademicDemoStudentSeeder::class, '--force' => true]);

        $this->assertSame('Otra identidad', User::query()->where('dni', '10000021')->where('role', 'student')->value('name'));
        $this->assertSame(1, User::query()->where('dni', '10000022')->count());
        $this->assertSame($antes + 1, AttendanceRecord::query()->count());
    }

    #[TestDox('El auxiliar registra uno de los QR y el reporte filtra su sección')]
    public function test_el_auxiliar_registra_y_el_reporte_filtra_la_seccion(): void
    {
        Artisan::call('db:seed', ['--class' => AcademicDemoStudentSeeder::class, '--force' => true]);
        $staff = User::query()->create([
            'dni' => '10000031',
            'role' => User::ROLE_STAFF,
            'name' => 'Auxiliar de demostración',
            'role_label' => 'Auxiliar',
            'password' => self::PASSWORD,
        ]);
        StaffMember::query()->create([
            'user_id' => $staff->id,
            'name' => $staff->name,
            'kind' => StaffMember::KIND_AUXILIAR,
        ]);

        $student = $this->postJson('/api/auth/login', [
            'identifier' => '10000021',
            'password' => self::PASSWORD,
            'role' => 'student',
        ])->assertOk()->json('token');
        $qr = $this->withToken($student)->getJson('/api/asistencia/estudiante')->json('token.value');

        $auxiliar = $this->postJson('/api/auth/login', [
            'identifier' => '10000031',
            'password' => self::PASSWORD,
            'role' => 'staff',
        ])->assertOk()->json('token');
        $this->withToken($auxiliar)->postJson('/api/asistencia/qr/validar', ['value' => $qr])
            ->assertOk()
            ->assertJsonPath('student.grade', '1.º A');
        $this->withToken($auxiliar)->postJson('/api/asistencia/qr/validar', ['value' => $qr])
            ->assertStatus(422);

        $admin = User::query()->create([
            'dni' => '10000032',
            'role' => User::ROLE_ADMIN,
            'name' => 'Admin de demostración',
            'role_label' => 'Administrador',
            'password' => self::PASSWORD,
        ]);
        $adminToken = $this->postJson('/api/auth/login', [
            'identifier' => $admin->dni,
            'password' => self::PASSWORD,
            'role' => 'admin',
        ])->assertOk()->json('token');
        $report = $this->withToken($adminToken)->getJson('/api/asistencia/reportes?grade=1.%C2%BA%20A')->assertOk();
        $this->assertSame(['1.º A'], array_column($report->json('rows'), 2));
    }
}
