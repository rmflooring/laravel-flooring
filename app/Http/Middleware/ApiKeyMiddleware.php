<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = env('LEAD_API_KEY');

        if (! $expected) {
            return response()->json(['error' => 'API key not configured.'], 500);
        }

        $token = $request->bearerToken();

        if (! $token || ! hash_equals($expected, $token)) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
