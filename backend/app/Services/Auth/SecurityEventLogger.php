<?php

namespace App\Services\Auth;

use App\Models\AuditLog;

/**
 * Bitácora de seguridad. Nunca guarda códigos, contraseñas ni tokens.
 */
class SecurityEventLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(?int $actorUserId, string $action, ?string $reason = null, array $metadata = []): void
    {
        AuditLog::query()->create([
            'actor_user_id' => $actorUserId,
            'student_id' => null,
            'action' => $action,
            'reason' => $reason,
            'metadata' => $this->sanitize($metadata),
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function sanitize(array $metadata): array
    {
        foreach (['password', 'code', 'token', 'code_hash', 'demo_code', 'dev_code'] as $secret) {
            unset($metadata[$secret]);
        }

        return $metadata;
    }
}
