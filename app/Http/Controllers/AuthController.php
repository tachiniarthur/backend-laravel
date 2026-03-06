<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $result = $this->authService->login($credentials);

        if (!$result) {
            return response()->json(['message' => 'Credenciais invalidas'], 401);
        }

        return response()->json([
            'user' => $result['user'],
            'token' => $result['token'],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout realizado.']);
    }

    public function createAccount(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users,username',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $result = $this->authService->create($data);

        if (!$result) {
            return response()->json(['message' => 'Erro ao criar conta'], 500);
        }

        return response()->json([
            'message' => 'Conta criada com sucesso',
            'user' => $result['user'],
            'token' => $result['token'],
        ], 201);
    }
}
