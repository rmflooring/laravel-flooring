<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Admin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect('login'); // or wherever your login page is
        }

        // Adjust this line based on your role/permission setup
        if (auth()->user()->hasRole(['admin', 'Admin', 'administrator'])) { // If using Spatie Laravel-Permission
            return $next($request);
        }

        // Or if you have a simple 'role' column in users table:
        // if (auth()->user()->role === 'admin') {

        abort(403, 'Unauthorized - You do not have admin access.');
    }
}
