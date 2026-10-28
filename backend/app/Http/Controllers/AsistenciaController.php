<?php

namespace App\Http\Controllers;

use App\Http\Requests\Asistencia\AprobarIngresoRequest;
use App\Http\Requests\Asistencia\JustificacionRequest;
use Illuminate\Http\JsonResponse;

/**
 * Endpoints de asistencia. Las reglas viven en Form Requests.
 * El cuerpo de cada método es el único lugar a reemplazar cuando exista BD.
 */
class AsistenciaController extends Controller
{
    public function estudiante(): JsonResponse
    {
        return response()->json(['data' => [], 'message' => 'Pendiente de implementación']);
    }

    public function padre(): JsonResponse
    {
        return response()->json(['data' => [], 'message' => 'Pendiente de implementación']);
    }

    public function casosAuxiliar(): JsonResponse
    {
        return response()->json(['data' => [], 'message' => 'Pendiente de implementación']);
    }

    public function aprobarIngreso(AprobarIngresoRequest $request): JsonResponse
    {
        $data = $request->validated();

        // TODO: persistir en bitácora de contingencias.
        return response()->json([
            'message' => 'Ingreso aprobado (simulado)',
            'data' => $data,
        ]);
    }

    public function justificar(JustificacionRequest $request): JsonResponse
    {
        $data = $request->validated();

        // TODO: persistir solicitud de justificación.
        return response()->json([
            'message' => 'Solicitud enviada a Coordinación Académica.',
            'data' => $data,
        ]);
    }
}
