<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        $section = $this->faker->randomElement(['A', 'B', 'C']);

        return [
            'user_id' => null,
            'homeroom_staff_id' => null,
            'dni' => $this->faker->unique()->numerify('2#######'),
            'code' => 'STU-'.$this->faker->unique()->numerify('#####'),
            'name' => $this->faker->name(),
            'short_name' => $this->faker->firstName(),
            'grade' => '4to Secundaria '.$section,
            'grade_long' => '4to Secundaria • Sección '.$section,
            'grade_short' => '4to Sec '.$section,
            'shift' => 'Turno Mañana',
            'early_exit_time' => null,
        ];
    }
}
