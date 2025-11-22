<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function index()
    {
        return response()->json($this->userService->list());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|min:3',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $user = $this->userService->create($data);

        return response()->json($user, 201);
    }

    public function show(int $id)
    {
        $user = $this->userService->find($id);

        if (! $user) {
            return response()->json(['message' => 'Usuário não encontrado'], 404);
        }

        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => 'sometimes|string|min:3',
            'email'    => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:6',
        ]);

        $user = $this->userService->update($user, $data);

        return response()->json($user);
    }

    public function destroy(User $user)
    {
        $this->userService->delete($user);
        return response()->json(null, 204);
    }
}
