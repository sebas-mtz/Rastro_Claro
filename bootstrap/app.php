<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Middleware\CheckRole;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
    apiPrefix: 'api',
)
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            // Permisos por módulo. Se aplica a todo el grupo web en vez de
            // ruta por ruta para que una ruta nueva quede protegida sola;
            // deduce el módulo del nombre de la ruta y deja pasar lo que no
            // pertenece a ninguno (panel, perfil, cierre de sesión).
            \App\Http\Middleware\VerificarPermisoModulo::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            // Reserva una ruta al superadministrador. Responde 403.
            'super_admin' => \App\Http\Middleware\CheckSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Una respuesta 403 debe verse como una pantalla del sistema, no como
        // la página de error en blanco de Laravel. Se renderiza con Inertia
        // para conservar el diseño y explicar qué pasó.
        $exceptions->respond(function (Response $response, \Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $response;
            }

            if (! in_array($response->getStatusCode(), [403, 404, 419, 500, 503], true)) {
                return $response;
            }

            // Durante las pruebas se deja pasar el error de servidor sin
            // maquillar. Convertirlo en una página bonita hacía que un fallo
            // real se viera como "no pasó nada": la petición devolvía 500, la
            // sesión no traía errores y las aserciones habituales pasaban.
            if (app()->runningUnitTests() && $response->getStatusCode() >= 500) {
                return $response;
            }

            return Inertia::render('Error', [
                'status' => $response->getStatusCode(),
                // El mensaje del abort() explica el motivo; si viene vacío,
                // la página usa un texto propio según el código.
                'mensaje' => $e->getMessage() ?: null,
            ])
                ->toResponse($request)
                ->setStatusCode($response->getStatusCode());
        });
    })->create();
