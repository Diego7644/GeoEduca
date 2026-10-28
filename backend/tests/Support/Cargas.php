<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Cargas útiles ficticias y listas de rutas reutilizadas por varias pruebas.
 */
final class Cargas
{
    /**
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    public static function justificacion(array $cambios = []): array
    {
        return array_merge([
            'child_id' => 1,
            'type' => 'medical',
            'event_date' => '2026-10-27',
            'event_time' => '11:30',
            'reason' => 'Cita medica programada en clinica (dato ficticio)',
        ], $cambios);
    }

    /**
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    public static function aprobacion(array $cambios = []): array
    {
        return array_merge([
            'student_id' => '74829104',
            'reason' => 'Bateria agotada (carnet fisico presentado)',
        ], $cambios);
    }

    /**
     * Rutas que un sistema seguro debe proteger con autenticación.
     *
     * @return array<string, array{0: string, 1: string, 2: array<string, mixed>}>
     */
    public static function rutasProtegidas(): array
    {
        return [
            'GET /api/auth/me' => ['GET', '/api/auth/me', []],
            'GET /api/asistencia/estudiante' => ['GET', '/api/asistencia/estudiante', []],
            'GET /api/asistencia/padre' => ['GET', '/api/asistencia/padre', []],
            'GET /api/asistencia/auxiliar/casos' => ['GET', '/api/asistencia/auxiliar/casos', []],
            'POST /api/asistencia/auxiliar/aprobar' => ['POST', '/api/asistencia/auxiliar/aprobar', self::aprobacion()],
            'POST /api/asistencia/justificacion' => ['POST', '/api/asistencia/justificacion', self::justificacion()],
        ];
    }
}
