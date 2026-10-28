<?php

declare(strict_types=1);

namespace Tests\Caracterizacion\Feature;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

/**
 * Caracterización: endpoints de salud y raíz de la API.
 * Matriz manual: F-01.
 */
#[Group('F-01')]
final class SaludTest extends TestCase
{
    #[TestDox('GET /api/health responde 200 con exactamente {"status":"ok"}')]
    public function test_endpoint_de_salud_responde_ok(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertExactJson(['status' => 'ok']);
    }

    #[TestDox('GET /api/health no requiere autenticación y devuelve JSON')]
    public function test_endpoint_de_salud_es_publico_y_json(): void
    {
        $respuesta = $this->getJson('/api/health');

        $respuesta->assertOk();
        $this->assertStringContainsString('application/json', (string) $respuesta->headers->get('Content-Type'));
    }

    #[TestDox('GET / identifica la API y apunta a /api/health')]
    public function test_ruta_raiz_identifica_la_api(): void
    {
        $this->getJson('/')
            ->assertOk()
            ->assertExactJson(['app' => 'GEOEDUCA API', 'docs' => '/api/health']);
    }

    #[TestDox('GET /up (health check de Laravel) responde 200')]
    public function test_health_check_de_laravel_responde_200(): void
    {
        $this->get('/up')->assertOk();
    }
}
