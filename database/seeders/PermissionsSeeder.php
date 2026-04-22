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

            // ===== Labour Items =====
            'view labour items',
            'create labour items',
            'edit labour items',
            'delete labour items',

            // ===== Unit Measures =====
            'view unit measures',
            'create unit measures',
            'edit unit measures',
            'delete unit measures',

            // ===== Estimates =====
            'view estimates',
            'create estimates',
            'edit estimates',
            'delete sales',

            // ===== RFMs =====
            'view rfms',
            'create rfms',
            'edit rfms',
            'delete rfms',

            // ===== Purchase Orders =====
            'view purchase orders',
            'create purchase orders',
            'edit purchase orders',
            'delete purchase orders',

            // ===== Sale Status =====
            'view sale status',

            // ===== Work Orders =====
            'view work orders',
            'create work orders',
            'edit work orders',
            'delete work orders',

            // ===== Pick Tickets =====
            'view pick tickets',

            // ===== RFC — Return From Customer =====
            'view rfcs',
            'create rfcs',
            'edit rfcs',

            // ===== RTV — Return to Vendor =====
            'view rtvs',
            'create rtvs',
            'edit rtvs',

            // ===== Installers =====
            'view installers',
            'create installers',
            'edit installers',
            'delete installers',

            // ===== Invoices =====
            'view invoices',
            'create invoices',
            'edit invoices',
            'delete invoices',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
