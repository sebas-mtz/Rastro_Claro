<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlan
{
    /**
     * Verifica que el usuario tenga el plan requerido.
     *
     * Uso en rutas:
     *   ->middleware(['auth', 'verified', 'plan:premium'])
     */
    public function handle(Request $request, Closure $next, string $requiredPlan): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403);
        }

        // Solo permitimos premium (o admin) cuando se pide "premium"
        if ($requiredPlan === 'premium') {
            if (!$user->isPremium() && !$user->isAdmin()) {
                abort(403, 'Este módulo es solo para usuarios con plan Premium.');
            }
        }

        return $next($request);
    }
}
