<?php

declare(strict_types=1);

namespace Tests\Seguridad;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\Cargas;
use Tests\Support\IniciaSesion;
use Tests\TestCase;

/**
 * REQUISITO DE SEGURIDAD: autorización por rol decidida en el servidor.
 * Un perfil autenticado no puede usar rutas de otro perfil.
 *
 * Con el código actual FALLAN (todas las rutas responden 200 a cualquier perfil).
 *
 * Matriz manual: S-05 (y UI-06, que lo reproduce desde el navegador).
 */
#[Group('requisito-incumplido')]
#[Group('S-05')]
final class AutorizacionPorRolTest extends TestCase
{
    use IniciaSesion;

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: array<string, mixed>}>
     */
    public static function usosIndebidos(): array
    {
        return [
            'estudiante → GET auxiliar/casos' => ['student', 'GET', '/api/asistencia/auxiliar/casos', []],
            'estudiante → POST auxiliar/aprobar' => ['student', 'POST', '/api/asistencia/auxiliar/aprobar', Cargas::aprobacion()],
            'estudiante → GET padre' => ['student', 'GET', '/api/asistencia/padre', []],
            'padre → GET auxiliar/casos' => ['parent', 'GET', '/api/asistencia/auxiliar/casos', []],
            'padre → POST auxiliar/aprobar' => ['parent', 'POST', '/api/asistencia/auxiliar/aprobar', Cargas::aprobacion()],
            'padre → GET estudiante' => ['parent', 'GET', '/api/asistencia/estudiante', []],
        ];
    }

    /**
     * @param  array<string, mixed>  $carga
     */
    #[DataProvider('usosIndebidos')]
    #[TestDox('Un perfil autenticado que usa la ruta de otro perfil recibe 403 Forbidden')]
    public function test_rol_incorrecto_recibe_403(string $perfil, string $metodo, string $uri, array $carga): void
    {
        $token = $this->tokenDe($perfil);

        $this->withToken($token)->json($metodo, $uri, $carga)->assertForbidden();
    }
}
