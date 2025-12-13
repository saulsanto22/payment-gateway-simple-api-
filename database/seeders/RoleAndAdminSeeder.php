<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RoleAndAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()['cache']->forget('spatie.permission.cache');

        // 1. Buat Roles
        $roleAdmin = Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'customer']);
        Role::firstOrCreate(['name' => 'merchant']); // Sesuai dengan Enum Anda sebelumnya

        // 2. Buat User Admin jika belum ada
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'], // Cari berdasarkan email
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'), // Ganti dengan password yang aman!
            ]
        );

        // 3. Berikan peran 'admin' ke user tersebut
        if ($adminUser->wasRecentlyCreated) {
            $adminUser->assignRole($roleAdmin);
        }

        $this->command->info('Roles and Admin User have been seeded!');
        $this->command->info('Admin Email: admin@example.com');
        $this->command->info('Admin Password: password');
    }
}
