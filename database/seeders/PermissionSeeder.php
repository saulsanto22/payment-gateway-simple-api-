<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions untuk e-commerce
        $permissions = [
            // Product permissions
            'view-products',
            'create-product',
            'edit-product',
            'delete-product',

            // Order permissions
            'manage-orders',
            'view-own-orders',

            // Report permissions
            'view-reports',

            // User management
            'manage-users',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        $this->command->info('Permissions created successfully!');
        $this->command->table(
            ['Permission Name'],
            collect($permissions)->map(fn ($p) => [$p])->toArray()
        );
    }
}
