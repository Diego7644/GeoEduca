<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\StaffMember;
use App\Models\Student;
use App\Models\User;
use App\Services\Asistencia\AttendancePortal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

    public function crearEstudiante(Request $request): JsonResponse
    {
        $data = $request->validate([
            'dni' => ['required', 'digits:8', 'unique:students,dni'],
            'code' => ['required', 'string', 'min:4', 'max:24', 'unique:students,code'],
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'grade' => ['required', 'string', 'min:2', 'max:40'],
            'password' => ['nullable', 'string', 'min:8', 'max:128'],
        ]);
        $student = DB::transaction(function () use ($data, $request) {
            $userId = null;
            if (! empty($data['password'])) {
                $account = User::query()->create([
                    'dni' => $data['dni'],
                    'role' => User::ROLE_STUDENT,
                    'name' => $data['name'],
                    'role_label' => 'Estudiante',
                    'password' => $data['password'],
                ]);
                $userId = $account->id;
            }
            $student = Student::query()->create([
                'user_id' => $userId,
                'dni' => $data['dni'],
                'code' => $data['code'],
                'name' => $data['name'],
                'short_name' => strtok($data['name'], ' ') ?: $data['name'],
                'grade' => $data['grade'],
                'grade_long' => $data['grade'],
                'grade_short' => $data['grade'],
                'shift' => 'Turno Mañana',
            ]);
            AuditLog::query()->create([
                'actor_user_id' => $request->user()->id,
                'student_id' => $student->id,
                'action' => 'student_created',
                'reason' => 'Alta de estudiante',
                'metadata' => [],
            ]);

            return $student;
        });

        return response()->json(['message' => 'Estudiante registrado.', 'student_id' => $student->id, 'directory' => $this->portal->directorioAdmin()], 201);
    }

    public function editarEstudiante(Request $request, Student $student): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'grade' => ['required', 'string', 'min:2', 'max:40'],
            'code' => ['required', 'string', 'min:4', 'max:24', Rule::unique('students', 'code')->ignore($student->id)],
        ]);
        $student->fill([
            'name' => $data['name'],
            'short_name' => strtok($data['name'], ' ') ?: $data['name'],
            'grade' => $data['grade'],
            'grade_long' => $data['grade'],
            'grade_short' => $data['grade'],
            'code' => $data['code'],
        ])->save();
        if ($student->user) {
            $student->user->forceFill(['name' => $data['name']])->save();
        }

        return response()->json(['message' => 'Estudiante actualizado.', 'directory' => $this->portal->directorioAdmin()]);
    }

    public function crearCuenta(Request $request): JsonResponse
    {
        $data = $request->validate([
            'dni' => ['required', 'digits:8'],
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'role' => ['required', 'in:parent,staff'],
            'staff_kind' => ['required_if:role,staff', 'in:docente,auxiliar'],
            'password' => ['required', 'string', 'min:8', 'max:128'],
        ]);
        $exists = User::query()->where('dni', $data['dni'])->where('role', $data['role'])->exists();
        if ($exists) {
            return response()->json(['message' => 'Ya existe una cuenta con ese DNI y rol.'], 422);
        }
        $user = User::query()->create([
            'dni' => $data['dni'],
            'role' => $data['role'],
            'name' => $data['name'],
            'role_label' => $data['role'] === 'parent' ? 'Padre / Tutor' : ($data['staff_kind'] === 'docente' ? 'Docente' : 'Auxiliar'),
            'password' => $data['password'],
        ]);
        if ($data['role'] === 'staff') {
            StaffMember::query()->create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'kind' => $data['staff_kind'],
            ]);
        }
        AuditLog::query()->create([
            'actor_user_id' => $request->user()->id,
            'action' => 'account_created',
            'reason' => $data['role'],
            'metadata' => ['dni_suffix' => substr($data['dni'], -2)],
        ]);

        return response()->json(['message' => 'Cuenta registrada.', 'user_id' => $user->id, 'directory' => $this->portal->directorioAdmin()], 201);
    }

    public function clave(Request $request, User $user): JsonResponse
    {
        $data = $request->validate(['password' => ['required', 'string', 'min:8', 'max:128']]);
        $user->forceFill(['password' => $data['password']])->save();
        $user->tokens()->delete();
        AuditLog::query()->create([
            'actor_user_id' => $request->user()->id,
            'action' => 'password_reset_by_admin',
            'reason' => 'Contraseña restablecida',
            'metadata' => ['user_id' => $user->id],
        ]);

        return response()->json(['message' => 'Contraseña actualizada. Las sesiones anteriores quedaron cerradas.']);
    }

    public function editarCuenta(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
        ]);
        $user->forceFill(['name' => $data['name']])->save();
        if ($user->staffProfile) {
            $user->staffProfile->forceFill(['name' => $data['name']])->save();
        }
        AuditLog::query()->create([
            'actor_user_id' => $request->user()->id,
            'action' => 'account_renamed',
            'reason' => 'Nombre actualizado',
            'metadata' => ['user_id' => $user->id],
        ]);

        return response()->json(['message' => 'Nombre actualizado.', 'directory' => $this->portal->directorioAdmin()]);
    }

    public function desvincular(Request $request): JsonResponse
    {
        $data = $request->validate([
            'guardian_dni' => ['required', 'digits:8'],
            'student_dni' => ['required', 'digits:8'],
        ]);
        $guardian = User::query()->where('dni', $data['guardian_dni'])->where('role', User::ROLE_PARENT)->first();
        $student = Student::query()->where('dni', $data['student_dni'])->first();
        if (! $guardian || ! $student) {
            return response()->json(['message' => 'No encontramos al apoderado o al estudiante.'], 422);
        }
        $guardian->wards()->detach($student->id);

        return response()->json(['message' => 'Vínculo retirado.', 'directory' => $this->portal->directorioAdmin()]);
    }

    public function actividad(): JsonResponse
    {
        return response()->json([
            'logs' => AuditLog::query()->latest('id')->limit(40)->get(['id', 'actor_user_id', 'student_id', 'action', 'reason', 'created_at']),
        ]);
    }
}
