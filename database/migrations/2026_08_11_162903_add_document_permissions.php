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
            'view documents',
            'create documents',
            'edit documents',
            'delete documents',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        // Grant to every existing role so access is unchanged immediately after
        // this migration runs — today every logged-in user can already view,
        // create, edit, and delete documents with no permission gate at all.
        // Restrict per-role afterward via /admin/roles/{role}/edit.
        foreach (Role::all() as $role) {
            $role->givePermissionTo($permissions);
        }
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['view documents', 'create documents', 'edit documents', 'delete documents'] as $name) {
            Permission::where('name', $name)->delete();
        }
    }
};
