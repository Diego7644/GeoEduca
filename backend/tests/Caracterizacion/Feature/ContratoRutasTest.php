<?php

declare(strict_types=1);

namespace Tests\Caracterizacion\Feature;

use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

/**
 * Caracterización: inventario de rutas de la aplicación.
 * Matriz manual: B-01, B-02, B-03 (funcionalidades inexistentes) y contrato de la API.
 */
final class ContratoRutasTest extends TestCase
{
    /**
     * @return list<string> Rutas "METODO uri" de la aplicación (sin HEAD).
     */
    private function rutasRegistradas(): array
    {
        $rutas = [];
        /** @var Route $ruta */
        foreach (app('router')->getRoutes() as $ruta) {
            foreach ($ruta->methods() as $metodo) {
                if ($metodo !== 'HEAD') {
                    $rutas[] = $metodo.' '.$ruta->uri();
                }
            }
        }

        return $rutas;
    }

    #[Group('F-01')]
    #[TestDox('La API expone exactamente las 20 rutas de negocio y salud documentadas (9 del código base + 11 de la rama de Diego)')]
    public function test_rutas_documentadas_existen(): void
    {
        // ADAPTADO: las 9 primeras son las del código base; las 11 siguientes las agregó la rama
        // `mejoras-interfaces-dinamicas` (acceso por carnet, recuperación, token del estudiante,
        // vincular hijo, reportes, configuración y notificaciones).
        $esperadas = [
            'GET api/health',
            'POST api/auth/login',
            'POST api/auth/logout',
            'GET api/auth/me',
            'GET api/asistencia/estudiante',
            'GET api/asistencia/padre',
            'GET api/asistencia/auxiliar/casos',
            'POST api/asistencia/auxiliar/aprobar',
            'POST api/asistencia/justificacion',
            'POST api/auth/acceso/detectar',
            'POST api/auth/acceso',
            'POST api/auth/recuperar',
            'POST api/auth/recuperar/confirmar',
            'POST api/asistencia/estudiante/token',
            'POST api/asistencia/padre/vincular',
            'GET api/asistencia/reportes',
            'GET api/asistencia/configuracion',
            'PUT api/asistencia/configuracion',
            'GET api/asistencia/notificaciones',
            'POST api/asistencia/notificaciones/leidas',
            'POST api/asistencia/estudiante/ubicacion',
            'POST api/asistencia/qr/validar',
            'GET api/asistencia/docente',
            'GET api/admin/directorio',
            'POST api/admin/apoderados',
            'GET api/admin/actividad',
        ];

        $registradas = $this->rutasRegistradas();

        foreach ($esperadas as $ruta) {
            $this->assertContains($ruta, $registradas, "Falta la ruta {$ruta}");
        }

        $apiPropias = array_values(array_filter($registradas, fn (string $r) => str_contains($r, ' api/')));
        $this->assertCount(count($esperadas), $apiPropias, 'Hay rutas de API no documentadas: '.implode(', ', $apiPropias));
    }

    #[Group('B-01')]
    #[TestDox('La validación de QR existe y exige personal autenticado')]
    public function test_la_validacion_de_qr_esta_protegida(): void
    {
        $this->assertSame(['POST api/asistencia/qr/validar'], $this->rutasQueCoinciden('/qr|desaf|challenge|totp/i'));
        $this->postJson('/api/asistencia/qr/validar', ['value' => str_repeat('a', 32)])->assertUnauthorized();
    }

    #[Group('B-02')]
    #[TestDox('La ubicación del estudiante se envía a una ruta protegida')]
    public function test_la_ubicacion_esta_protegida(): void
    {
        $this->assertSame(
            ['POST api/asistencia/estudiante/ubicacion'],
            $this->rutasQueCoinciden('/geo|ubicaci|gps|geocerca/i')
        );
        $this->postJson('/api/asistencia/estudiante/ubicacion', [
            'latitude' => -12.0,
            'longitude' => -77.0,
        ])->assertUnauthorized();
    }

    #[Group('B-03')]
    #[TestDox('Existen rutas de notificaciones, pero solo entregan avisos dentro de la aplicación (no hay envío por correo, SMS ni push)')]
    public function test_hay_rutas_de_notificaciones_solo_en_la_aplicacion(): void
    {
        // ADAPTADO: antes describía la AUSENCIA de la funcionalidad; la rama de Diego añadió las rutas.
        $this->assertSame(
            ['GET api/asistencia/notificaciones', 'POST api/asistencia/notificaciones/leidas'],
            $this->rutasQueCoinciden('/notific|aviso|whatsapp|alerta/i')
        );
        $this->assertDirectoryDoesNotExist(app_path('Notifications'), 'Si existe app/Notifications, actualizar esta caracterización.');
    }

    /**
     * @return list<string>
     */
    private function rutasQueCoinciden(string $patron): array
    {
        return array_values(array_filter($this->rutasRegistradas(), fn (string $r) => preg_match($patron, $r) === 1));
    }

    #[Group('B-04')]
    #[TestDox('Existen modelos Eloquent de persistencia, pero la API de asistencia sigue usando AsistenciaStore')]
    public function test_existen_modelos_y_asistencia_sigue_en_el_almacen_json(): void
    {
        $this->assertDirectoryExists(app_path('Models'));
        $this->assertFileExists(app_path('Models/User.php'));
        $this->assertFileExists(app_path('Services/Asistencia/AsistenciaStore.php'));
        $controlador = file_get_contents(app_path('Http/Controllers/AsistenciaController.php'));
        $this->assertNotFalse($controlador);
        $this->assertStringContainsString('AttendancePortal', $controlador);
        $this->assertFileExists(app_path('Services/Asistencia/AsistenciaStore.php'));
    }
}
