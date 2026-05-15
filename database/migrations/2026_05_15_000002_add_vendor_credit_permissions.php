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
            'view vendor credits',
            'create vendor credits',
            'edit vendor credits',
            'delete vendor credits',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        $admin = Role::findByName('admin');
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }

        $coordinator = Role::findByName('coordinator');
        if ($coordinator) {
            $coordinator->givePermissionTo(['view vendor credits', 'create vendor credits', 'edit vendor credits']);
        }
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['view vendor credits', 'create vendor credits', 'edit vendor credits', 'delete vendor credits'] as $name) {
            Permission::where('name', $name)->delete();
        }
    }
};
