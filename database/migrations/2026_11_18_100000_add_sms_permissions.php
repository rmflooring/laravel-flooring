<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The SMS inbox used to be open to every signed-in user. Now:
 *   view sms inbox    — the desktop SMS inbox (and SMS panels on opportunity/lead pages)
 *   use sms on mobile — the mobile SMS pages (/m/sms)
 * Both start with admin and Manager; tick them for other roles under Roles.
 */
return new class extends Migration
{
    private array $permissions = ['view sms inbox', 'use sms on mobile'];

    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (['admin', 'Manager'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()
                ?->givePermissionTo($this->permissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::whereIn('name', $this->permissions)->delete();
    }
};
