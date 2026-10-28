<?php

declare(strict_types=1);

namespace Tests\Persistencia;

use App\Models\AttendanceRecord;
use App\Models\Justification;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\TestDox;

final class AsistenciaPersistenciaTest extends PersistenciaTestCase
{
    #[TestDox('Se puede registrar una asistencia y consultarla')]
    public function test_crear_y_consultar_asistencia(): void
    {
        $student = Student::factory()->create();
        $record = AttendanceRecord::factory()->create([
            'student_id' => $student->id,
            'attended_on' => '2026-10-28',
            'status' => AttendanceRecord::STATUS_ON_TIME,
        ]);

        $found = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->whereDate('attended_on', '2026-10-28')
            ->first();

        $this->assertNotNull($found);
        $this->assertTrue($found->is($record));
        $this->assertTrue($student->attendanceRecords()->whereKey($record->id)->exists());
    }

    #[TestDox('No se duplica la asistencia del mismo estudiante en el mismo día')]
    public function test_una_asistencia_por_estudiante_y_dia(): void
    {
        $student = Student::factory()->create();
        AttendanceRecord::factory()->create([
            'student_id' => $student->id,
            'attended_on' => '2026-10-28',
        ]);

        $this->expectException(QueryException::class);
        AttendanceRecord::factory()->create([
            'student_id' => $student->id,
            'attended_on' => '2026-10-28',
        ]);
    }

    #[TestDox('Una justificación queda ligada al tutor y al estudiante')]
    public function test_justificacion_persistida(): void
    {
        $parent = User::factory()->parent()->create();
        $student = Student::factory()->create();
        $parent->wards()->attach($student);

        $justification = Justification::factory()->create([
            'student_id' => $student->id,
            'guardian_user_id' => $parent->id,
        ]);

        $this->assertTrue($student->justifications()->whereKey($justification->id)->exists());
        $this->assertTrue($parent->justifications()->whereKey($justification->id)->exists());
    }
}
