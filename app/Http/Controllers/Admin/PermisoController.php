<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\PuestoTrabajador;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Support\ModuloSistema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Qué puede tocar cada puesto, y las excepciones por persona.
 *
 * Toda la sección va detrás del middleware `super_admin` (ver routes/web.php).
 * Ocultar los controles no bastaría: lo que de verdad limita el acceso es el
 * middleware VerificarPermisoModulo, que lee justamente lo que se guarda aquí.
 */
class PermisoController extends Controller
{
    public function __construct(protected AuditoriaService $auditoria)
    {
    }

    public function index(Request $request)
    {
        $cuentaId = Auth::user()->cuentaId();

        $puestos = PuestoTrabajador::withoutGlobalScope('owner')
            ->where('owner_id', $cuentaId)
            ->orderBy('nombre')
            ->get()
            ->map(fn (PuestoTrabajador $p) => [
                'id' => $p->id,
                'clave' => $p->clave,
                'nombre' => $p->nombre,
                'area' => $p->area,
                'activo' => $p->activo,
                'permisos' => $p->permisosNormalizados(),
                'personas' => $p->usuarios()->count(),
            ]);

        return Inertia::render('Admin/Permisos/Index', [
            'puestos' => $puestos,
            'modulos' => collect(ModuloSistema::MODULOS)
                ->map(fn ($datos, $clave) => [
                    'clave' => $clave,
                    'nombre' => $datos[0],
                    'descripcion' => $datos[1],
                    'economico' => ModuloSistema::esEconomico($clave),
                ])->values(),
            'acciones' => ModuloSistema::ACCIONES,
            // Cuentas del rancho a las que se pueden poner excepciones.
            'personas' => User::where('cuenta_id', $cuentaId)
                ->with('puestoCatalogo:id,nombre')
                ->orderBy('name')
                ->get()
                ->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'rol_legible' => $u->rolLegible(),
                    'es_dueno' => $u->esDuenoDeCuenta(),
                    'puesto_id' => $u->puesto_id,
                    'puesto_nombre' => $u->puestoCatalogo?->nombre,
                    'permisos_extra' => $u->permisos_extra ?? ['conceder' => [], 'revocar' => []],
                    'permisos_efectivos' => $u->permisos(),
                ]),
        ]);
    }

    /** Guarda los módulos que puede tocar un puesto. */
    public function actualizarPuesto(Request $request, PuestoTrabajador $puesto)
    {
        $this->confirmarQueEsDeMiRancho($puesto->owner_id);

        $datos = $request->validate([
            'permisos' => 'present|array',
            'permisos.*' => 'array',
            'permisos.*.*' => ['string', Rule::in(array_keys(ModuloSistema::ACCIONES))],
        ]);

        $anterior = $puesto->permisosNormalizados();
        $nuevos = $this->limpiar($datos['permisos']);

        $puesto->update(['permisos' => $nuevos]);

        $this->auditoria->registrarSobreEntidad(
            Auditoria::PERMISOS_CAMBIADOS,
            PuestoTrabajador::class,
            $puesto->id,
            $anterior,
            $nuevos,
            "Permisos del puesto «{$puesto->nombre}» actualizados."
        );

        return back()->with('success', "Permisos de «{$puesto->nombre}» guardados.");
    }

    /**
     * Excepciones para una persona concreta, por encima de lo que da su puesto.
     */
    public function actualizarPersona(Request $request, User $user)
    {
        $this->confirmarQueEsDeMiRancho($user->cuenta_id);

        $datos = $request->validate([
            'puesto_id' => ['nullable', Rule::exists('puestos_trabajador', 'id')],
            'conceder' => 'present|array',
            'conceder.*' => 'array',
            'conceder.*.*' => ['string', Rule::in(array_keys(ModuloSistema::ACCIONES))],
            'revocar' => 'present|array',
            'revocar.*' => 'array',
            'revocar.*.*' => ['string', Rule::in(array_keys(ModuloSistema::ACCIONES))],
        ]);

        $anterior = [
            'puesto_id' => $user->puesto_id,
            'permisos_extra' => $user->permisos_extra,
        ];

        $extra = [
            'conceder' => $this->limpiar($datos['conceder']),
            'revocar' => $this->limpiar($datos['revocar']),
        ];

        // Sin excepciones se guarda null, no un objeto vacío: así la ficha
        // dice claramente "esta persona solo tiene lo de su puesto".
        $sinExcepciones = $extra['conceder'] === [] && $extra['revocar'] === [];

        $user->update([
            'puesto_id' => $datos['puesto_id'] ?: null,
            'permisos_extra' => $sinExcepciones ? null : $extra,
        ]);

        $this->auditoria->registrarSobreUsuario(
            Auditoria::PERMISOS_CAMBIADOS,
            $user,
            $anterior,
            ['puesto_id' => $user->puesto_id, 'permisos_extra' => $user->permisos_extra],
            'Permisos individuales actualizados.'
        );

        return back()->with('success', "Permisos de {$user->name} guardados.");
    }

    // ─── Apoyos ───────────────────────────────────────────────────────────

    /**
     * Deja fuera los módulos desconocidos y los que quedaron sin ninguna
     * acción, para que no se guarden claves vacías que después confunden.
     */
    private function limpiar(array $permisos): array
    {
        $validos = array_intersect_key($permisos, array_flip(ModuloSistema::claves()));

        return array_filter(array_map(
            fn ($acciones) => array_values(array_unique((array) $acciones)),
            $validos
        ));
    }

    /**
     * Un superadministrador administra el rancho en el que está, no los ajenos.
     *
     * El scope global no interviene aquí porque las consultas usan
     * withoutGlobalScope para poder mostrar el catálogo completo.
     */
    private function confirmarQueEsDeMiRancho(?int $ownerId): void
    {
        abort_unless(
            (int) $ownerId === Auth::user()->cuentaId(),
            403,
            'Ese registro pertenece a otro rancho.'
        );
    }
}
