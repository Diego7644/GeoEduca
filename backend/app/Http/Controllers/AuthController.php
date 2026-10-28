<?php

namespace App\Http\Controllers;

use App\Auth\EloquentUserAuthenticator;
use App\Http\Requests\Auth\AccesoRequest;
use App\Http\Requests\Auth\DetectAccesoRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RecoveryConfirmRequest;
use App\Http\Requests\Auth\RecoveryRequest;
use App\Models\User;
use App\Services\Auth\AccountRecoveryService;
use App\Services\Auth\AuthService;
use App\Services\Auth\GateAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $auth,
        private AccountRecoveryService $recovery,
        private GateAccessService $gate
    ) {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        return response()->json($this->auth->attempt($request->validated()));
    }

    public function detectar(DetectAccesoRequest $request): JsonResponse
    {
        return response()->json($this->gate->readerStatus($this->staff($request)));
    }

    public function acceso(AccesoRequest $request): JsonResponse
    {
        return $this->jsonOrDomainError(fn () => $this->gate->identify(
            $this->staff($request),
            $request->validated()['credential']
        ));
    }

    public function recuperar(RecoveryRequest $request): JsonResponse
    {
        return response()->json($this->recovery->request($request->validated()));
    }

    public function confirmarRecuperacion(RecoveryConfirmRequest $request): JsonResponse
    {
        return $this->jsonOrDomainError(fn () => $this->recovery->confirm($request->validated()));
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

    private function staff(Request $request): User
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(response()->json(['message' => 'No autenticado.'], 401));
        }

        return $user;
    }

    private function jsonOrDomainError(callable $action): JsonResponse
    {
        try {
            return response()->json($action());
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
