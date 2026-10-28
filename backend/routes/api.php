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
*/

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/acceso/detectar', [AuthController::class, 'detectar']);
    Route::post('/acceso', [AuthController::class, 'acceso']);
    Route::post('/recuperar', [AuthController::class, 'recuperar']);
    Route::post('/recuperar/confirmar', [AuthController::class, 'confirmarRecuperacion']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

// TODO: proteger con middleware('auth:sanctum') cuando exista autenticación real

Route::prefix('asistencia')->group(function () {
    Route::get('/estudiante', [AsistenciaController::class, 'estudiante']);
    Route::post('/estudiante/token', [AsistenciaController::class, 'refreshToken']);
    Route::get('/padre', [AsistenciaController::class, 'padre']);
    Route::post('/padre/vincular', [AsistenciaController::class, 'vincular']);
    Route::get('/auxiliar/casos', [AsistenciaController::class, 'casosAuxiliar']);
    Route::post('/auxiliar/aprobar', [AsistenciaController::class, 'aprobarIngreso']);
    Route::post('/justificacion', [AsistenciaController::class, 'justificar']);
    Route::get('/reportes', [AsistenciaController::class, 'reportes']);
    Route::get('/configuracion', [AsistenciaController::class, 'configuracion']);
    Route::put('/configuracion', [AsistenciaController::class, 'actualizarConfiguracion']);
    Route::get('/notificaciones', [AsistenciaController::class, 'notificaciones']);
    Route::post('/notificaciones/leidas', [AsistenciaController::class, 'leerNotificaciones']);
});
