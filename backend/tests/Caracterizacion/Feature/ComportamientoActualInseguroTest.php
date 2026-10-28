<?php

declare(strict_types=1);

namespace Tests\Caracterizacion\Feature;

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
    #[TestDox('HOY: /api/auth/me sin credenciales responde 200 con {"user":null} (debería ser 401)')]
    public function test_me_sin_token_responde_200_con_usuario_nulo(): void
    {
        $this->getJson('/api/auth/me')->assertOk()->assertExactJson(['user' => null]);
    }

    #[Group('S-01')]
    #[Group('S-04')]
    #[TestDox('HOY: /api/auth/me con un token inventado también responde 200 con {"user":null} (debería ser 401)')]
    public function test_me_con_token_inventado_responde_200_con_usuario_nulo(): void
    {
        $this->withToken('token.falso.inventado')->getJson('/api/auth/me')->assertOk()->assertExactJson(['user' => null]);
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
    #[TestDox('HOY: una justificación inválida SIN credenciales responde 422, no 401 (la validación se ejecuta antes que la autenticación)')]
    public function test_la_validacion_se_ejecuta_antes_que_la_autenticacion(): void
    {
        $this->postJson('/api/asistencia/justificacion', ['reason' => 'corto'])->assertStatus(422);
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
    #[TestDox('HOY: logout SIN token responde 200 y no invalida ninguna sesión (el token sigue aceptándose)')]
    public function test_logout_sin_token_no_invalida_nada(): void
    {
        $token = $this->tokenDe('student');

        $this->postJson('/api/auth/logout')->assertOk()->assertExactJson(['message' => 'Sesión cerrada']);
        $this->withToken($token)->getJson('/api/asistencia/estudiante')->assertOk();
    }

    #[Group('S-06')]
    #[TestDox('AHORA: logout CON token invalida la sesión: las rutas de asistencia responden 401, pero /api/auth/me responde 200 con user null')]
    public function test_logout_con_token_invalida_la_sesion(): void
    {
        $token = $this->tokenDe('student');

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();

        $this->withToken($token)->getJson('/api/asistencia/estudiante')->assertUnauthorized();
        $this->withToken($token)->getJson('/api/auth/me')->assertOk()->assertExactJson(['user' => null]);
    }

    #[Group('S-08')]
    #[TestDox('HOY: 12 contraseñas erróneas seguidas reciben siempre 401, nunca 429')]
    public function test_no_hay_limite_de_intentos(): void
    {
        $credenciales = DemoCredentials::for('student');
        $estados = [];

        for ($i = 1; $i <= 12; $i++) {
            $estados[] = $this->postJson('/api/auth/login', array_merge($credenciales, ['password' => "Intento-fallido-{$i}"]))
                ->getStatusCode();
        }

        $this->assertSame(array_fill(0, 12, 401), $estados);
    }

    #[Group('S-01')]
    #[Group('S-08')]
    #[TestDox('HOY: ninguna ruta de la API tiene middleware de autenticación ni de límite de peticiones (el 401 lo decide el controlador)')]
    public function test_rutas_sin_middleware_de_autenticacion_ni_throttle(): void
    {
        $router = app('router');

        foreach ($router->getRoutes() as $ruta) {
            if (! str_starts_with($ruta->uri(), 'api/')) {
                continue;
            }

            $middleware = implode(',', array_map('strval', $router->gatherRouteMiddleware($ruta)));
            $this->assertDoesNotMatchRegularExpression(
                '/Authenticate|auth|Throttle|throttle/',
                $middleware,
                "La ruta {$ruta->uri()} ya tiene middleware de seguridad: actualizar esta caracterización."
            );
        }
    }

    #[Group('AUTO-01')]
    #[TestDox('HOY: un DNI enviado como arreglo JSON provoca HTTP 500 (debería ser 422); causa en LoginRequest.php:22')]
    public function test_dni_como_arreglo_provoca_error_500(): void
    {
        $carga = array_merge(DemoCredentials::for('student'), ['identifier' => ['71405255']]);

        $this->postJson('/api/auth/login', $carga)->assertStatus(500);
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
