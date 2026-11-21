<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // rmserver2 reverse-proxies devfm.rmflooring.ca and terminates SSL there;
        // trust its forwarded headers so Laravel generates https:// URLs and secure cookies
        $middleware->trustProxies(at: ['192.168.1.150'], headers: Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO);

        // Restore Laravel <11 behaviour: convert empty strings to null and trim input
        $middleware->convertEmptyStringsToNull();
        $middleware->trimStrings(except: [
            'current_password',
            'password',
            'password_confirmation',
        ]);

        // Sync mobile guard auth into web guard on every request so mobile-authenticated
        // users can access desktop pages without re-logging in on the web guard
        $middleware->appendToGroup('web', \App\Http\Middleware\SyncMobileAuth::class);

        // ...and it must run BEFORE `auth`. Laravel's priority sort moves Authenticate
        // ahead of SubstituteBindings (and so ahead of this group-appended middleware),
        // which made `auth` on /dashboard see a mobile-only user as a guest → /login,
        // while `guest` on /login (unsorted, runs after the sync) saw them as logged
        // in → /dashboard: an endless redirect loop (iPhone, 2026-11-21).
        $middleware->prependToPriorityList(
            before: \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            prepend: \App\Http\Middleware\SyncMobileAuth::class,
        );

        $middleware->alias([
            'admin' => \App\Http\Middleware\Admin::class,
            'auth.mobile' => \App\Http\Middleware\AuthMobile::class,

            // Spatie Permission middleware aliases
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
			'admin_or_permission' => \App\Http\Middleware\AdminOrPermission::class,
            'api.key' => \App\Http\Middleware\ApiKeyMiddleware::class,

        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
