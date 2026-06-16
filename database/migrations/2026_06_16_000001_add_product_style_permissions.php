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
            'view product styles',
            'create product styles',
            'edit product styles',
            'delete product styles',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        $admin = Role::findByName('admin');
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['view product styles', 'create product styles', 'edit product styles', 'delete product styles'] as $name) {
            Permission::where('name', $name)->delete();
        }
    }
};
