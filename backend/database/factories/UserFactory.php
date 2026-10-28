<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'dni' => $this->faker->unique()->numerify('3#######'),
            'role' => User::ROLE_STUDENT,
            'name' => $this->faker->name(),
            'role_label' => null,
            'password' => 'password',
        ];
    }

    public function student(): static
    {
        return $this->state(fn () => [
            'role' => User::ROLE_STUDENT,
            'role_label' => 'Estudiante',
        ]);
    }

    public function parent(): static
    {
        return $this->state(fn () => [
            'role' => User::ROLE_PARENT,
            'role_label' => 'Padre / Tutor',
        ]);
    }

    public function staff(): static
    {
        return $this->state(fn () => [
            'role' => User::ROLE_STAFF,
            'role_label' => 'Auxiliar de Puerta',
        ]);
    }
}
