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

// Protected routes dengan JWT authentication & Spatie Permissions
Route::middleware(['auth:api', 'throttle:api'])->group(function () {

    // Products - Granular permissions
    Route::prefix('products')->group(function () {
        // Semua authenticated users (dengan permission) bisa view products
        Route::get('/', [ProductController::class, 'index'])
            ->middleware('permission:view-products');

        // Admin & Merchant only (create, update, delete)
        Route::post('/', [ProductController::class, 'store'])
            ->middleware('permission:create-product');

        Route::put('/{id}', [ProductController::class, 'update'])
            ->middleware('permission:edit-product');

        Route::delete('/{id}', [ProductController::class, 'destroy'])
            ->middleware('permission:delete-product');
    });

    // Cart - Semua authenticated users
    Route::prefix('cart')->group(function () {
        // Asumsi: Semua user login boleh akses cart mereka sendiri
        Route::get('/', [CartController::class, 'index']);
        Route::post('/', [CartController::class, 'add']);
        Route::delete('/{id}', [CartController::class, 'remove']);
    });

    // Orders
    Route::prefix('orders')->group(function () {
        // Checkout & History: Semua user login (bisa dibatasi permission view-own-orders jika mau ketat)
        Route::post('/checkout', [OrderController::class, 'checkout'])
            ->middleware('permission:view-own-orders');

        Route::get('/history', [OrderController::class, 'history'])
            ->middleware('permission:view-own-orders');

        // Admin & Merchant bisa lihat semua order (upcoming implementation)
        // Route::get('/all', ...) ->middleware('permission:manage-orders');
    });

    // Admin Routes
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        // Placeholder untuk future admin endpoints
    });
});

// Webhook dengan rate limiting lebih longgar (100 req/min)
Route::post('/midtrans/webhook', [OrderController::class, 'webhook'])
    ->middleware('throttle:webhook');
