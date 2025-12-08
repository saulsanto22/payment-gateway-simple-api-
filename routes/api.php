<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

});
Route::middleware('auth:sanctum')->group(function () {
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
Route::post('/midtrans/webhook', [OrderController::class, 'webhook']);
