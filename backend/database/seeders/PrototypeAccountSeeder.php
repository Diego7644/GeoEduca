<?php

namespace Database\Seeders;

use App\Auth\DemoAccounts;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Copia hasheada de las cuentas históricas del prototipo, solo si APP_ENV=local
 * y GEO_SEED_PROTOTYPE=true. El autenticador nunca lee DemoAccounts.
 */
class PrototypeAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'development'])) {
            $this->command?->warn('PrototypeAccountSeeder solo corre en local/development.');

            return;
        }

        if (env('GEO_SEED_PROTOTYPE') !== 'true' && env('GEO_SEED_PROTOTYPE') !== true) {
            $this->command?->warn('GEO_SEED_PROTOTYPE no es true: no se siembran las cuentas del prototipo.');

            return;
        }

        foreach (DemoAccounts::all() as $account) {
            User::query()->updateOrCreate(
                ['dni' => $account['dni'], 'role' => $account['role']],
                [
                    'name' => $account['name'],
                    'role_label' => $account['roleLabel'],
                    'password' => $account['password'],
                ]
            );
        }

        $this->command?->info('Cuentas del prototipo sembradas con hash (DNI 71405255 y 40111222).');
    }
}
