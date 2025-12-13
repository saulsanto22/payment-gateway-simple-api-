<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Jalankan seeder untuk role dan admin terlebih dahulu.
            RoleAndAdminSeeder::class,

            // Kemudian jalankan seeder lainnya.
            ProductSeeder::class,
        ]);
    }
}
