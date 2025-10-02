<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function register($data)
    {

        try {

            DB::beginTransaction();
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $token = $user->createToken('api_token')->plainTextToken;
            DB::commit();

            return [
                'user' => $user,
                'token' => $token,
            ];

        } catch (\Exception $e) {
            DB::RollBack();

            return $e;
        }

    }

    public function login($data)
    {
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return null;
        }

        $token = $user->createToken('api_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
