<?php

use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
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

// --- USER-FACING ROUTES ---
Route::middleware(['auth:api', 'throttle:api'])->group(function () {

    // Products for users
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);

    // Cart for users
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/', [CartController::class, 'add']);
        Route::delete('/{id}', [CartController::class, 'remove']);
    });

    // Orders for users
    Route::prefix('orders')->group(function () {
        Route::post('/checkout', [OrderController::class, 'checkout']);
        Route::get('/history', [OrderController::class, 'history']);
    });
});


// --- ADMIN ROUTES ---
Route::middleware(['auth:api', 'throttle:api', 'role:admin'])->prefix('admin')->group(function () {
    
    /**
     * Route resource untuk admin mengelola produk.
     * Ini akan secara otomatis membuat route untuk:
     * GET /admin/products -> AdminProductController@index
     * POST /admin/products -> AdminProductController@store
     * GET /admin/products/{product} -> AdminProductController@show
     * PUT/PATCH /admin/products/{product} -> AdminProductController@update
     * DELETE /admin/products/{product} -> AdminProductController@destroy
     */
    Route::apiResource('products', AdminProductController::class);

    // Di sini Anda bisa menambahkan route admin lainnya di masa depan
    // Contoh: Route::get('/orders', [AdminOrderController::class, 'index']);
});


// Webhook dari Midtrans
Route::post('/midtrans/webhook', [OrderController::class, 'webhook'])
    ->middleware('throttle:webhook');
