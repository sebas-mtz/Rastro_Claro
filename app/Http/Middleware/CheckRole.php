<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Middleware de rol.
 * Uso: ->middleware('role:administrador')
 *       ->middleware('role:administrador,encargado')
 */
class CheckRole
{
    public function handle($request, Closure $next, string ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if (!$user->activo) {
            auth()->logout();
            return redirect()->route('login')
                ->with('error', 'Tu cuenta ha sido desactivada.');
        }

        // Compatibilidad: 'admin' → 'administrador', 'user' → 'trabajador'
        $rolesNormalized = array_map(fn($r) => match ($r) {
            'admin' => 'administrador',
            'user'  => 'trabajador',
            default => $r,
        }, $roles);

        if (!in_array($user->role, $rolesNormalized)) {
            abort(403, 'Acceso denegado: rol insuficiente.');
        }

        return $next($request);
    }
}
