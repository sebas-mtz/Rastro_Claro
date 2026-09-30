<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle($request, Closure $next, $role)
    {
        // Verificar que el usuario esté autenticado y tenga el rol requerido
        if (!Auth::check() || Auth::user()->role !== $role) {
            abort(403, 'Acceso denegado');  // denegar acceso si no tiene el rol
        }
        return $next($request);
    }
}
