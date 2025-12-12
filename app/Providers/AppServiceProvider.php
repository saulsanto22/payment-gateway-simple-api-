<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Konfigurasi Rate Limiting dengan custom responses

        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(60)->by($request->user()->id)->response(function () {
                    return \App\Helpers\ApiResponse::rateLimit(
                        'Terlalu banyak request. Silakan tunggu sebentar.'
                    );
                })
                : Limit::perMinute(60)->by($request->ip())->response(function () {
                    return \App\Helpers\ApiResponse::rateLimit(
                        'Terlalu banyak request. Silakan tunggu sebentar.'
                    );
                });
        });

        RateLimiter::for('register', function (Request $request) {
            // Limit register: 10 percobaan per menit
            // Lebih longgar dari login karena bisa gagal validation
            return Limit::perMinute(10)->by($request->ip())->response(function () {
                return \App\Helpers\ApiResponse::rateLimit(
                    'Terlalu banyak percobaan registrasi. Silakan tunggu 1 menit.'
                );
            });
        });

        RateLimiter::for('login', function (Request $request) {
            // Limit login: 5 percobaan per menit berdasarkan IP
            // Mencegah brute force attack
            return Limit::perMinute(5)->by($request->ip())->response(function () {
                return \App\Helpers\ApiResponse::rateLimit(
                    'Terlalu banyak percobaan login. Silakan tunggu 1 menit untuk keamanan akun Anda.'
                );
            });
        });

        RateLimiter::for('webhook', function (Request $request) {
            // Limit webhook: 100 request per menit berdasarkan IP
            // Midtrans bisa kirim banyak notifikasi, jadi lebih longgar
            return Limit::perMinute(100)->by($request->ip())->response(function () {
                return \App\Helpers\ApiResponse::rateLimit(
                    'Webhook rate limit exceeded.'
                );
            });
        });
    }
}
