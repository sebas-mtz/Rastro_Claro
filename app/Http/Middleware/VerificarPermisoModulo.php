<?php

namespace App\Http\Middleware;

use App\Support\ModuloSistema;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Comprueba que quien pide una página tenga permiso sobre su módulo.
 *
 * Va aplicado al grupo entero de rutas autenticadas, no ruta por ruta, y
 * deduce el módulo del NOMBRE de la ruta. Así una ruta nueva queda cubierta
 * sola, sin que nadie tenga que acordarse de protegerla: es el error que este
 * diseño quiere volver imposible.
 *
 * Una ruta que no corresponde a ningún módulo —el panel, el perfil, cerrar
 * sesión— pasa sin restricción. Para que eso no se convierta en un agujero
 * silencioso, PermisosModuloTest recorre todas las rutas de la aplicación y
 * falla si aparece alguna sin clasificar.
 */
class VerificarPermisoModulo
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        $modulo = ModuloSistema::desdeRuta($request->route()?->getName());

        if ($modulo === null) {
            return $next($request);
        }

        $accion = ModuloSistema::accionDesdeMetodo($request->method());

        if ($user->puede($modulo, $accion)) {
            return $next($request);
        }

        abort(403, $this->explicacion($modulo, $accion, $user->puede($modulo)));
    }

    /**
     * Un mensaje que diga qué pasó sin revelar nada del rancho.
     *
     * Se distingue "no entras a este módulo" de "entras pero no puedes hacer
     * esto", porque son problemas distintos y el segundo suele ser un botón
     * que no debería haberse mostrado.
     */
    private function explicacion(string $modulo, string $accion, bool $puedeVer): string
    {
        $nombre = ModuloSistema::nombre($modulo);

        if (! $puedeVer) {
            return "Tu puesto no tiene acceso al módulo {$nombre}.";
        }

        $verbo = strtolower(ModuloSistema::ACCIONES[$accion] ?? $accion);

        return "Puedes consultar {$nombre}, pero no {$verbo} en este módulo.";
    }
}
