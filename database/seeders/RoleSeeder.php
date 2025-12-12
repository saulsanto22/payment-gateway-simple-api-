<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $admin = Role::create(['name' => 'admin']);
        $merchant = Role::create(['name' => 'merchant']);
        $customer = Role::create(['name' => 'customer']);

        // Admin: All permissions
        $admin->givePermissionTo(Permission::all());

        // Merchant: Product management + own orders
        $merchant->givePermissionTo([
            'view-products',
            'create-product',
            'edit-product',
            'delete-product',
            'manage-orders',
            'view-own-orders',
        ]);

        // Customer: View products + own orders only
        $customer->givePermissionTo([
            'view-products',
            'view-own-orders',
        ]);

        $this->command->info('Roles created and permissions assigned!');
        $this->command->newLine();

        // Display role-permission mapping
        $this->command->info('Role-Permission Mapping:');
        $this->command->table(
            ['Role', 'Permissions'],
            [
                ['Admin', $admin->permissions->pluck('name')->implode(', ')],
                ['Merchant', $merchant->permissions->pluck('name')->implode(', ')],
                ['Customer', $customer->permissions->pluck('name')->implode(', ')],
            ]
        );
    }
}
