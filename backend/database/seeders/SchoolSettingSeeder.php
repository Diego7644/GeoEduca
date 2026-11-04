<?php

namespace Database\Seeders;

use App\Models\SchoolSetting;
use App\Support\InstitutionIdentity;
use Illuminate\Database\Seeder;

class SchoolSettingSeeder extends Seeder
{
    public function run(): void
    {
        SchoolSetting::query()->firstOrCreate(
            ['id' => 1],
            InstitutionIdentity::settingsDefaults()
        );
    }
}
