<?php

use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - GEOEDUCA
|--------------------------------------------------------------------------
| Prefijo /api aplicado automáticamente (bootstrap/app.php).
| El frontend (React) consume estas rutas desde src/services/apiClient.js.
|
| El rol se toma del usuario autenticado (Sanctum), nunca del cuerpo JSON.
*/

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/recuperar', [AuthController::class, 'recuperar'])->middleware('throttle:recovery');
    Route::post('/recuperar/confirmar', [AuthController::class, 'confirmarRecuperacion'])->middleware('throttle:recovery');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::middleware('role:staff')->group(function () {
            Route::post('/acceso/detectar', [AuthController::class, 'detectar'])->middleware('throttle:login');
            Route::post('/acceso', [AuthController::class, 'acceso'])->middleware('throttle:login');
        });
    });
});

Route::middleware('auth:sanctum')->prefix('asistencia')->group(function () {
    Route::middleware('role:student')->group(function () {
        Route::get('/estudiante', [AsistenciaController::class, 'estudiante']);
        Route::post('/estudiante/token', [AsistenciaController::class, 'refreshToken']);
        Route::post('/estudiante/ubicacion', [AsistenciaController::class, 'ubicacion']);
    });

    Route::middleware('role:parent')->group(function () {
        Route::get('/padre', [AsistenciaController::class, 'padre']);
        Route::post('/padre/vincular', [AsistenciaController::class, 'vincular']);
        Route::post('/justificacion', [AsistenciaController::class, 'justificar']);
    });

    Route::middleware(['role:staff', 'staff.kind:auxiliar'])->group(function () {
        Route::get('/auxiliar/casos', [AsistenciaController::class, 'casosAuxiliar']);
        Route::post('/auxiliar/aprobar', [AsistenciaController::class, 'aprobarIngreso']);
        Route::post('/qr/validar', [AsistenciaController::class, 'validarQr']);
    });

    Route::middleware(['role:staff', 'staff.kind:docente'])->group(function () {
        Route::get('/docente', [AsistenciaController::class, 'docente']);
    });

    Route::middleware('staff.kind:auxiliar,admin')->group(function () {
        Route::put('/configuracion', [AsistenciaController::class, 'actualizarConfiguracion']);
        Route::post('/salida', [AsistenciaController::class, 'salida']);
        Route::post('/jornada/cerrar', [AsistenciaController::class, 'cerrarJornada']);
    });

    Route::middleware('role:student,parent,staff,admin')->group(function () {
        Route::get('/reportes', [AsistenciaController::class, 'reportes']);
        Route::get('/configuracion', [AsistenciaController::class, 'configuracion']);
        Route::get('/notificaciones', [AsistenciaController::class, 'notificaciones']);
        Route::post('/notificaciones/leidas', [AsistenciaController::class, 'leerNotificaciones']);
    });

    Route::middleware('role:parent,staff,admin')->group(function () {
        Route::get('/justificaciones', [AsistenciaController::class, 'justificaciones']);
    });

    Route::middleware('role:staff,admin')->group(function () {
        Route::post('/justificaciones/{id}/revisar', [AsistenciaController::class, 'revisarJustificacion']);
    });
});

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/directorio', [\App\Http\Controllers\AdminController::class, 'directorio']);
    Route::post('/estudiantes', [\App\Http\Controllers\AdminController::class, 'crearEstudiante']);
    Route::put('/estudiantes/{student}', [\App\Http\Controllers\AdminController::class, 'editarEstudiante']);
    Route::post('/cuentas', [\App\Http\Controllers\AdminController::class, 'crearCuenta']);
    Route::post('/usuarios/{user}/clave', [\App\Http\Controllers\AdminController::class, 'clave']);
    Route::put('/usuarios/{user}', [\App\Http\Controllers\AdminController::class, 'editarCuenta']);
    Route::post('/apoderados', [\App\Http\Controllers\AdminController::class, 'vincular']);
    Route::delete('/apoderados', [\App\Http\Controllers\AdminController::class, 'desvincular']);
    Route::get('/actividad', [\App\Http\Controllers\AdminController::class, 'actividad']);
});
