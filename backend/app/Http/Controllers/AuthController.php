<?php

namespace App\Http\Controllers;

use App\Auth\EloquentUserAuthenticator;
use App\Http\Requests\Auth\AccesoRequest;
use App\Http\Requests\Auth\DetectAccesoRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RecoveryConfirmRequest;
use App\Http\Requests\Auth\RecoveryRequest;
use App\Services\Asistencia\AsistenciaStore;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function __construct(private AuthService $auth, private AsistenciaStore $store)
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        return response()->json($this->auth->attempt($request->validated()));
    }

    public function detectar(DetectAccesoRequest $request): JsonResponse
    {
        return response()->json($this->auth->detect($request->validated()));
    }

    public function acceso(AccesoRequest $request): JsonResponse
    {
        return response()->json($this->auth->attemptCredential($request->validated()));
    }

    public function recuperar(RecoveryRequest $request): JsonResponse
    {
        return $this->recovery(fn () => $this->store->startRecovery(
            $request->validated()['identifier'],
            $request->validated()['role']
        ));
    }

    public function confirmarRecuperacion(RecoveryConfirmRequest $request): JsonResponse
    {
        $data = $request->validated();

        return $this->recovery(fn () => $this->store->confirmRecovery(
            $data['identifier'],
            $data['role'],
            $data['code'],
            $data['password']
        ));
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        } else {
            $request->user()?->tokens()->delete();
        }

        Auth::forgetGuards();

        return response()->json(['message' => 'Sesión cerrada']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => EloquentUserAuthenticator::payload($request->user()),
        ]);
    }

    private function recovery(callable $action): JsonResponse
    {
        try {
            return response()->json($action());
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
