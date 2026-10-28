<?php

namespace Database\Factories;

use App\Models\SchoolSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolSetting>
 */
class SchoolSettingFactory extends Factory
{
    protected $model = SchoolSetting::class;

    public function definition(): array
    {
        return [
            'school' => 'Colegio de desarrollo',
            'campus' => 'Sede Central',
            'address' => 'Av. Ejemplo 100',
            'year' => '2026',
            'entry_limit' => '08:00',
            'exit_time' => '14:30',
            'geofence_m' => 50,
            'promotion_min' => 85,
            'tolerance_min' => 10,
            'lat' => -12.086200,
            'lng' => -77.054300,
        ];
    }
}
