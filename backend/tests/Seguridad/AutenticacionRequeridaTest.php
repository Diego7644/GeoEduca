<?php

declare(strict_types=1);

namespace Tests\Seguridad;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\Cargas;
use Tests\TestCase;

/**
 * REQUISITO DE SEGURIDAD: las rutas protegidas exigen autenticación.
 *
 * Estas pruebas describen el comportamiento SEGURO esperado. Con el código actual FALLAN
 * (los endpoints responden 200 sin credenciales); eso es lo que documentan.
 * No se debe modificar el resultado esperado para que pasen: se corrige el código.
 *
 * Matriz manual: S-01, S-02, S-03, S-04.
 */
#[Group('requisito-incumplido')]
final class AutenticacionRequeridaTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: array<string, mixed>}>
     */
    public static function rutasProtegidas(): array
    {
        return Cargas::rutasProtegidas();
    }

    /**
     * @param  array<string, mixed>  $carga
     */
    #[Group('S-01')]
    #[Group('S-02')]
    #[Group('S-03')]
    #[DataProvider('rutasProtegidas')]
    #[TestDox('Sin credenciales la ruta responde 401 Unauthorized')]
    public function test_sin_credenciales_responde_401(string $metodo, string $uri, array $carga): void
    {
        $this->json($metodo, $uri, $carga)->assertUnauthorized();
    }

    /**
     * @param  array<string, mixed>  $carga
     */
    #[Group('S-04')]
    #[DataProvider('rutasProtegidas')]
    #[TestDox('Con un token inventado la ruta responde 401 Unauthorized')]
    public function test_token_inventado_responde_401(string $metodo, string $uri, array $carga): void
    {
        $this->withToken('token.falso.inventado')->json($metodo, $uri, $carga)->assertUnauthorized();
    }
}
