<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SchoolSettingSeeder::class,
            DevelopmentUserSeeder::class,
            PrototypeAccountSeeder::class,
        ]);
    }
}
