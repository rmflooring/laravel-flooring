<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOrPermission
{
    /**
     * Allow access if user is admin OR has the given permission.
     * Usage: ->middleware('admin_or_permission:view customers')
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        // If your existing admin middleware works, this should match your app’s admin flag/role
        if (method_exists($user, 'is_admin') && $user->is_admin) {
            return $next($request);
        }

        // If you use Spatie roles:
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return $next($request);
        }

        // Spatie permission check
        if (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo($permission)) {
            return $next($request);
        }

        // Laravel native permission check (in case you use policies/abilities)
        if (method_exists($user, 'can') && $user->can($permission)) {
            return $next($request);
        }

        abort(403, 'User does not have the right permissions.');
    }
}
