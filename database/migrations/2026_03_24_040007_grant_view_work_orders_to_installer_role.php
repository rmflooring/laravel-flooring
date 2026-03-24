<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'view work orders', 'guard_name' => 'web']);

        $role = Role::where('name', 'installer')->where('guard_name', 'web')->first();

        if ($role) {
            $role->givePermissionTo($permission);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        $role       = Role::where('name', 'installer')->where('guard_name', 'web')->first();
        $permission = Permission::where('name', 'view work orders')->where('guard_name', 'web')->first();

        if ($role && $permission) {
            $role->revokePermissionTo($permission);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
