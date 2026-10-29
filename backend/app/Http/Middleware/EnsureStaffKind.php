<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Distingue docente y auxiliar por staff_members.kind.
 * El id numérico del usuario no autoriza la puerta ni el aula.
 */
class EnsureStaffKind
{
    public function handle(Request $request, Closure $next, string ...$kinds): Response
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        if ($user->isAdmin() && in_array('admin', $kinds, true)) {
            return $next($request);
        }

        $kind = $user->staffProfile?->kind;
        if ($user->isStaff() && $kind !== null && in_array($kind, $kinds, true)) {
            return $next($request);
        }

        return response()->json(['message' => 'No tiene acceso a este panel.'], 403);
    }
}
