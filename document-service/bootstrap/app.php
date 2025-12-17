<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ✅ CRITICAL: Correlation ID Middleware (REQUIREMENT d)
        // Append to global middleware stack
        $middleware->append(\App\Http\Middleware\CorrelationIdMiddleware::class);

        // ✅ Register custom middleware aliases
        $middleware->alias([
            'auth.user-service' => \App\Http\Middleware\AuthenticateViaUserService::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
