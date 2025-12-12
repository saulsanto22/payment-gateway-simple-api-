<?php

namespace App\Services\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    /**
     * Register user baru dan generate JWT token
     */
    public function register($data)
    {
        try {
            DB::beginTransaction();

            // Create user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'] ?? UserRole::CUSTOMER, // Default: customer
            ]);

            // Generate JWT token (bukan Sanctum!)
            $token = JWTAuth::fromUser($user);

            DB::commit();

            return [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60, // TTL dalam detik
            ];
        } catch (\Exception $e) {
            DB::RollBack();
            throw $e;
        }
    }

    /**
     * Login user dan generate JWT token
     */
    public function login($data)
    {
        // Attempt login dengan credentials
        $credentials = [
            'email' => $data['email'],
            'password' => $data['password'],
        ];

        // JWTAuth::attempt() akan:
        // 1. Cek credentials (email + password)
        // 2. Jika valid, generate JWT token
        // 3. Return token
        if (! $token = JWTAuth::attempt($credentials)) {
            return null; // Login gagal
        }

        return [
            'user' => auth('api')->user(),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ];
    }

    /**
     * Refresh JWT token
     *
     * Kenapa perlu refresh?
     * - Access token punya expiry (misal 60 menit)
     * - Daripada user login lagi, kita refresh token aja
     */
    public function refresh()
    {
        // Pakai JWTAuth facade secara eksplisit
        $newToken = JWTAuth::refresh(JWTAuth::getToken());

        return [
            'access_token' => $newToken,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60, // TTL dari config
        ];
    }

    /**
     * Logout user (blacklist token)
     *
     * Kenapa perlu blacklist?
     * - JWT tidak bisa di-delete (stateless)
     * - Jadi kita blacklist token sampai expired
     */
    public function logout()
    {
        // Invalidate (blacklist) current token
        JWTAuth::invalidate(JWTAuth::getToken());

        return [
            'message' => 'Successfully logged out',
        ];
    }

    /**
     * Get authenticated user
     */
    public function me()
    {
        return JWTAuth::user();
    }
}
