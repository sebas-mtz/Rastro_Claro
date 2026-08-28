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
                    ? array_merge(
                        $request->user()->only('id', 'name', 'email', 'role', 'puesto', 'activo'),
                        [
                            'rol_legible' => $request->user()->rolLegible(),
                            // Banderas para pintar la interfaz. La autorización
                            // real la hacen el middleware y las policies: esto
                            // solo evita mostrar botones que no funcionarían.
                            'es_super_admin' => $request->user()->isSuperAdmin(),
                            'puede_gestionar_usuarios' => $request->user()->canManageUsers(),
                            'es_dueno' => $request->user()->esDuenoDeCuenta(),
                            // Permisos por módulo, para armar el menú.
                            'permisos' => $request->user()->permisos(),
                            'modulos' => $request->user()->modulosVisibles(),
                        ]
                    )
                    : null,
            ],
            'flash' => [
                'success' => fn () => session('success'),
                'error'   => fn () => session('error'),
            ],
        ]);
    }
}
