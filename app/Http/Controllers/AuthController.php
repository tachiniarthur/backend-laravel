<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $result = $this->authService->login($credentials);

        if (!$result) {
            return response()->json(['message' => 'Credenciais invalidas'], 401);
        }

        $result['user']->makeHidden(['password']);

        return response()->json([
            'user' => $result['user'],
            'token' => $result['token'],
        ]);
    }

    public function createAccount(Request $request)
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

        $result['user']->makeHidden(['password']);

        return response()->json([
            'message' => 'Conta criada com sucesso',
            'user' => $result['user'],
            'token' => $result['token'],
        ], 201);
    }
}
