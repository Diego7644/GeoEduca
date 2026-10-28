<?php

declare(strict_types=1);

namespace Tests\Caracterizacion\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\Cargas;
use Tests\Support\IniciaSesion;
use Tests\TestCase;

/**
 * Caracterización: contratos de los endpoints de asistencia.
 * Matriz manual: F-05, F-06, B-04.
 *
 * ADAPTADA a la rama `mejoras-interfaces-dinamicas` de Diego (cambios de contrato, no de expectativas
 * de seguridad): ahora las rutas exigen la sesión del rol correcto y devuelven el panel actualizado
 * (`panel`) en lugar de un eco de los datos o de un marcador de posición. La justificación exige
 * `child_id` y se guarda en el archivo de estado (no en base de datos). Ver docs/02-desarrollo/.
 */
final class AsistenciaContratoTest extends TestCase
{
    use IniciaSesion;

    #[Group('F-05')]
    #[TestDox('Justificación válida de un tutor: 200, mensaje de envío y el panel del padre actualizado')]
    public function test_justificacion_valida_responde_con_el_panel(): void
    {
        $token = $this->tokenDe('parent');

        $this->withToken($token)
            ->postJson('/api/asistencia/justificacion', Cargas::justificacion())
            ->assertOk()
            ->assertJsonPath('message', 'Solicitud enviada a Coordinación Académica.')
            ->assertJsonStructure(['message', 'panel']);
    }

    #[Group('F-05')]
    #[TestDox('La justificación sin hora (event_time es opcional) también se acepta')]
    public function test_justificacion_sin_hora_se_acepta(): void
    {
        $carga = Cargas::justificacion();
        unset($carga['event_time']);

        $this->withToken($this->tokenDe('parent'))
            ->postJson('/api/asistencia/justificacion', $carga)
            ->assertOk();
    }

    #[Group('F-06')]
    #[TestDox('Aprobación manual válida del auxiliar: 200, mensaje de bitácora y el panel actualizado')]
    public function test_aprobacion_valida_responde_con_el_panel(): void
    {
        $this->withToken($this->tokenDe('staff'))
            ->postJson('/api/asistencia/auxiliar/aprobar', Cargas::aprobacion())
            ->assertOk()
            ->assertJsonPath('message', 'Ingreso aprobado y anotado en la bitácora.')
            ->assertJsonStructure(['message', 'panel']);
    }

    #[Group('F-06')]
    #[TestDox('Los campos extra enviados (role, is_admin) no se reflejan en la respuesta')]
    public function test_campos_extra_no_se_reflejan(): void
    {
        $respuesta = $this->withToken($this->tokenDe('staff'))
            ->postJson('/api/asistencia/auxiliar/aprobar', Cargas::aprobacion(['role' => 'admin', 'is_admin' => true]))
            ->assertOk();

        $this->assertStringNotContainsString('is_admin', $respuesta->getContent());
    }

    /**
     * @return array<string, array{0: string, 1: string}> uri => [uri, perfil que la puede consultar]
     */
    public static function consultasPorPerfil(): array
    {
        return [
            'GET /api/asistencia/estudiante (estudiante)' => ['/api/asistencia/estudiante', 'student'],
            'GET /api/asistencia/padre (padre)' => ['/api/asistencia/padre', 'parent'],
            'GET /api/asistencia/auxiliar/casos (auxiliar)' => ['/api/asistencia/auxiliar/casos', 'staff'],
        ];
    }

    #[Group('B-04')]
    #[DataProvider('consultasPorPerfil')]
    #[TestDox('Las consultas de asistencia ya NO son marcadores de posición: devuelven un panel con datos')]
    public function test_consultas_devuelven_un_panel_y_no_el_marcador(string $uri, string $perfil): void
    {
        $respuesta = $this->withToken($this->tokenDe($perfil))->getJson($uri)->assertOk();

        $this->assertNotSame(['data' => [], 'message' => 'Pendiente de implementación'], $respuesta->json());
        $this->assertNotEmpty($respuesta->json());
        $this->assertStringNotContainsString('Pendiente de implementación', $respuesta->getContent());
    }

    #[Group('B-04')]
    #[TestDox('Una justificación enviada aparece después en el panel del padre')]
    public function test_la_justificacion_se_guarda_y_aparece_en_el_panel(): void
    {
        $token = $this->tokenDe('parent');
        $motivo = 'Justificacion para verificar persistencia';

        $this->withToken($token)
            ->postJson('/api/asistencia/justificacion', Cargas::justificacion(['reason' => $motivo]))
            ->assertOk();

        $consulta = $this->withToken($token)->getJson('/api/asistencia/padre')->assertOk();

        $this->assertStringContainsString($motivo, $consulta->getContent());
    }
}
