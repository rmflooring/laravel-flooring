<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view opportunities',
            'create opportunities',
            'edit opportunities',
            'delete opportunities',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Roles that get all four permissions
        $fullAccess = ['Manager', 'sales', 'estimator', 'coordinator'];

        // Roles that get view-only
        $viewOnly = ['accounting', 'reception'];

        foreach ($fullAccess as $roleName) {
            $role = Role::findByName($roleName, 'web');
            $role->givePermissionTo($permissions);
        }

        foreach ($viewOnly as $roleName) {
            $role = Role::findByName($roleName, 'web');
            $role->givePermissionTo('view opportunities');
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['view opportunities', 'create opportunities', 'edit opportunities', 'delete opportunities'] as $name) {
            Permission::findByName($name, 'web')?->delete();
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
