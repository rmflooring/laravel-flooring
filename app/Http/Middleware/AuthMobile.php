<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthMobile
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('mobile')->check()) {
            return redirect()->route('mobile.login');
        }

        return $next($request);
    }
}
