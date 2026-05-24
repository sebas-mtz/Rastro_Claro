<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Comparte datos globales con TODAS las páginas Inertia.
     * auth.user.permisos es un array plano: ['animales.ver', 'animales.crear', ...]
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $user ? [
                    'id'                  => $user->id,
                    'name'                => $user->name,
                    'email'               => $user->email,
                    'telefono'            => $user->telefono,
                    'role'                => $user->role,
                    'roleLabel'           => $user->roleLabel(),
                    'plan'                => $user->plan,
                    'activo'              => $user->activo,
                    'isPremium'           => $user->isPremium(),
                    'planLabel'           => $user->planLabel(),
                    'permisos'            => $user->permisosArray(),
                    'must_change_password'=> (bool) $user->must_change_password,
                    // Helpers booleanos para el frontend
                    'isAdmin'             => $user->isAdmin(),
                    'isEncargado'         => $user->isEncargado(),
                ] : null,
            ],

            'flash' => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
                'info'    => $request->session()->get('info'),
            ],
        ];
    }
}
