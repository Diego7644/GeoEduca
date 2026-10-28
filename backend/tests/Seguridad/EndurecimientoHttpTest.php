<?php

declare(strict_types=1);

namespace Tests\Seguridad;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

/**
 * REQUISITOS DE ENDURECIMIENTO HTTP Y ROBUSTEZ DE LA API.
 *
 * Con el código actual FALLAN. No se incluye S-09 (cabecera X-Powered-By) porque la añade el
 * intérprete de PHP (expose_php) y no Laravel: no es observable en una prueba en proceso.
 *
 * Matriz manual: S-10, S-11, I-05.
 */
#[Group('requisito-incumplido')]
final class EndurecimientoHttpTest extends TestCase
{
    #[Group('S-10')]
    #[TestDox('Las respuestas incluyen X-Content-Type-Options: nosniff')]
    public function test_cabecera_nosniff(): void
    {
        $this->getJson('/api/health')->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    #[Group('S-11')]
    #[TestDox('Con la configuración por defecto del proyecto (.env.example) un 404 de API no filtra traza ni rutas')]
    public function test_error_404_con_configuracion_por_defecto_no_filtra_detalles(): void
    {
        $ejemplo = (string) file_get_contents(base_path('.env.example'));
        $this->assertMatchesRegularExpression('/^APP_DEBUG=/m', $ejemplo, '.env.example debe declarar APP_DEBUG');

        preg_match('/^APP_DEBUG=(\S+)/m', $ejemplo, $coincidencia);
        $depuracion = filter_var($coincidencia[1] ?? 'false', FILTER_VALIDATE_BOOLEAN);

        config(['app.debug' => $depuracion]);

        $cuerpo = $this->getJson('/api/ruta-que-no-existe-geoeduca')->assertNotFound()->json();

        $filtrados = array_values(array_intersect(['trace', 'file', 'line', 'exception'], array_keys($cuerpo)));
        $this->assertSame(
            [],
            $filtrados,
            'Con APP_DEBUG='.($depuracion ? 'true' : 'false').' (valor de .env.example) la respuesta de error expone: '.implode(', ', $filtrados)
        );
    }

    #[Group('I-05')]
    #[TestDox('Un error de validación sin cabecera Accept responde 422 JSON, no una redirección')]
    public function test_validacion_sin_accept_responde_422(): void
    {
        $this->post('/api/auth/login', [
            'identifier' => '7140525',
            'password' => 'cualquier-valor',
            'role' => 'student',
        ])->assertStatus(422);
    }
}
