<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Asistencia\AttendancePortal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(private AttendancePortal $portal)
    {
    }

    public function directorio(): JsonResponse
    {
        return response()->json($this->portal->directorioAdmin());
    }

    public function vincular(Request $request): JsonResponse
    {
        $data = $request->validate([
            'guardian_dni' => ['required', 'digits:8'],
            'student_dni' => ['required', 'digits:8'],
        ]);

        try {
            return response()->json($this->portal->vincularAdmin($request->user(), $data['guardian_dni'], $data['student_dni']));
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function actividad(): JsonResponse
    {
        return response()->json([
            'logs' => AuditLog::query()->latest('id')->limit(40)->get(['id', 'actor_user_id', 'student_id', 'action', 'reason', 'created_at']),
        ]);
    }
}
