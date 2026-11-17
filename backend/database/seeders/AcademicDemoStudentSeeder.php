<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Tres estudiantes sintéticos para una instalación de demostración.
 * Los grados son ficticios. No usa nombres ni DNI identificables.
 * Si el DNI ya pertenece a otra persona, no cambia esa ficha ni sus asistencias.
 */
class AcademicDemoStudentSeeder extends Seeder
{
    public const STUDENTS = [
        [
            'dni' => '10000021',
            'name' => 'Alumno Demostración A',
            'short' => 'Demostración A',
            'code' => 'DEM-1A',
            'grade' => '1.º A',
            'grade_long' => '1.º de secundaria • Sección A',
            'grade_short' => '1.º A',
        ],
        [
            'dni' => '10000022',
            'name' => 'Alumno Demostración B',
            'short' => 'Demostración B',
            'code' => 'DEM-3B',
            'grade' => '3.º B',
            'grade_long' => '3.º de secundaria • Sección B',
            'grade_short' => '3.º B',
        ],
        [
            'dni' => '10000023',
            'name' => 'Alumno Demostración C',
            'short' => 'Demostración C',
            'code' => 'DEM-5C',
            'grade' => '5.º C',
            'grade_long' => '5.º de secundaria • Sección C',
            'grade_short' => '5.º C',
        ],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'development', 'demo', 'testing'])) {
            $this->command?->warn('AcademicDemoStudentSeeder solo corre en desarrollo, demostración o pruebas.');

            return;
        }

        $password = env('GEO_DEV_PASSWORD');
        if (! is_string($password) || strlen($password) < 8) {
            $this->command?->warn('GEO_DEV_PASSWORD no está definido: no se crean los estudiantes de demostración.');

            return;
        }

        foreach (self::STUDENTS as $row) {
            $this->seedStudent($row, $password);
        }
    }

    private function seedStudent(array $row, string $password): void
    {
        $user = User::query()->where('dni', $row['dni'])->where('role', User::ROLE_STUDENT)->first();
        $student = Student::query()->where('dni', $row['dni'])->first();

        if (($user && $user->name !== $row['name']) || ($student && $student->name !== $row['name'])) {
            $this->command?->warn('DNI '.$row['dni'].' ya tiene otra identidad. No se modifica.');

            return;
        }

        if (! $user) {
            $user = User::query()->create([
                'dni' => $row['dni'],
                'role' => User::ROLE_STUDENT,
                'name' => $row['name'],
                'role_label' => 'Estudiante',
                'password' => $password,
            ]);
        }

        if ($student) {
            return;
        }

        if (Student::query()->where('code', $row['code'])->exists()) {
            $this->command?->warn('El código '.$row['code'].' ya está usado. No se crea la ficha '.$row['dni'].'.');

            return;
        }

        Student::query()->create([
            'user_id' => $user->id,
            'dni' => $row['dni'],
            'code' => $row['code'],
            'name' => $row['name'],
            'short_name' => $row['short'],
            'grade' => $row['grade'],
            'grade_long' => $row['grade_long'],
            'grade_short' => $row['grade_short'],
            'shift' => 'Turno Mañana',
        ]);
    }
}
