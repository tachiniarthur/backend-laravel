<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function login(array $credentials): ?array
    {
        $user = User::where('email', $credentials['email'])->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            $token = $user->createToken('api-token')->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
            ];
        }

        return null;
    }

    /**
     * Create a new user and return the created user and a token.
     *
     * @param array $data
     * @return array{user: User, token: string}|null
     */
    public function create(array $data): ?array
    {
        // Nao usar Hash::make() aqui pois o cast 'hashed' no Model User
        // ja faz o hash automaticamente ao atribuir o password.
        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        if (!$user) {
            return null;
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }
}
