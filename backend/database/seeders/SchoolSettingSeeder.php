<?php

namespace Database\Seeders;

use App\Models\SchoolSetting;
use Illuminate\Database\Seeder;

class SchoolSettingSeeder extends Seeder
{
    public function run(): void
    {
        SchoolSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'school' => 'Colegio San Gabriel',
                'campus' => 'Sede Central',
                'address' => 'Av. Las Palmeras 450, Urb. Monterrico',
                'year' => '2026',
                'entry_limit' => '08:00',
                'exit_time' => '14:30',
                'geofence_m' => 50,
                'promotion_min' => 85,
                'tolerance_min' => 10,
                'lat' => -12.0862,
                'lng' => -77.0543,
            ]
        );
    }
}
