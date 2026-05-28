<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ForzarCambioPassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (
            $user
            && $user->must_change_password
            && !$request->routeIs(
                'password.cambiar',
                'password.cambiar.update',
                'logout',
                'verification.*',
                'password.*'
            )
            && !$request->is('_inertia*', 'sanctum*', 'api*')
        ) {
            // Para peticiones Inertia (XHR), Inertia maneja la redirección correctamente
            return redirect()->route('password.cambiar');
        }

        return $next($request);
    }
}
