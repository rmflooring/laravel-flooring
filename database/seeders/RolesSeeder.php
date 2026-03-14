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

        // Normalize any mixed-case role names to lowercase
        Role::all()->each(function ($role) {
            $lower = strtolower($role->name);
            if ($role->name !== $lower) {
                $role->name = $lower;
                $role->save();
            }
        });

        // Create roles (use consistent lowercase to avoid casing issues)
        $admin       = Role::firstOrCreate(['name' => 'admin']);
        $sales       = Role::firstOrCreate(['name' => 'sales']);
        $estimator   = Role::firstOrCreate(['name' => 'estimator']);
        $accounting  = Role::firstOrCreate(['name' => 'accounting']);
        $reception   = Role::firstOrCreate(['name' => 'reception']);
        $coordinator = Role::firstOrCreate(['name' => 'coordinator']);

        // Admin gets everything
        $admin->syncPermissions(Permission::all());

        // Helper: build permissions by name list
        $perm = fn (array $names) => Permission::whereIn('name', $names)->get();

        // Reception: can view dashboard + customers + RFMs + view POs + view WOs
        $reception->syncPermissions($perm([
            'view dashboard',
            'view customers',
            'create customers',
            'edit customers',

            'view rfms',

            'view purchase orders',

            'view work orders',
        ]));

        // Sales: customer + vendor + PM + products + RFMs + POs + sale status
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

            'view rfms', 'create rfms',

            'view purchase orders', 'create purchase orders', 'edit purchase orders',

            'view sale status',

            'view work orders', 'create work orders', 'edit work orders',
        ]));

        // Estimator: mostly view reference data + full RFM access + view POs + sale status + WOs
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

            'view rfms', 'create rfms', 'edit rfms',

            'view purchase orders',

            'view sale status',

            'view work orders', 'create work orders', 'edit work orders',
        ]));

        // Coordinator: ordering + fulfilment tracking + WOs
        $coordinator->syncPermissions($perm([
            'view dashboard',
            'view customers',

            'view purchase orders', 'create purchase orders', 'edit purchase orders',

            'view sale status',

            'view work orders', 'create work orders', 'edit work orders',
        ]));

        // Accounting: mostly view + view POs
        $accounting->syncPermissions($perm([
            'view dashboard',
            'view customers',
            'view vendors',
            'view vendor reps',
            'view project managers',

            'view rfms',

            'view purchase orders',
        ]));
    }
}
