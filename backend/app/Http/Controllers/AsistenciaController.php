<?php

namespace App\Http\Controllers;

use App\Http\Requests\Asistencia\AprobarIngresoRequest;
use App\Http\Requests\Asistencia\ConfiguracionRequest;
use App\Http\Requests\Asistencia\JustificacionRequest;
use App\Http\Requests\Asistencia\VincularRequest;
use App\Models\User;
use App\Services\Asistencia\AttendancePortal;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AsistenciaController extends Controller
{
    public function __construct(private AttendancePortal $portal)
    {
    }

    public function estudiante(Request $request): JsonResponse
    {
        return $this->run(fn (User $user) => $this->portal->estudiante($user), $request);
    }

    public function refreshToken(Request $request): JsonResponse
    {
        return $this->run(fn (User $user) => $this->portal->refreshQr($user), $request);
    }

    public function ubicacion(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_m' => ['nullable', 'integer', 'min:0', 'max:5000'],
        ]);

        return $this->run(fn (User $user) => $this->portal->ubicacion(
            $user,
            (float) $data['latitude'],
            (float) $data['longitude'],
            isset($data['accuracy_m']) ? (int) $data['accuracy_m'] : null
        ), $request);
    }

    public function validarQr(Request $request): JsonResponse
    {
        $data = $request->validate([
            'value' => ['required', 'string', 'size:32'],
        ]);

        return $this->run(fn (User $user) => $this->portal->validarQr($user, $data['value']), $request);
    }

    public function padre(Request $request): JsonResponse
    {
        return $this->run(fn (User $user) => $this->portal->padre($user), $request);
    }

    public function vincular(VincularRequest $request): JsonResponse
    {
        return $this->run(fn (User $user) => $this->portal->vincular($user, $request->validated()['dni']), $request);
    }

    public function casosAuxiliar(Request $request): JsonResponse
    {
        return $this->run(fn (User $user) => $this->portal->auxiliar($user), $request);
    }

    public function aprobarIngreso(AprobarIngresoRequest $request): JsonResponse
    {
        return $this->run(fn (User $user) => $this->portal->aprobar($user, $request->validated()), $request);
    }

    public function justificar(JustificacionRequest $request): JsonResponse
    {
        return $this->run(fn (User $user) => $this->portal->justificar($user, $request->validated()), $request);
    }

    public function docente(Request $request): JsonResponse
    {
        return $this->run(fn (User $user) => $this->portal->docente($user), $request);
    }

    public function reportes(Request $request): JsonResponse
    {
        return $this->run(fn (User $user) => $this->portal->reportes($user), $request);
    }

    public function configuracion(Request $request): JsonResponse
    {
        return $this->run(fn (User $user) => $this->portal->configuracion($user), $request);
    }

    public function actualizarConfiguracion(ConfiguracionRequest $request): JsonResponse
    {
        return $this->run(fn (User $user) => $this->portal->actualizarConfiguracion($user, $request->validated()), $request);
    }

    public function notificaciones(Request $request): JsonResponse
    {
        return $this->run(fn (User $user) => $this->portal->notificaciones($user), $request);
    }

    public function leerNotificaciones(Request $request): JsonResponse
    {
        return $this->run(fn (User $user) => $this->portal->leerNotificaciones($user), $request);
    }

    private function run(callable $action, Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (! $user instanceof User) {
                throw new HttpResponseException(response()->json(['message' => 'No autenticado.'], 401));
            }

            return response()->json($action($user));
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\RuntimeException $exception) {
            $denied = str_contains($exception->getMessage(), 'No tiene acceso');

            return response()->json(['message' => $exception->getMessage()], $denied ? 403 : 422);
        }
    }
}
