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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('portal') || $request->is('portal/*')) {
                return route('portal.login');
            }
            if ($request->is('physician') || $request->is('physician/*')) {
                return route('physician.login');
            }

            return route('admin.login');
        });
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));

        $middleware->append([
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\CompressResponse::class,
        ]);

        // Legacy unified_portal PHP forms POST through this route without Laravel's @csrf;
        // the workspace is already behind auth:admin.
        $middleware->validateCsrfTokens(except: [
            'admin/workspace',
            'admin/workspace/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
