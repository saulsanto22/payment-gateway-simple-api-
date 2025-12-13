<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    /**
     * Register user baru sebagai 'customer' dan generate JWT token.
     */
    public function register($data)
    {
        try {
            DB::beginTransaction();

            // 1. Buat user tanpa menentukan peran secara langsung.
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            // 2. Berikan peran 'customer' kepada user baru menggunakan Spatie.
            // Ini adalah cara yang direkomendasikan.
            $user->assignRole('customer');

            DB::commit();

            // 3. Generate JWT token setelah transaksi berhasil.
            $token = JWTAuth::fromUser($user);

            return [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60, // TTL dalam detik
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Register error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Login user dan generate JWT token.
     */
    public function login($data)
    {
        $credentials = [
            'email' => $data['email'],
            'password' => $data['password'],
        ];

        if (!$token = JWTAuth::attempt($credentials)) {
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
     * Refresh JWT token.
     */
    public function refresh()
    {
        $newToken = JWTAuth::refresh(JWTAuth::getToken());

        return [
            'access_token' => $newToken,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ];
    }

    /**
     * Logout user (blacklist token).
     */
    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return [
            'message' => 'Successfully logged out',
        ];
    }

    /**
     * Get authenticated user.
     */
    public function me()
    {
        return JWTAuth::user();
    }
}
