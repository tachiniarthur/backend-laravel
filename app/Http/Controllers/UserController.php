<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|min:3|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
        ]);

        $user = $request->user();
        $user->update($data);

        return response()->json($user);
    }
}
