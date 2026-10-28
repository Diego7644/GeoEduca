<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

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
    private ?string $almacenamientoTemporal = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->almacenamientoTemporal = sys_get_temp_dir().DIRECTORY_SEPARATOR.'geoeduca-pruebas-'.bin2hex(random_bytes(6));
        mkdir($this->almacenamientoTemporal.DIRECTORY_SEPARATOR.'app', 0777, true);
        $this->app->useStoragePath($this->almacenamientoTemporal);
    }

    protected function tearDown(): void
    {
        if ($this->almacenamientoTemporal !== null && is_dir($this->almacenamientoTemporal)) {
            $this->eliminarDirectorio($this->almacenamientoTemporal);
        }

        parent::tearDown();
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
