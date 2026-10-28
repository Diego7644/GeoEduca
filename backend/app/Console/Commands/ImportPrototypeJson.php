<?php

namespace App\Console\Commands;

use App\Models\SchoolSetting;
use App\Models\StaffMember;
use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportPrototypeJson extends Command
{
    protected $signature = 'geoeduca:import-json {--dry-run : Simula la importación y escribe el informe sin modificar datos}';

    protected $description = 'Importa el catálogo del prototipo JSON a PostgreSQL sin copiar contraseñas ni borrar el archivo';

    public function handle(): int
    {
        $path = storage_path('app/geoeduca-state.json');
        $dry = (bool) $this->option('dry-run');
        $report = [
            'dry_run' => $dry,
            'source' => is_file($path) ? $path : null,
            'created' => [],
            'updated' => [],
            'skipped' => [],
            'warnings' => [
                'El historial calculado del prototipo no se importa: no son registros reales.',
                'Las contraseñas del JSON no se copian.',
            ],
        ];

        if (! $dry && is_file($path)) {
            $backup = storage_path('app/backups');
            if (! is_dir($backup)) {
                mkdir($backup, 0777, true);
            }
            $target = $backup.'/geoeduca-state-'.now()->format('YmdHis').'.json';
            copy($path, $target);
            $report['backup'] = $target;
        }

        $config = [];
        if (is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded) && isset($decoded['passwords']) && $decoded['passwords'] !== []) {
                $report['warnings'][] = 'Se ignoraron contraseñas en texto claro encontradas en el JSON.';
            }
            $config = is_array($decoded['config'] ?? null) ? $decoded['config'] : [];
        }

        $apply = function () use ($config, &$report, $dry) {
            $this->settings($config, $report, $dry);
            foreach ($this->roster() as $row) {
                $this->student($row, $report, $dry);
            }
            $this->linkKnownAccounts($report, $dry);
        };

        if ($dry) {
            $apply();
        } else {
            DB::transaction($apply);
        }

        $dir = storage_path('app/import-reports');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $reportPath = $dir.'/import-'.now()->format('YmdHis').'.json';
        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info(($dry ? 'Simulación' : 'Importación').' escrita en '.$reportPath);

        return self::SUCCESS;
    }

    private function settings(array $config, array &$report, bool $dry): void
    {
        $values = [
            'school' => $config['school'] ?? 'Colegio San Gabriel',
            'campus' => $config['campus'] ?? 'Sede Central',
            'address' => $config['address'] ?? 'Av. Las Palmeras 450, Urb. Monterrico',
            'year' => (string) ($config['year'] ?? '2026'),
            'entry_limit' => substr((string) ($config['entry_limit'] ?? '08:00'), 0, 5),
            'exit_time' => substr((string) ($config['exit_time'] ?? '14:30'), 0, 5),
            'geofence_m' => (int) ($config['geofence_m'] ?? 50),
            'promotion_min' => (int) ($config['promotion_min'] ?? 85),
            'tolerance_min' => (int) ($config['tolerance_min'] ?? 10),
            'lat' => $config['lat'] ?? -12.0862,
            'lng' => $config['lng'] ?? -77.0543,
        ];
        $exists = SchoolSetting::query()->exists();
        $report[$exists ? 'updated' : 'created'][] = 'school_settings';
        if ($dry) {
            return;
        }
        $current = SchoolSetting::query()->first();
        $current ? $current->fill($values)->save() : SchoolSetting::query()->create($values);
    }

    private function student(array $row, array &$report, bool $dry): void
    {
        $exists = Student::query()->where('dni', $row['dni'])->exists();
        $report[$exists ? 'updated' : 'created'][] = 'student:'.$row['dni'];
        if ($dry) {
            return;
        }
        $user = User::query()->where('dni', $row['dni'])->where('role', User::ROLE_STUDENT)->first();
        Student::query()->updateOrCreate(
            ['dni' => $row['dni']],
            [
                'user_id' => $user?->id,
                'code' => $row['code'],
                'name' => $row['name'],
                'short_name' => $row['short_name'],
                'grade' => $row['grade'],
                'grade_long' => $row['grade_long'],
                'grade_short' => $row['grade_short'],
                'shift' => 'Turno Mañana',
                'early_exit_time' => $row['exit'],
            ]
        );
    }

    private function linkKnownAccounts(array &$report, bool $dry): void
    {
        $parent = User::query()->where('dni', '71405255')->where('role', User::ROLE_PARENT)->first();
        $staff = User::query()->where('dni', '40111222')->where('role', User::ROLE_STAFF)->first();
        if ($parent) {
            $ids = Student::query()->whereIn('dni', ['71405255', '70112233', '70998877'])->pluck('id');
            $report['updated'][] = 'guardian:71405255';
            if (! $dry) {
                $parent->wards()->syncWithoutDetaching($ids->all());
            }
        } else {
            $report['skipped'][] = 'guardian:71405255 ausente';
        }
        if ($staff) {
            $report['updated'][] = 'staff:40111222 auxiliar';
            if (! $dry) {
                StaffMember::query()->updateOrCreate(
                    ['user_id' => $staff->id],
                    ['name' => $staff->name, 'kind' => StaffMember::KIND_AUXILIAR]
                );
            }
        }
        $report['warnings'][] = 'El id 2 del JSON no se usa como users.id. El apoderado se reconoce por el DNI 71405255 y el rol parent.';
    }

    private function roster(): array
    {
        return [
            ['dni' => '71405255', 'code' => 'STU-88219', 'name' => 'Mateo Valenzuela', 'short_name' => 'Mateo', 'grade' => '4to Secundaria B', 'grade_long' => '4to Secundaria • Sección B', 'grade_short' => '4to Sec B', 'exit' => null],
            ['dni' => '70112233', 'code' => 'STU-77102', 'name' => 'Lucía Valenzuela', 'short_name' => 'Lucía', 'grade' => '1ro Secundaria A', 'grade_long' => '1ro Secundaria • Sección A', 'grade_short' => '1ro Sec A', 'exit' => null],
            ['dni' => '70998877', 'code' => 'PRI-2204', 'name' => 'Diego Valenzuela', 'short_name' => 'Diego', 'grade' => '2do Primaria C', 'grade_long' => '2do Primaria • Sección C', 'grade_short' => '2do Prim C', 'exit' => '13:00'],
            ['dni' => '74829104', 'code' => 'SEC-5021', 'name' => 'Sofía Morales Vega', 'short_name' => 'Sofía', 'grade' => '5to Secundaria A', 'grade_long' => '5to Secundaria - Sección A', 'grade_short' => '5to Sec A', 'exit' => null],
        ];
    }
}
