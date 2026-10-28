<?php

namespace Database\Factories;

use App\Models\Justification;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Justification>
 */
class JustificationFactory extends Factory
{
    protected $model = Justification::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'guardian_user_id' => User::factory()->parent(),
            'type' => 'medical',
            'event_date' => now()->toDateString(),
            'event_time' => '11:30',
            'reason' => 'Cita medica programada en clinica (dato ficticio)',
            'attachment_name' => null,
            'status' => Justification::STATUS_SUBMITTED,
        ];
    }
}
