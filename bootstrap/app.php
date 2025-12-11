<?php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Konfigurasi Rate Limiting
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(60)->by($request->user()->id)
                : Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            // Limit login: 5 percobaan per menit berdasarkan IP
            // Mencegah brute force attack
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('webhook', function (Request $request) {
            // Limit webhook: 100 request per menit berdasarkan IP
            // Midtrans bisa kirim banyak notifikasi, jadi lebih longgar
            return Limit::perMinute(100)->by($request->ip());
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
