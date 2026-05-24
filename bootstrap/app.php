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
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\ForzarCambioPassword::class,
        ]);

        // Aliases de middleware
        $middleware->alias([
            'role'                => \App\Http\Middleware\CheckRole::class,
            'plan'                => \App\Http\Middleware\CheckPlan::class,
            'permiso'             => \App\Http\Middleware\CheckPermiso::class,
            'forzar.password'     => \App\Http\Middleware\ForzarCambioPassword::class,
        ]);

        // Excluir webhook de Stripe de verificación CSRF
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();