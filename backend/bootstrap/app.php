<?php

use App\Http\Middleware\EnsureStaffKind;
use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\ResetAuthGuard;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\AuthenticationException;
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
    })->create();
