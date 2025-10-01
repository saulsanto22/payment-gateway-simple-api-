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

    public function register(RegisterRequest $request)
    {
        $data = $this->authService->register($request->validated());

        return ApiResponse::success($data, 'Registered successfully', 201);
    }

    public function login(LoginRequest $request)
    {
        $data = $this->authService->login($request->validated());

        if (! $data) {
            return ApiResponse::error($data['error']);
        }

        return ApiResponse::success($data, 'Login successfully');
    }
}
