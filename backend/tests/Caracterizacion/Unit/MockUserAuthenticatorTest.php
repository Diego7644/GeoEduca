<?php

declare(strict_types=1);

namespace Tests\Caracterizacion\Unit;

use App\Auth\MockUserAuthenticator;
use App\Services\Asistencia\AsistenciaStore;
use Illuminate\Http\Exceptions\HttpResponseException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\DemoCredentials;
use Tests\TestCase;

/**
 * Caracterización: autenticador simulado (MockUserAuthenticator).
 * Extiende Tests\TestCase porque usa el helper response() del contenedor de Laravel.
 */
final class MockUserAuthenticatorTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function perfiles(): array
    {
        return array_combine(DemoCredentials::ROLES, array_map(fn (string $r) => [$r], DemoCredentials::ROLES));
    }

    #[Group('F-02')]
    #[DataProvider('perfiles')]
    #[TestDox('Con credenciales de demostración devuelve token, tipo Bearer y el usuario del perfil')]
    public function test_autentica_cuentas_de_demostracion(string $perfil): void
    {
        $credenciales = DemoCredentials::for($perfil);

        $resultado = $this->autenticador()->authenticate($credenciales);

        $this->assertSame('Bearer', $resultado['token_type']);
        $this->assertStringStartsWith('mock-', $resultado['token']);
        $this->assertSame($perfil, $resultado['user']['role']);
        $this->assertSame($credenciales['identifier'], $resultado['user']['identifier']);
        $this->assertArrayNotHasKey('password', $resultado['user']);
    }

    #[Group('N-01')]
    #[TestDox('Con contraseña incorrecta lanza una respuesta HTTP 401')]
    public function test_contrasena_incorrecta_lanza_401(): void
    {
        $credenciales = array_merge(DemoCredentials::for('student'), ['password' => 'Clave-incorrecta-01']);

        try {
            $this->autenticador()->authenticate($credenciales);
            $this->fail('Debía lanzar HttpResponseException');
        } catch (HttpResponseException $e) {
            $this->assertSame(401, $e->getResponse()->getStatusCode());
        }
    }

    #[Group('N-02')]
    #[TestDox('Exige que el perfil coincida con el de la cuenta (DNI correcto, perfil distinto)')]
    public function test_exige_coincidencia_de_perfil(): void
    {
        $personal = DemoCredentials::for('staff');

        $this->expectException(HttpResponseException::class);

        $this->autenticador()->authenticate(array_merge($personal, ['role' => 'student']));
    }

    #[Group('complementaria')]
    #[TestDox('Ignora los caracteres no numéricos del DNI')]
    public function test_ignora_caracteres_no_numericos_del_dni(): void
    {
        $credenciales = DemoCredentials::for('student');
        $sucio = substr($credenciales['identifier'], 0, 4).' . '.substr($credenciales['identifier'], 4);

        $resultado = $this->autenticador()->authenticate(array_merge($credenciales, ['identifier' => $sucio]));

        $this->assertSame($credenciales['identifier'], $resultado['user']['identifier']);
    }

    /**
     * ADAPTACION: el autenticador ahora recibe el almacen de asistencia por constructor
     * (cambio de la rama de integracion); se resuelve igual que lo hace el contenedor de Laravel.
     */
    private function autenticador(): MockUserAuthenticator
    {
        return new MockUserAuthenticator($this->app->make(AsistenciaStore::class));
    }
}
