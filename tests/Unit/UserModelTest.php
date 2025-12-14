<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

describe('User Model', function () {

    it('dapat membuat user dengan atribut yang benar', function () {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);

        expect($user)->toBeInstanceOf(User::class)
            ->and($user->name)->toBe('John Doe')
            ->and($user->email)->toBe('john@example.com')
            ->and(Hash::check('password123', $user->password))->toBeTrue();
    });

    it('email harus unique', function () {
        User::factory()->create(['email' => 'test@example.com']);

        // Coba buat user dengan email yang sama
        expect(fn () => User::create([
            'name' => 'Another User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('password di-hash otomatis', function () {
        $user = User::factory()->create(['password' => 'plaintext']);

        // Password tidak boleh plaintext
        expect($user->password)->not->toBe('plaintext')
            ->and(strlen($user->password))->toBeGreaterThan(50);
    });

    it('dapat assign role customer', function () {
        // Buat role customer dulu
        \Spatie\Permission\Models\Role::create(['name' => 'customer', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole('customer');

        expect($user->hasRole('customer'))->toBeTrue();
    });
});
