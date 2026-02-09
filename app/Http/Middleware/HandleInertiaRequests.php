<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * El nombre de la vista raíz que se renderiza en la primera visita.
     */
    protected $rootView = 'app';

    /**
     * Determina la versión de los assets.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Datos que se comparten con todas las respuestas Inertia.
     *  Debe ser PUBLIC para coincidir con la firma de Inertia\Middleware
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => fn () => $request->user()
                    ? $request->user()->only('id', 'name', 'email', 'role', 'plan', 'activo')
                    : null,
            ],
            'flash' => [
                'success' => fn () => session('success'),
                'error'   => fn () => session('error'),
            ],
        ]);
    }
}
