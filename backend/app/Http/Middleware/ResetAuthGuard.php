<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * El RequestGuard de Sanctum recuerda al usuario en el mismo proceso
 * (PHPUnit y Octane). Cada petición de la API debe resolver su propio token.
 */
class ResetAuthGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::forgetGuards();

        return $next($request);
    }
}
