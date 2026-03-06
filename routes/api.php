<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Rotas publicas - autenticacao
Route::post('/login', [AuthController::class, 'login']);
Route::post('/create-account', [AuthController::class, 'createAccount']);

// Rotas publicas - produtos (leitura)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// Rotas protegidas por Sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Autenticacao
    Route::post('/logout', [AuthController::class, 'logout']);

    // Perfil do usuario
    Route::get('/user', [UserController::class, 'show']);
    Route::put('/user', [UserController::class, 'update']);

    // Carrinho
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{cartItem}', [CartController::class, 'update']);
    Route::delete('/cart/{cartItem}', [CartController::class, 'destroy']);

    // Produtos (criacao/edicao/remocao - admin)
    Route::post('/products', [ProductController::class, 'store']);
    Route::post('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    // Pedidos
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);

    // Pedidos - admin
    Route::get('/admin/orders', [OrderController::class, 'indexAll']);
    Route::patch('/admin/orders/{order}/status', [OrderController::class, 'updateStatus']);
});
