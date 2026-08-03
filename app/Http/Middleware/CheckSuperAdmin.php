<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reserva una ruta al superadministrador.
 *
 * Responde 403, nunca una redirección silenciosa: quien no tiene permiso debe
 * saber que no lo tiene, no acabar en el panel creyendo que la acción se hizo.
 */
class CheckSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            abort(403, 'Necesitas iniciar sesión para acceder a esta sección.');
        }

        if (! $user->isSuperAdmin()) {
            abort(403, 'Esta sección está reservada al superadministrador.');
        }

        // Una cuenta desactivada conserva su rol pero no su acceso.
        //
        // Se compara contra false a propósito: `activo` lo rellena la base de
        // datos por omisión, así que un modelo que todavía no se ha releído
        // tiene el atributo en null. Solo un false explícito significa baja.
        if ($user->activo === false) {
            abort(403, 'Tu cuenta está desactivada.');
        }

        return $next($request);
    }
}
