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
        // Supaya Laravel mengenali HTTPS/host asli saat diakses lewat ngrok/reverse proxy.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'access' => \App\Http\Middleware\CheckAccess::class,
        ]);

        $middleware->appendToGroup('web', \App\Http\Middleware\CheckAccess::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
