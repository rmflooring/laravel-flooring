<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
{
    $roles = Role::with('permissions')->paginate(10);
    return view('admin.roles.index', compact('roles'));
}

    public function create()
    {
        return view('admin.roles.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
        ]);

        Role::create(['name' => $request->name]);

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        return view('admin.roles.edit', compact('role'));
    }

    public function update(Request $request, Role $role)
    {
    $request->validate([
        'name' => 'required|unique:roles,name,' . $role->id,
        'permissions' => 'array',
    ]);

    $role->update(['name' => $request->name]);

    // Sync permissions
    $role->syncPermissions($request->permissions ?? []);

    return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully.');
    }
    public function destroy(Role $role)
    {
        // Prevent deleting the default "Admin" role
        if ($role->name === 'Admin') {
            return back()->with('error', 'Cannot delete the main Admin role.');
        }

        // Optional: Check if role is assigned to users
        if ($role->users()->count() > 0) {
            return back()->with('error', 'Cannot delete role assigned to users. Remove from users first.');
        }

        $role->delete();

        return back()->with('success', 'Role deleted successfully.');
    }
}
