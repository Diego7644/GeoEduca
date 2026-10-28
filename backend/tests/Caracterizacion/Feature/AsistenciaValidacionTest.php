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
 * Caracterización: validación de los formularios de justificación y aprobación manual.
 * Matriz manual: V-07, V-08, V-09.
 */
final class AsistenciaValidacionTest extends TestCase
{
    use IniciaSesion;

    /**
     * @return array<string, array{0: array<string, mixed>, 1: list<string>}>
     */
    public static function justificacionesInvalidas(): array
    {
        $sinFecha = Cargas::justificacion();
        unset($sinFecha['event_date']);

        return [
            'V-07 motivo de 5 caracteres' => [Cargas::justificacion(['reason' => 'corto']), ['reason']],
            'V-07 motivo ausente' => [array_diff_key(Cargas::justificacion(), ['reason' => 1]), ['reason']],
            'V-07 motivo de 1001 caracteres' => [Cargas::justificacion(['reason' => str_repeat('a', 1001)]), ['reason']],
            'V-08 tipo inexistente' => [Cargas::justificacion(['type' => 'inexistente']), ['type']],
            'V-08 fecha ausente' => [$sinFecha, ['event_date']],
            'V-08 fecha no válida' => [Cargas::justificacion(['event_date' => 'no-es-fecha']), ['event_date']],
            'V-08 hora con formato inválido' => [Cargas::justificacion(['event_time' => '25:99']), ['event_time']],
            // ADAPTADO: el contrato ahora exige también `child_id` (rama de integración).
            'V-08 formulario vacío' => [[], ['child_id', 'type', 'event_date', 'reason']],
        ];
    }

    /**
     * @param  array<string, mixed>  $carga
     * @param  list<string>  $camposConError
     */
    #[Group('V-07')]
    #[Group('V-08')]
    #[DataProvider('justificacionesInvalidas')]
    #[TestDox('Justificación inválida responde 422 y marca solo los campos erróneos')]
    public function test_justificacion_invalida(array $carga, array $camposConError): void
    {
        $respuesta = $this->withToken($this->tokenDe('parent'))->postJson('/api/asistencia/justificacion', $carga);

        $respuesta->assertStatus(422)->assertJsonStructure(['message', 'errors']);
        $this->assertCamposConError($camposConError, $respuesta->json('errors'));
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: list<string>}>
     */
    public static function aprobacionesInvalidas(): array
    {
        return [
            'V-09 sin motivo' => [['student_id' => '74829104'], ['reason']],
            'V-09 motivo de 3 caracteres' => [Cargas::aprobacion(['reason' => 'abc']), ['reason']],
            'V-09 identificador de 3 caracteres' => [Cargas::aprobacion(['student_id' => 'abc']), ['student_id']],
            'V-09 identificador ausente' => [['reason' => 'Bateria agotada (carnet fisico presentado)'], ['student_id']],
            'V-09 formulario vacío' => [[], ['student_id', 'reason']],
        ];
    }

    /**
     * @param  array<string, mixed>  $carga
     * @param  list<string>  $camposConError
     */
    #[Group('V-09')]
    #[DataProvider('aprobacionesInvalidas')]
    #[TestDox('Aprobación manual inválida responde 422 y marca solo los campos erróneos')]
    public function test_aprobacion_invalida(array $carga, array $camposConError): void
    {
        $respuesta = $this->withToken($this->tokenDe('staff'))->postJson('/api/asistencia/auxiliar/aprobar', $carga);

        $respuesta->assertStatus(422)->assertJsonStructure(['message', 'errors']);
        $this->assertCamposConError($camposConError, $respuesta->json('errors'));
    }

    #[Group('V-09')]
    #[TestDox('El mensaje de motivo obligatorio está en español')]
    public function test_mensaje_de_motivo_obligatorio(): void
    {
        $this->withToken($this->tokenDe('staff'))->postJson('/api/asistencia/auxiliar/aprobar', ['student_id' => '74829104'])
            ->assertStatus(422)
            ->assertJsonPath('errors.reason.0', 'El motivo de contingencia es obligatorio.');
    }

    /**
     * @param  list<string>  $esperados
     * @param  array<string, mixed>  $errores
     */
    private function assertCamposConError(array $esperados, array $errores): void
    {
        $campos = array_keys($errores);
        sort($campos);
        sort($esperados);

        $this->assertSame($esperados, $campos);
    }
}
