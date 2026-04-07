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
            'view bills',
            'create bills',
            'edit bills',
            'delete bills',
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
            $coordinator->givePermissionTo(['view bills', 'create bills', 'edit bills']);
        }
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['view bills', 'create bills', 'edit bills', 'delete bills'] as $name) {
            Permission::where('name', $name)->delete();
        }
    }
};
