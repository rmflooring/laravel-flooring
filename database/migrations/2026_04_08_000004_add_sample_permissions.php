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
            'view samples',
            'create samples',
            'edit samples',
            'delete samples',
            'manage sample checkouts',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        $roleMap = [
            'admin'       => ['view samples', 'create samples', 'edit samples', 'delete samples', 'manage sample checkouts'],
            'coordinator' => ['view samples', 'create samples', 'edit samples', 'manage sample checkouts'],
            'sales'       => ['view samples', 'manage sample checkouts'],
            'reception'   => ['view samples', 'manage sample checkouts'],
            'estimator'   => ['view samples'],
        ];

        foreach ($roleMap as $roleName => $rolePermissions) {
            $role = Role::findByName($roleName);
            if ($role) {
                $role->givePermissionTo($rolePermissions);
            }
        }
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['view samples', 'create samples', 'edit samples', 'delete samples', 'manage sample checkouts'] as $name) {
            Permission::where('name', $name)->delete();
        }
    }
};
