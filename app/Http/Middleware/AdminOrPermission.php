<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOrPermission
{
    /**
     * Allow access if user is admin OR has ANY of the given permissions.
     * Usage: ->middleware('admin_or_permission:view customers')
     *        ->middleware('admin_or_permission:view reports,view sales report')
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->hasRole('admin')) {
            return $next($request);
        }

        if ($user->hasAnyPermission($permissions)) {
            return $next($request);
        }

        abort(403, 'User does not have the right permissions.');
    }
}
