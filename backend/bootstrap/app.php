<?php

use App\Http\Middleware\EnsureStaffKind;
use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\ResetAuthGuard;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: ['api/*']);
        $middleware->append(SecurityHeaders::class);
        $middleware->api(prepend: [
            ResetAuthGuard::class,
            ForceJsonResponse::class,
        ]);
        $middleware->alias([
            'role' => EnsureUserRole::class,
            'staff.kind' => EnsureStaffKind::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'No autenticado.'], 401);
            }

            return null;
        });

        $exceptions->render(function (QueryException $e, Request $request) {
            $previous = $e->getPrevious();
            $sqlState = $previous instanceof \PDOException ? (string) $previous->getCode() : '';
            $unreachable = in_array($sqlState, ['7', '08006', '08001', '08004'], true);
            if (($request->is('api/*') || $request->expectsJson()) && $unreachable) {
                return response()->json([
                    'message' => 'No se pudo conectar con la base de datos.',
                ], 503);
            }

            return null;
        });
    })->create();
