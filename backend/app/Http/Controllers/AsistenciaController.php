<?php

namespace App\Http\Controllers;

use App\Http\Requests\Asistencia\AprobarIngresoRequest;
use App\Http\Requests\Asistencia\ConfiguracionRequest;
use App\Http\Requests\Asistencia\JustificacionRequest;
use App\Http\Requests\Asistencia\VincularRequest;
use App\Services\Asistencia\AsistenciaStore;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints de asistencia. Las reglas viven en Form Requests.
 * El cuerpo de cada método es el único lugar a reemplazar cuando exista BD.
 */
class AsistenciaController extends Controller
{
    public function __construct(private AsistenciaStore $store)
    {
    }

    public function estudiante(Request $request): JsonResponse
    {
        return $this->run(fn (array $actor) => $this->store->estudiante($actor), $request);
    }

    public function refreshToken(Request $request): JsonResponse
    {
        return $this->run(fn (array $actor) => $this->store->refreshToken($actor), $request);
    }

    public function padre(Request $request): JsonResponse
    {
        return $this->run(fn (array $actor) => $this->store->padre($actor), $request);
    }

    public function vincular(VincularRequest $request): JsonResponse
    {
        return $this->run(function (array $actor) use ($request) {
            $panel = $this->store->linkChild($actor, $request->validated()['dni']);

            return ['message' => 'Estudiante vinculado a su cuenta.', 'panel' => $panel];
        }, $request);
    }

    public function casosAuxiliar(Request $request): JsonResponse
    {
        return $this->run(fn (array $actor) => $this->store->auxiliar($actor), $request);
    }

    public function aprobarIngreso(AprobarIngresoRequest $request): JsonResponse
    {
        return $this->run(function (array $actor) use ($request) {
            $panel = $this->store->approve($actor, $request->validated());

            return ['message' => 'Ingreso aprobado y anotado en la bitácora.', 'panel' => $panel];
        }, $request);
    }

    public function justificar(JustificacionRequest $request): JsonResponse
    {
        return $this->run(function (array $actor) use ($request) {
            $panel = $this->store->justify($actor, $request->validated());

            return ['message' => 'Solicitud enviada a Coordinación Académica.', 'panel' => $panel];
        }, $request);
    }

    public function reportes(Request $request): JsonResponse
    {
        return $this->run(fn (array $actor) => $this->store->reportes($actor), $request);
    }

    public function configuracion(Request $request): JsonResponse
    {
        return $this->run(fn (array $actor) => $this->store->configuracion($actor), $request);
    }

    public function actualizarConfiguracion(ConfiguracionRequest $request): JsonResponse
    {
        return $this->run(function (array $actor) use ($request) {
            $payload = $this->store->updateConfig($actor, $request->validated());

            return ['message' => 'Configuración institucional actualizada.', ...$payload];
        }, $request);
    }

    public function notificaciones(Request $request): JsonResponse
    {
        return $this->run(fn (array $actor) => ['notifications' => $this->store->notifications($actor)], $request);
    }

    public function leerNotificaciones(Request $request): JsonResponse
    {
        return $this->run(fn (array $actor) => ['notifications' => $this->store->markNotificationsRead($actor)], $request);
    }

    private function run(callable $action, Request $request): JsonResponse
    {
        try {
            return response()->json($action($this->actor($request)));
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\RuntimeException $exception) {
            $denied = str_contains($exception->getMessage(), 'No tiene acceso');

            return response()->json(['message' => $exception->getMessage()], $denied ? 403 : 422);
        }
    }

    private function actor(Request $request): array
    {
        $user = $this->store->userForToken((string) $request->bearerToken());
        if (! $user) {
            throw new HttpResponseException(response()->json(['message' => 'Sesión no válida.'], 401));
        }

        return $user;
    }
}
