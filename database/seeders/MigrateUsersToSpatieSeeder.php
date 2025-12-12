<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class MigrateUsersToSpatieSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Migrate existing users dari old role column ke Spatie roles
     */
    public function run(): void
    {
        $this->command->info('Migrating existing users to Spatie roles...');

        $users = User::all();
        $migrated = 0;

        foreach ($users as $user) {
            // Skip jika user sudah punya Spatie role
            if ($user->roles->isNotEmpty()) {
                continue;
            }

            // Assign role berdasarkan old role column
            if ($user->role) {
                $user->assignRole($user->role->value);
                $migrated++;
            }
        }

        $this->command->info("Migrated {$migrated} users to Spatie roles!");

        // Display summary
        $this->command->newLine();
        $this->command->table(
            ['Role', 'User Count'],
            [
                ['Admin', User::role('admin')->count()],
                ['Merchant', User::role('merchant')->count()],
                ['Customer', User::role('customer')->count()],
            ]
        );
    }
}
