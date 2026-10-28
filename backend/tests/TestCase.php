<?php

declare(strict_types=1);

namespace Tests;

use App\Auth\DemoAccounts;
use App\Models\User;
use App\Support\PrototypeGraph;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Base de las pruebas de GEOEDUCA. Arranca la aplicación real (bootstrap/app.php)
 * con el entorno aislado definido en phpunit.xml. No usa base de datos.
 *
 * ADAPTACIÓN PARA LA RAMA DE INTEGRACIÓN: el almacén de asistencia (AsistenciaStore) guarda
 * sesiones, contraseñas y justificaciones en storage/app/geoeduca-state.json. Para que una
 * prueba no vea el estado de otra (ni deje archivos en el repositorio), cada prueba trabaja con
 * un directorio de almacenamiento temporal propio que se elimina al terminar.
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    private ?string $almacenamientoTemporal = null;

    /**
     * PHPUnit `force="true"` no pisa variables ya presentes en el proceso
     * (por ejemplo DB_DATABASE=geoeduca de un `.env` o de la terminal).
     * Las pruebas deben usar SQLite en memoria aunque exista PostgreSQL local.
     */
    public function createApplication()
    {
        $this->forzarEntornoSqliteDePrueba();

        return parent::createApplication();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->almacenamientoTemporal = sys_get_temp_dir().DIRECTORY_SEPARATOR.'geoeduca-pruebas-'.bin2hex(random_bytes(6));
        mkdir($this->almacenamientoTemporal.DIRECTORY_SEPARATOR.'app', 0777, true);
        $this->app->useStoragePath($this->almacenamientoTemporal);
        Cache::flush();
        $this->sembrarCuentasDePrueba();
    }

    protected function tearDown(): void
    {
        if ($this->almacenamientoTemporal !== null && is_dir($this->almacenamientoTemporal)) {
            $this->eliminarDirectorio($this->almacenamientoTemporal);
        }

        parent::tearDown();
    }

    private function sembrarCuentasDePrueba(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        foreach (DemoAccounts::all() as $account) {
            User::query()->firstOrCreate(
                ['dni' => $account['dni'], 'role' => $account['role']],
                [
                    'name' => $account['name'],
                    'role_label' => $account['roleLabel'],
                    'password' => $account['password'],
                ]
            );
        }

        PrototypeGraph::ensure();
    }

    private function forzarEntornoSqliteDePrueba(): void
    {
        foreach ([
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'DB_FOREIGN_KEYS' => 'true',
        ] as $clave => $valor) {
            putenv($clave.'='.$valor);
            $_ENV[$clave] = $valor;
            $_SERVER[$clave] = $valor;
        }
    }

    private function eliminarDirectorio(string $ruta): void
    {
        $elementos = scandir($ruta) ?: [];
        foreach ($elementos as $elemento) {
            if ($elemento === '.' || $elemento === '..') {
                continue;
            }
            $completa = $ruta.DIRECTORY_SEPARATOR.$elemento;
            is_dir($completa) ? $this->eliminarDirectorio($completa) : unlink($completa);
        }
        rmdir($ruta);
    }
}
