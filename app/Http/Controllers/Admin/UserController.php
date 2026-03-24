<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Installer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->paginate(10);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles      = Role::all();
        $installers = Installer::orderBy('company_name')->get(['id', 'company_name', 'user_id']);
        return view('admin.users.create', compact('roles', 'installers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|string|email|max:255|unique:users',
            'password'     => 'required|string|min:8|confirmed',
            'roles'        => 'array',
            'user_type'    => 'nullable|in:staff,installer',
            'installer_id' => 'nullable|exists:installers,id',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if ($request->input('user_type') === 'installer' && $request->filled('installer_id')) {
            $user->syncRoles(['installer']);
            Installer::where('id', $request->installer_id)->update(['user_id' => $user->id]);
        } elseif ($request->has('roles')) {
            $user->syncRoles($request->roles);
        }

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $roles           = Role::all();
        $userRoles       = $user->roles->pluck('name')->toArray();
        $installers      = Installer::orderBy('company_name')->get(['id', 'company_name', 'user_id']);
        $linkedInstaller = Installer::where('user_id', $user->id)->first();
        return view('admin.users.edit', compact('user', 'roles', 'userRoles', 'installers', 'linkedInstaller'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password'     => 'nullable|string|min:8|confirmed',
            'roles'        => 'array',
            'user_type'    => 'nullable|in:staff,installer',
            'installer_id' => 'nullable|exists:installers,id',
        ]);

        $user->update([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->filled('password') ? Hash::make($request->password) : $user->password,
        ]);

        if ($request->input('user_type') === 'installer' && $request->filled('installer_id')) {
            $user->syncRoles(['installer']);
            // Detach any installer that previously pointed to this user
            Installer::where('user_id', $user->id)
                ->where('id', '<>', $request->installer_id)
                ->update(['user_id' => null]);
            Installer::where('id', $request->installer_id)->update(['user_id' => $user->id]);
        } else {
            $user->syncRoles($request->roles ?? []);
            // If switching away from installer type, clear the link
            if ($request->input('user_type') === 'staff') {
                Installer::where('user_id', $user->id)->update(['user_id' => null]);
            }
        }

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();
        return back()->with('success', 'User deleted successfully.');
    }
}
