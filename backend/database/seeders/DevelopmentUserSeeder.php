<?php

namespace Database\Seeders;

use App\Models\StaffMember;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Usuarios ficticios solo para desarrollo local. No se ejecuta si falta GEO_DEV_PASSWORD.
 * No copia las cuentas hardcodeadas de DemoAccounts.
 */
class DevelopmentUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('GEO_DEV_PASSWORD');
        if (! is_string($password) || $password === '') {
            $this->command?->warn('GEO_DEV_PASSWORD no está definido: no se siembran usuarios de desarrollo.');

            return;
        }

        if (! app()->environment(['local', 'development'])) {
            $this->command?->warn('DevelopmentUserSeeder solo corre en local/development.');

            return;
        }

        $studentUser = User::query()->updateOrCreate(
            ['dni' => '10000001', 'role' => User::ROLE_STUDENT],
            [
                'name' => 'Estudiante de desarrollo',
                'role_label' => 'Estudiante 4to Secundaria B',
                'password' => $password,
            ]
        );

        $parent = User::query()->updateOrCreate(
            ['dni' => '10000002', 'role' => User::ROLE_PARENT],
            [
                'name' => 'Tutor de desarrollo',
                'role_label' => 'Padre / Tutor',
                'password' => $password,
            ]
        );

        $staffUser = User::query()->updateOrCreate(
            ['dni' => '10000003', 'role' => User::ROLE_STAFF],
            [
                'name' => 'Auxiliar de desarrollo',
                'role_label' => 'Auxiliar de Puerta',
                'password' => $password,
            ]
        );

        $auxiliar = StaffMember::query()->updateOrCreate(
            ['user_id' => $staffUser->id],
            ['name' => $staffUser->name, 'kind' => StaffMember::KIND_AUXILIAR]
        );

        $docente = StaffMember::query()->updateOrCreate(
            ['name' => 'Prof. R. Morales', 'kind' => StaffMember::KIND_DOCENTE],
            ['user_id' => null]
        );

        $student = Student::query()->updateOrCreate(
            ['dni' => '10000001'],
            [
                'user_id' => $studentUser->id,
                'homeroom_staff_id' => $docente->id,
                'code' => 'DEV-0001',
                'name' => $studentUser->name,
                'short_name' => 'Estudiante',
                'grade' => '4to Secundaria B',
                'grade_long' => '4to Secundaria • Sección B',
                'grade_short' => '4to Sec B',
                'shift' => 'Turno Mañana',
                'early_exit_time' => null,
            ]
        );

        $sibling = Student::query()->updateOrCreate(
            ['dni' => '10000004'],
            [
                'user_id' => null,
                'homeroom_staff_id' => $docente->id,
                'code' => 'DEV-0004',
                'name' => 'Hermano de desarrollo',
                'short_name' => 'Hermano',
                'grade' => '1ro Secundaria A',
                'grade_long' => '1ro Secundaria • Sección A',
                'grade_short' => '1ro Sec A',
                'shift' => 'Turno Mañana',
                'early_exit_time' => null,
            ]
        );

        $parent->wards()->syncWithoutDetaching([$student->id, $sibling->id]);

        $this->command?->info('Usuarios de desarrollo: DNI 10000001 (student), 10000002 (parent), 10000003 (staff).');
        unset($auxiliar);
    }
}
