<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Middleware de permisos granulares.
 *
 * Uso en rutas:
 *   ->middleware('permiso:animales,crear')
 *   ->middleware('permiso:salud,ver')
 *
 * También verifica que el usuario esté activo.
 */
class CheckPermiso
{
    public function handle(Request $request, Closure $next, string $modulo, string $accion = 'ver')
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->activo) {
            auth()->logout();
            return redirect()->route('login')
                ->with('error', 'Tu cuenta ha sido desactivada. Contacta al administrador.');
        }

        if (!$user->puede($modulo, $accion)) {
            if ($request->wantsJson() || $request->header('X-Inertia')) {
                return Inertia::render('Errors/Forbidden', [
                    'modulo' => $modulo,
                    'accion' => $accion,
                ])->toResponse($request)->setStatusCode(403);
            }
            abort(403, "No tienes permiso para $accion en $modulo.");
        }

        return $next($request);
    }
}
