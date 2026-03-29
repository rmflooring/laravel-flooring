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

        Permission::firstOrCreate(['name' => 'delete sales']);

        // Same roles that have 'create estimates'
        foreach (['admin', 'manager'] as $roleName) {
            $role = Role::findByName($roleName);
            if ($role) {
                $role->givePermissionTo('delete sales');
            }
        }
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::where('name', 'delete sales')->delete();
    }
};
