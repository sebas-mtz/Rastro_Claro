<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckPlan Middleware
 *
 * Verifica que el usuario tenga el plan requerido.
 *
 * Uso en rutas:
 *   ->middleware('plan:premium')
 *
 * Registrar en bootstrap/app.php:
 *   $middleware->alias(['plan' => \App\Http\Middleware\CheckPlan::class]);
 */
class CheckPlan
{
    public function handle(Request $request, Closure $next, string $requiredPlan): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'No autenticado.');
        }

        if ($requiredPlan === 'premium' && !$user->isPremium()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'   => 'premium_required',
                    'message' => 'Este módulo requiere plan Premium.',
                ], 403);
            }

            // Para Inertia: redirigir a la página de planes con mensaje
            return redirect()->route('planes.index')
                ->with('info', 'Este módulo es exclusivo del plan Premium. Mejora tu cuenta para acceder.');
        }

        return $next($request);
    }
}