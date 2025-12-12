<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

// Auth routes dengan rate limiting berbeda per endpoint
Route::prefix('auth')->group(function () {
    // Public routes (tidak perlu auth)
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:register');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:login');

    // Protected routes (perlu JWT token)
    Route::middleware(['auth:api', 'throttle:api'])->group(function () {
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// Protected routes dengan JWT authentication (ganti auth:sanctum ke auth:api)
Route::middleware(['auth:api', 'throttle:api'])->group(function () {
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
    });

    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/', [CartController::class, 'add']);
        Route::delete('/{id}', [CartController::class, 'remove']);
    });

    Route::prefix('orders')->group(function () {
        Route::post('/checkout', [OrderController::class, 'checkout']);
        Route::get('/history', [OrderController::class, 'history']);
    });
});

// Webhook dengan rate limiting lebih longgar (100 req/min)
Route::post('/midtrans/webhook', [OrderController::class, 'webhook'])
    ->middleware('throttle:webhook');
