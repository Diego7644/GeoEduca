<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'recorded_by_user_id' => null,
            'attended_on' => now()->toDateString(),
            'status' => AttendanceRecord::STATUS_ON_TIME,
            'method' => 'manual',
            'gate' => 'Puerta A',
            'entry_time' => '07:50:00',
            'exit_time' => null,
            'late_minutes' => null,
            'reason' => null,
        ];
    }
}
