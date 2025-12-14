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
     *
     * @OA\Post(
     *     path="/api/auth/register",
     *     tags={"Authentication"},
     *     summary="Register user baru",
     *     description="Registrasi user baru dengan email, name, dan password. User otomatis dapat role 'customer'",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation"},
     *
     *             @OA\Property(property="name", type="string", example="John Doe", description="Nama lengkap user"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Email user (must be unique)"),
     *             @OA\Property(property="password", type="string", format="password", example="password123", minLength=8, description="Password minimal 8 karakter"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123", description="Konfirmasi password (harus sama)")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="User berhasil register, langsung login otomatis",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Registered successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com")
     *                 ),
     *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOi..."),
     *                 @OA\Property(property="token_type", type="string", example="bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error (email sudah terdaftar, password kurang dari 8 karakter, dll)",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The email has already been taken.")
     *         )
     *     )
     * )
     */
    public function register(RegisterRequest $request)
    {
        $data = $this->authService->register($request->validated());

        return ApiResponse::success($data, 'Registered successfully', 201);
    }

    /**
     * Login user
     *
     * @OA\Post(
     *     path="/api/auth/login",
     *     tags={"Authentication"},
     *     summary="Login user",
     *     description="Login dengan email dan password, return JWT token untuk authentication",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="customer@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Login berhasil, return JWT token",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="customer@example.com")
     *                 ),
     *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOi..."),
     *                 @OA\Property(property="token_type", type="string", example="bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600, description="Token valid selama 1 jam")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Email atau password salah",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid credentials")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The email field is required.")
     *         )
     *     )
     * )
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
     *
     * @OA\Post(
     *     path="/api/auth/logout",
     *     tags={"Authentication"},
     *     summary="Logout user",
     *     description="Logout user dan blacklist JWT token. Token tidak bisa dipakai lagi setelah logout.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Logout berhasil",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logged out successfully"),
     *             @OA\Property(property="data", type="object", example={})
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Token tidak valid atau sudah expired",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
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
