<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Clear cached permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [

            // ===== Core / Admin =====
            'view dashboard',
            'edit settings',
            'manage users',
            'manage roles',

            // ===== Customers =====
            'view customers',
            'create customers',
            'edit customers',
            'delete customers',

            // ===== Vendors =====
            'view vendors',
            'create vendors',
            'edit vendors',
            'delete vendors',

            // ===== Vendor Reps =====
            'view vendor reps',
            'create vendor reps',
            'edit vendor reps',
            'delete vendor reps',

            // ===== Project Managers =====
            'view project managers',
            'create project managers',
            'edit project managers',
            'delete project managers',

            // ===== Product Types =====
            'view product types',
            'create product types',
            'edit product types',
            'delete product types',

            // ===== Product Lines =====
            'view product lines',
            'create product lines',
            'edit product lines',
            'delete product lines',

            // ===== Labour Types =====
            'view labour types',
            'create labour types',
            'edit labour types',
            'delete labour types',

            // ===== Unit Measures =====
            'view unit measures',
            'create unit measures',
            'edit unit measures',
            'delete unit measures',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
