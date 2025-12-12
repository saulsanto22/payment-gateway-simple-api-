<?php

namespace App\Http\Controllers\Api\Auth;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\Auth\AuthService;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register user baru
     */
    public function register(RegisterRequest $request)
    {
        $data = $this->authService->register($request->validated());

        return ApiResponse::success($data, 'Registered successfully', 201);
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request)
    {
        $data = $this->authService->login($request->validated());

        if (! $data) {
            return ApiResponse::error('Invalid credentials', 401);
        }

        return ApiResponse::success($data, 'Login successfully');
    }

    /**
     * Refresh JWT token
     *
     * Endpoint ini untuk refresh access token yang sudah expired
     * User kirim token lama, dapat token baru
     */
    public function refresh()
    {
        try {
            $data = $this->authService->refresh();

            return ApiResponse::success($data, 'Token refreshed successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Token refresh failed: '.$e->getMessage(), 401);
        }
    }

    /**
     * Logout user (blacklist token)
     *
     * Token yang di-logout akan di-blacklist
     * Tidak bisa dipakai lagi sampai expired
     */
    public function logout()
    {
        $data = $this->authService->logout();

        return ApiResponse::success($data, 'Logged out successfully');
    }

    /**
     * Get authenticated user info
     *
     * Endpoint untuk ambil data user yang sedang login
     */
    public function me()
    {
        $user = $this->authService->me();

        return ApiResponse::success($user, 'User retrieved successfully');
    }
}
