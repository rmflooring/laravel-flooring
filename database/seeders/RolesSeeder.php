<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles (use consistent lowercase to avoid casing issues)
        $admin      = Role::firstOrCreate(['name' => 'admin']);
        $sales      = Role::firstOrCreate(['name' => 'sales']);
        $estimator  = Role::firstOrCreate(['name' => 'estimator']);
        $accounting = Role::firstOrCreate(['name' => 'accounting']);
        $reception  = Role::firstOrCreate(['name' => 'reception']);

        // Admin gets everything
        $admin->syncPermissions(Permission::all());

        // Helper: build permissions by prefix
        $perm = fn (array $names) => Permission::whereIn('name', $names)->get();

        // Reception: can view dashboard + customers (and later opportunities when you add them)
        $reception->syncPermissions($perm([
            'view dashboard',
            'view customers',
            'create customers',
            'edit customers',
        ]));

        // Sales: customer + vendor + PM + products (adjust as you like)
        $sales->syncPermissions($perm([
            'view dashboard',

            'view customers', 'create customers', 'edit customers',

            'view vendors', 'create vendors', 'edit vendors',
            'view vendor reps', 'create vendor reps', 'edit vendor reps',

            'view project managers', 'create project managers', 'edit project managers',

            'view product types', 'create product types', 'edit product types',
            'view product lines', 'create product lines', 'edit product lines',

            'view labour types', 'create labour types', 'edit labour types',
            'view unit measures', 'create unit measures', 'edit unit measures',
        ]));

        // Estimator: mostly view reference data (you can expand later)
        $estimator->syncPermissions($perm([
            'view dashboard',
            'view customers',
            'view vendors',
            'view vendor reps',
            'view project managers',
            'view product types',
            'view product lines',
            'view labour types',
            'view unit measures',
        ]));

        // Accounting: mostly view + some edits (adjust later)
        $accounting->syncPermissions($perm([
            'view dashboard',
            'view customers',
            'view vendors',
            'view vendor reps',
            'view project managers',
        ]));
    }
}
