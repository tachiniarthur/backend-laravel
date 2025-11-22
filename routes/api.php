<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::get('/ping', function () {
    return response()->json(['message' => 'API Laravel rodandos.'], 200);
});

Route::apiResource('users', UserController::class);
