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

        Permission::firstOrCreate(['name' => 'delete rfms']);

        // Same roles that have 'edit rfms'
        foreach (['admin', 'manager', 'estimator'] as $roleName) {
            $role = Role::findByName($roleName);
            if ($role) {
                $role->givePermissionTo('delete rfms');
            }
        }
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::where('name', 'delete rfms')->delete();
    }
};
