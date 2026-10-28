<?php

declare(strict_types=1);

namespace Tests\Caracterizacion\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\DemoCredentials;
use Tests\TestCase;

/**
 * Caracterización: validación de datos del login (LoginRequest).
 * Matriz manual: V-01, V-02, V-03, V-04, V-05, V-06.
 *
 * Los datasets contienen SOLO los campos que cambian (un valor null significa "quitar el campo",
 * y el modo "vacio" parte de un formulario sin campos). La credencial base se añade dentro de la
 * prueba para que PHPUnit nunca imprima la contraseña de demostración en un informe de fallo.
 */
final class LoginValidacionTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: array<string, mixed>, 2: list<string>}>
     */
    public static function cargasInvalidas(): array
    {
        $dni = DemoCredentials::for('student')['identifier'];

        return [
            'V-01 DNI de 7 dígitos' => ['base', ['identifier' => '7140525'], ['identifier']],
            'V-01 DNI de 9 dígitos' => ['base', ['identifier' => '714052551'], ['identifier']],
            'V-05 DNI solo con letras' => ['base', ['identifier' => 'ABCDEFGH'], ['identifier']],
            'V-02 contraseña de 3 caracteres' => ['base', ['password' => 'abc'], ['password']],
            'V-02 contraseña de 7 caracteres' => ['base', ['password' => 'abcdefg'], ['password']],
            'V-06 contraseña de 129 caracteres' => ['base', ['password' => str_repeat('a', 129)], ['password']],
            'V-03 perfil inexistente (admin)' => ['base', ['role' => 'admin'], ['role']],
            'V-03 perfil ausente' => ['base', ['role' => null], ['role']],
            'V-04 formulario vacío' => ['vacio', [], ['identifier', 'password', 'role']],
            'V-04 solo el DNI' => ['vacio', ['identifier' => $dni], ['password', 'role']],
        ];
    }

    /**
     * @param  array<string, mixed>  $cambios
     * @param  list<string>  $camposConError
     */
    #[Group('V-01')]
    #[Group('V-02')]
    #[Group('V-03')]
    #[Group('V-04')]
    #[Group('V-05')]
    #[Group('V-06')]
    #[DataProvider('cargasInvalidas')]
    #[TestDox('Login con datos inválidos responde 422, marca solo los campos erróneos y no emite token')]
    public function test_datos_invalidos_responden_422(string $modo, array $cambios, array $camposConError): void
    {
        $carga = $modo === 'vacio' ? [] : DemoCredentials::for('student');
        foreach ($cambios as $campo => $valor) {
            if ($valor === null) {
                unset($carga[$campo]);
            } else {
                $carga[$campo] = $valor;
            }
        }

        $respuesta = $this->postJson('/api/auth/login', $carga);

        $respuesta->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonMissingPath('token');

        $campos = array_keys($respuesta->json('errors'));
        sort($campos);
        $esperados = $camposConError;
        sort($esperados);

        $this->assertSame($esperados, $campos);
    }

    #[Group('V-01')]
    #[TestDox('El mensaje de DNI corto está en español y es claro')]
    public function test_mensaje_de_dni_corto(): void
    {
        $credenciales = array_merge(DemoCredentials::for('student'), ['identifier' => '7140525']);

        $this->postJson('/api/auth/login', $credenciales)
            ->assertStatus(422)
            ->assertJsonPath('errors.identifier.0', 'El DNI debe tener 8 dígitos.');
    }

    #[Group('V-02')]
    #[TestDox('El mensaje de contraseña corta indica el mínimo de 8 caracteres')]
    public function test_mensaje_de_contrasena_corta(): void
    {
        $credenciales = array_merge(DemoCredentials::for('student'), ['password' => 'abc']);

        $this->postJson('/api/auth/login', $credenciales)
            ->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'La contraseña debe tener al menos 8 caracteres.');
    }
}
