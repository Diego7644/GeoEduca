<?php

declare(strict_types=1);

namespace Tests\Caracterizacion\Feature;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\Cargas;
use Tests\Support\DemoCredentials;
use Tests\Support\IniciaSesion;
use Tests\TestCase;

/**
 * CARACTERIZACIÓN DEL COMPORTAMIENTO ACTUAL FRENTE A LOS DEFECTOS DE SEGURIDAD CONOCIDOS.
 *
 * Estas pruebas APRUEBAN porque describen lo que GEOEDUCA hace HOY. No son requisitos: los requisitos
 * seguros están en tests/Seguridad (suite RequisitosSeguridad), que NO se modificó.
 *
 * ADAPTADA a la rama `mejoras-interfaces-dinamicas` de Diego. Esa rama corrigió varios defectos del
 * código base (rutas de asistencia sin autenticación, sin autorización por rol, logout que no
 * invalida la sesión), por lo que las pruebas que fotografiaban ese defecto ahora describen el
 * comportamiento NUEVO (marcadas «AHORA»). Las que fotografían defectos que SIGUEN presentes se
 * conservan sin cambios (marcadas «HOY»). Cada cambio está registrado en
 * docs/01-guia-pruebas-calidad/ (REGISTRO-DE-ADAPTACION-DE-PRUEBAS.md).
 *
 * Matriz manual: S-01 a S-08.
 */
#[Group('defecto-conocido')]
final class ComportamientoActualInseguroTest extends TestCase
{
    use IniciaSesion;

    #[Group('S-01')]
    #[TestDox('AHORA: /api/auth/me sin credenciales responde 401')]
    public function test_me_sin_token_responde_401(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    #[Group('S-01')]
    #[Group('S-04')]
    #[TestDox('AHORA: /api/auth/me con un token inventado responde 401')]
    public function test_me_con_token_inventado_responde_401(): void
    {
        $this->withToken('token.falso.inventado')->getJson('/api/auth/me')->assertUnauthorized();
    }

    #[Group('S-01')]
    #[Group('S-02')]
    #[Group('S-03')]
    #[DataProvider('rutasDeAsistencia')]
    #[TestDox('AHORA: las rutas de asistencia responden 401 sin ninguna credencial')]
    public function test_rutas_de_asistencia_responden_401_sin_credenciales(string $metodo, string $uri, array $carga): void
    {
        $this->json($metodo, $uri, $carga)->assertUnauthorized();
    }

    #[Group('S-04')]
    #[DataProvider('rutasDeAsistencia')]
    #[TestDox('AHORA: un token inventado se rechaza con 401 en las rutas de asistencia')]
    public function test_token_inventado_se_rechaza(string $metodo, string $uri, array $carga): void
    {
        $this->withToken('token.falso.inventado')->json($metodo, $uri, $carga)->assertUnauthorized();
    }

    #[Group('S-01')]
    #[TestDox('AHORA: una justificación inválida SIN credenciales responde 401 (la autenticación corre antes que la validación)')]
    public function test_la_autenticacion_se_ejecuta_antes_que_la_validacion(): void
    {
        $this->postJson('/api/asistencia/justificacion', ['reason' => 'corto'])->assertUnauthorized();
    }

    #[Group('S-05')]
    #[TestDox('AHORA: el token de un estudiante recibe 403 en las rutas del auxiliar')]
    public function test_estudiante_recibe_403_en_rutas_de_personal(): void
    {
        $token = $this->tokenDe('student');

        $this->withToken($token)->getJson('/api/asistencia/auxiliar/casos')->assertForbidden();
        $this->withToken($token)->postJson('/api/asistencia/auxiliar/aprobar', Cargas::aprobacion())->assertForbidden();
    }

    #[Group('S-01')]
    #[TestDox('AHORA: un token válido identifica al usuario: /api/auth/me devuelve su rol y su DNI, sin contraseña')]
    public function test_token_valido_identifica_al_usuario(): void
    {
        $token = $this->tokenDe('student');
        $credenciales = DemoCredentials::for('student');

        $respuesta = $this->withToken($token)->getJson('/api/auth/me')->assertOk();

        $this->assertSame('student', $respuesta->json('user.role'));
        $this->assertSame($credenciales['identifier'], $respuesta->json('user.identifier'));
        $this->assertArrayNotHasKey('password', $respuesta->json('user'));
    }

    #[Group('S-06')]
    #[TestDox('AHORA: logout SIN token responde 401')]
    public function test_logout_sin_token_responde_401(): void
    {
        $this->postJson('/api/auth/logout')->assertUnauthorized();
    }

    #[Group('S-06')]
    #[TestDox('AHORA: logout CON token revoca la sesión: asistencia y /me responden 401')]
    public function test_logout_con_token_invalida_la_sesion(): void
    {
        $token = $this->tokenDe('student');

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();

        $this->withToken($token)->getJson('/api/asistencia/estudiante')->assertUnauthorized();
        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
    }

    #[Group('S-08')]
    #[TestDox('AHORA: 12 contraseñas erróneas seguidas reciben al menos un 429')]
    public function test_hay_limite_de_intentos(): void
    {
        $credenciales = DemoCredentials::for('student');
        $estados = [];

        for ($i = 1; $i <= 12; $i++) {
            $estados[] = $this->postJson('/api/auth/login', array_merge($credenciales, ['password' => "Intento-fallido-{$i}"]))
                ->getStatusCode();
        }

        $this->assertContains(429, $estados, 'Estados: '.implode(', ', $estados));
    }

    #[Group('S-01')]
    #[Group('S-08')]
    #[TestDox('AHORA: login tiene throttle y las rutas protegidas usan auth:sanctum')]
    public function test_rutas_protegidas_tienen_sanctum_y_login_tiene_throttle(): void
    {
        $router = app('router');
        $login = $router->getRoutes()->match(Request::create('/api/auth/login', 'POST'));
        $me = $router->getRoutes()->match(Request::create('/api/auth/me', 'GET'));

        $loginMw = implode(',', $router->gatherRouteMiddleware($login));
        $meMw = implode(',', $router->gatherRouteMiddleware($me));
        $this->assertStringContainsString('ThrottleRequests', $loginMw);
        $this->assertTrue(
            str_contains($meMw, 'Authenticate') || str_contains($meMw, 'auth:sanctum'),
            "me middleware inesperado: {$meMw}"
        );
    }

    #[Group('AUTO-01')]
    #[TestDox('AHORA: un DNI enviado como arreglo JSON responde 422')]
    public function test_dni_como_arreglo_responde_422(): void
    {
        $carga = array_merge(DemoCredentials::for('student'), ['identifier' => ['71405255']]);

        $this->postJson('/api/auth/login', $carga)->assertStatus(422);
    }

    #[Group('S-11')]
    #[TestDox('Con APP_DEBUG desactivado un 404 de API no filtra traza ni rutas de archivo')]
    public function test_error_404_sin_debug_no_filtra_detalles(): void
    {
        config(['app.debug' => false]);

        $this->getJson('/api/ruta-que-no-existe-geoeduca')
            ->assertNotFound()
            ->assertJsonMissingPath('trace')
            ->assertJsonMissingPath('file')
            ->assertJsonMissingPath('exception');
    }

    #[Group('S-11')]
    #[TestDox('Con APP_DEBUG activado un 404 de API SÍ filtra traza y rutas de archivo (comportamiento de Laravel)')]
    public function test_error_404_con_debug_filtra_detalles(): void
    {
        config(['app.debug' => true]);

        $this->getJson('/api/ruta-que-no-existe-geoeduca')
            ->assertNotFound()
            ->assertJsonStructure(['message', 'exception', 'file', 'line', 'trace']);
    }

    /**
     * Rutas de asistencia (todas las protegidas salvo /api/auth/me, que se prueba aparte).
     *
     * @return array<string, array{0: string, 1: string, 2: array<string, mixed>}>
     */
    public static function rutasDeAsistencia(): array
    {
        return array_filter(
            Cargas::rutasProtegidas(),
            fn (string $clave) => $clave !== 'GET /api/auth/me',
            ARRAY_FILTER_USE_KEY
        );
    }
}
