<?php

declare(strict_types=1);

namespace Tests\Persistencia;

use App\Models\StaffMember;
use App\Models\Student;
use App\Models\User;
use PHPUnit\Framework\Attributes\TestDox;

final class RelacionesTest extends PersistenciaTestCase
{
    #[TestDox('Un tutor queda vinculado a sus estudiantes')]
    public function test_tutor_ve_a_sus_hijos(): void
    {
        $parent = User::factory()->parent()->create();
        $student = Student::factory()->create();
        $parent->wards()->attach($student);

        $this->assertTrue($parent->wards()->whereKey($student->id)->exists());
        $this->assertTrue($student->guardians()->whereKey($parent->id)->exists());
    }

    #[TestDox('Un docente queda asociado al aula del estudiante')]
    public function test_estudiante_tiene_tutor_de_aula(): void
    {
        $docente = StaffMember::factory()->create(['kind' => StaffMember::KIND_DOCENTE]);
        $student = Student::factory()->create(['homeroom_staff_id' => $docente->id]);

        $this->assertTrue($student->homeroomStaff()->is($docente));
        $this->assertTrue($docente->homeroomStudents()->whereKey($student->id)->exists());
    }

    #[TestDox('Un auxiliar puede tener cuenta de usuario')]
    public function test_auxiliar_con_cuenta(): void
    {
        $user = User::factory()->staff()->create();
        $staff = StaffMember::factory()->auxiliar()->create([
            'user_id' => $user->id,
            'name' => $user->name,
        ]);

        $this->assertTrue($user->staffProfile()->is($staff));
        $this->assertTrue($staff->user()->is($user));
    }
}
