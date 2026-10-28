<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private AuthService $auth)
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        // validated() ya pasó las reglas. El mock (o la BD) solo autentica.
        return response()->json($this->auth->attempt($request->validated()));
    }

    public function logout(): JsonResponse
    {
        return response()->json(['message' => 'Sesión cerrada']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }
}
