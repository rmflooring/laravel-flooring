<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SyncMobileAuth
{
    /**
     * If the user is authenticated on the mobile guard but not the web guard,
     * set them on the web guard for this request so that all standard auth
     * checks (pages middleware, @can, auth()->user(), etc.) work transparently.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('web')->check() && Auth::guard('mobile')->check()) {
            Auth::setUser(Auth::guard('mobile')->user());
        }

        return $next($request);
    }
}
