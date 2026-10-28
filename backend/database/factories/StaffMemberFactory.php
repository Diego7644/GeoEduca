<?php

namespace Database\Factories;

use App\Models\StaffMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaffMember>
 */
class StaffMemberFactory extends Factory
{
    protected $model = StaffMember::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'name' => 'Prof. '.$this->faker->lastName(),
            'kind' => StaffMember::KIND_DOCENTE,
        ];
    }

    public function auxiliar(): static
    {
        return $this->state(fn () => ['kind' => StaffMember::KIND_AUXILIAR]);
    }
}
