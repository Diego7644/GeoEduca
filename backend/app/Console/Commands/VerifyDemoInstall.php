<?php

namespace App\Console\Commands;

use App\Contracts\UserAuthenticator;
use App\Models\User;
use App\Services\Asistencia\AttendancePortal;
use Database\Seeders\AcademicDemoStudentSeeder;
use Illuminate\Console\Command;

/** Comprueba una base vacía recién sembrada. No debe apuntar a la base de trabajo. */
class VerifyDemoInstall extends Command
{
    protected $signature = 'geoeduca:verificar-demo';

    protected $description = 'Comprueba login, QR y filtro de una instalación nueva';

    public function handle(UserAuthenticator $auth, AttendancePortal $portal): int
    {
        $database = (string) config('database.connections.'.config('database.default').'.database');
        if ($database !== 'geoeduca_install_check') {
            $this->error('Esta comprobación solo corre sobre geoeduca_install_check.');

            return 1;
        }
        if (! app()->environment(['local', 'development', 'demo'])) {
            $this->error('Solo en un entorno de desarrollo o demostración.');

            return 1;
        }

        $password = env('GEO_DEV_PASSWORD');
        if (! is_string($password) || strlen($password) < 8) {
            $this->error('Falta GEO_DEV_PASSWORD.');

            return 1;
        }

        $tokens = [];
        foreach (AcademicDemoStudentSeeder::STUDENTS as $row) {
            $auth->authenticate([
                'identifier' => $row['dni'],
                'password' => $password,
                'role' => 'student',
            ]);
            $user = User::query()->where('dni', $row['dni'])->where('role', 'student')->firstOrFail();
            $user->tokens()->delete();
            $panel = $portal->estudiante($user);
            $tokens[$row['dni']] = $panel['token']['value'];
            $this->line($row['dni'].' grado='.$panel['profile']['grade'].' qr='.strlen($panel['token']['value']));
        }
        $this->line('qr_distintos='.(count(array_unique($tokens)) === 3 ? 'si' : 'no'));

        $auth->authenticate(['identifier' => '10000003', 'password' => $password, 'role' => 'staff']);
        $staff = User::query()->where('dni', '10000003')->where('role', 'staff')->firstOrFail();
        $staff->tokens()->delete();
        $marked = $portal->validarQr($staff, $tokens['10000021']);
        $this->line('escaneo='.$marked['student']['grade']);
        try {
            $portal->validarQr($staff, $tokens['10000021']);
            $this->line('reuso=fail');

            return 1;
        } catch (\RuntimeException) {
            $this->line('reuso=rechazado');
        }

        $admin = User::query()->where('dni', '10000007')->where('role', 'admin')->firstOrFail();
        $report = $portal->reportes($admin, ['grade' => '1.º A']);
        $this->line('filtro='.count($report['rows']));

        return count(array_unique($tokens)) === 3 && count($report['rows']) === 1 ? 0 : 1;
    }
}
