<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Asistencia\AsistenciaStore;

/**
 * Identificación en puerta. Nunca emite una sesión: el carnet no sustituye al login.
 */
class GateAccessService
{
    public const READER_MESSAGE = 'No hay un lector NFC o QR conectado a este servidor. Identifique al estudiante con DNI o código en la mesa de contingencia.';

    public function __construct(
        private AsistenciaStore $store,
        private SecurityEventLogger $events
    ) {
    }

    /**
     * @return array{connected: bool, message: string}
     */
    public function readerStatus(User $actor): array
    {
        $this->events->record($actor->id, 'gate_reader_status', 'disconnected', [
            'role' => $actor->role,
        ]);

        return [
            'connected' => false,
            'message' => self::READER_MESSAGE,
        ];
    }

    /**
     * @return array{identified: bool, student: array{name: string, grade: string, code: string, dni: string}}
     */
    public function identify(User $actor, string $credential): array
    {
        try {
            $result = $this->store->identifyStudentForStaff(
                $this->store->actorForUser($actor),
                $credential
            );
        } catch (\RuntimeException $exception) {
            $this->events->record($actor->id, 'gate_identify', 'not_found', [
                'role' => $actor->role,
            ]);
            throw $exception;
        }

        $this->events->record($actor->id, 'gate_identify', 'found', [
            'role' => $actor->role,
            'code' => $result['student']['code'] ?? null,
        ]);

        return $result;
    }
}
