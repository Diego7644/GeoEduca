<?php

declare(strict_types=1);

namespace Tests\Caracterizacion\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

/**
 * Caracterización: política CORS de la API (config/cors.php).
 * Matriz manual: I-03, I-04.
 */
final class CorsTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function origenesPermitidos(): array
    {
        return [
            'localhost:5173' => ['http://localhost:5173'],
            '127.0.0.1:5173' => ['http://127.0.0.1:5173'],
        ];
    }

    #[Group('I-03')]
    #[DataProvider('origenesPermitidos')]
    #[TestDox('El preflight de un origen del frontend recibe Access-Control-Allow-Origin igual al origen')]
    public function test_origen_permitido(string $origen): void
    {
        $this->withHeaders([
            'Origin' => $origen,
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'content-type',
        ])->options('/api/auth/login')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', $origen);
    }

    #[Group('I-04')]
    #[TestDox('El preflight de un origen no autorizado NO recibe Access-Control-Allow-Origin')]
    public function test_origen_no_autorizado(): void
    {
        $this->withHeaders([
            'Origin' => 'http://evil.example',
            'Access-Control-Request-Method' => 'POST',
        ])->options('/api/auth/login')
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    #[Group('I-04')]
    #[TestDox('Una petición real desde un origen no autorizado tampoco recibe Access-Control-Allow-Origin')]
    public function test_peticion_real_desde_origen_no_autorizado(): void
    {
        $this->withHeaders(['Origin' => 'http://evil.example'])
            ->getJson('/api/health')
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }
}
