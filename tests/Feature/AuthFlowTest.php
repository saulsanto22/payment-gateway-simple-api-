<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

describe('Auth Flow - Register & Login', function () {

    beforeEach(function () {
        // Seed role customer untuk test
        \Spatie\Permission\Models\Role::create(['name' => 'customer', 'guard_name' => 'web']);
    });

    it('user dapat register dengan data yang valid', function () {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'access_token',
                    'token_type',
                    'expires_in',
                ],
                'message',
            ]);

        // Cek user ada di database
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        // Cek user dapat role customer
        $user = User::where('email', 'john@example.com')->first();
        expect($user->hasRole('customer'))->toBeTrue();
    });

    it('register gagal jika email sudah terdaftar', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Another User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('register gagal jika password kurang dari 8 karakter', function () {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('user dapat login dengan kredensial yang benar', function () {
        // Buat user dulu
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                ],
                'message',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Login successfully',
            ]);
    });

    it('login gagal dengan password yang salah', function () {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    });

    it('user dapat mengakses endpoint yang dilindungi dengan token', function () {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    });

    it('akses ditolak tanpa token', function () {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    });

    it('user dapat logout', function () {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    });
});
