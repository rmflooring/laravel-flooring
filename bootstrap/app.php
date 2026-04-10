<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Sync mobile guard auth into web guard on every request so mobile-authenticated
        // users can access desktop pages without re-logging in on the web guard
        $middleware->appendToGroup('web', \App\Http\Middleware\SyncMobileAuth::class);

        $middleware->alias([
            'admin' => \App\Http\Middleware\Admin::class,
            'auth.mobile' => \App\Http\Middleware\AuthMobile::class,

            // Spatie Permission middleware aliases
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
			'admin_or_permission' => \App\Http\Middleware\AdminOrPermission::class,

        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
