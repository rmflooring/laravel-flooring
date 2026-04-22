<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view labour items',
            'create labour items',
            'edit labour items',
            'delete labour items',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        $admin = Role::findByName('admin');
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }

        $sales = Role::findByName('sales');
        if ($sales) {
            $sales->givePermissionTo(['view labour items', 'create labour items', 'edit labour items']);
        }

        $estimator = Role::findByName('estimator');
        if ($estimator) {
            $estimator->givePermissionTo(['view labour items']);
        }
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['view labour items', 'create labour items', 'edit labour items', 'delete labour items'] as $name) {
            Permission::where('name', $name)->delete();
        }
    }
};
