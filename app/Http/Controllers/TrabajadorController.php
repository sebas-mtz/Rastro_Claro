<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTrabajadorRequest;
use App\Http\Requests\UpdateTrabajadorRequest;
use App\Models\ActividadTrabajador;
use App\Models\Animal;
use App\Models\Costo;
use App\Models\Lote;
use App\Models\PuestoTrabajador;
use App\Models\Trabajador;
use App\Models\User;
use App\Services\ManoObraService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TrabajadorController extends Controller
{
    // El Controller base del proyecto no trae el trait, así que se declara
    // aquí en vez de tocarlo y afectar a los demás controladores.
    use AuthorizesRequests;

    public function __construct(protected ManoObraService $manoObra)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Trabajador::class);

        $usuario = Auth::user();
        // A nivel de módulo decide si se pintan las columnas reservadas; el
        // permiso real se vuelve a evaluar registro por registro más abajo.
        $puedeVerSensibles = $usuario->can('verDatosSensibles', Trabajador::class);

        $trabajadores = Trabajador::query()
            ->buscar($request->buscar)
            ->when($request->puesto_id, fn ($q) => $q->where('puesto_id', $request->puesto_id))
            ->when($request->area, fn ($q) => $q->where('area', $request->area))
            // 'activo' e 'inactivo' explícitos; sin filtro se ven todos.
            ->when($request->estado === 'activo', fn ($q) => $q->where('activo', true))
            ->when($request->estado === 'inactivo', fn ($q) => $q->where('activo', false))
            ->with(['puesto:id,nombre,area', 'usuario:id,name,email'])
            ->orderBy('activo', 'desc')
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Trabajador $t) => $this->presentar($t, $usuario->can('verDatosSensibles', $t)));

        return Inertia::render('Trabajadores/Index', [
            'trabajadores' => $trabajadores,
            'puestos' => PuestoTrabajador::activo()->orderBy('nombre')->get(['id', 'nombre', 'area']),
            'areas' => PuestoTrabajador::AREAS,
            'tiposContratacion' => Trabajador::TIPOS_CONTRATACION,
            'filtros' => $request->only(['buscar', 'puesto_id', 'area', 'estado']),
            'permisos' => $this->permisos(),
            // Cuentas todavía libres, para el enlace opcional trabajador ↔ usuario.
            'usuariosDisponibles' => $puedeVerSensibles ? $this->usuariosDisponibles() : [],
            'resumen' => [
                'total' => Trabajador::count(),
                'activos' => Trabajador::activo()->count(),
                'inactivos' => Trabajador::where('activo', false)->count(),
            ],
        ]);
    }

    public function show(Trabajador $trabajador)
    {
        $this->authorize('view', $trabajador);

        $puedeVerSensibles = Auth::user()->can('verDatosSensibles', $trabajador);
        $puedeVerCostos = Auth::user()->can('verCostosManoObra', $trabajador);

        $actividades = $trabajador->actividades()
            ->with(['animal:id,arete,alias', 'lote:id,nombre', 'faena:id,fecha'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Trabajadores/Show', [
            'trabajador' => $this->presentar($trabajador->load(['puesto', 'usuario:id,name,email', 'registradoPor:id,name']), $puedeVerSensibles),
            'actividades' => $actividades,
            'resumen' => $puedeVerCostos
                ? $this->manoObra->resumen($trabajador)
                // Sin permiso para ver dinero se entregan las cifras de trabajo
                // pero no los importes: se omiten, no se ponen en cero.
                : collect($this->manoObra->resumen($trabajador))
                    ->except(['costo_total', 'costo_promedio'])
                    ->all(),
            'costos' => $puedeVerCostos ? $this->costosDeManoObra($trabajador) : null,
            'historialCambios' => $this->historialCambios($trabajador),
            'tiposActividad' => ActividadTrabajador::TIPOS,
            'modalidadesPago' => ActividadTrabajador::MODALIDADES_PAGO,
            'animales' => Animal::activo()->orderBy('arete')->get(['id', 'arete', 'alias']),
            'lotes' => Lote::orderBy('nombre')->get(['id', 'nombre']),
            'permisos' => $this->permisos($trabajador),
        ]);
    }

    public function store(StoreTrabajadorRequest $request)
    {
        $datos = $request->validated();
        $datos['registrado_por'] = Auth::id();
        $datos['activo'] = $request->boolean('activo', true);

        // Si no se eligió área, se hereda la del puesto del catálogo.
        if (empty($datos['area'])) {
            $datos['area'] = PuestoTrabajador::find($datos['puesto_id'])?->area;
        }

        Trabajador::create($datos);

        return redirect()
            ->route('trabajadores.index')
            ->with('success', 'Trabajador registrado correctamente.');
    }

    public function update(UpdateTrabajadorRequest $request, Trabajador $trabajador)
    {
        $datos = $request->validated();

        if (empty($datos['area'])) {
            $datos['area'] = PuestoTrabajador::find($datos['puesto_id'])?->area;
        }

        $trabajador->update($datos);

        return back()->with('success', 'Datos del trabajador actualizados.');
    }

    /**
     * Activa o inactiva a una persona.
     *
     * Nunca se borra a quien ya tiene historial: se inactiva, y sus actividades
     * y costos siguen contando en los reportes del periodo en que trabajó.
     */
    public function cambiarEstado(Request $request, Trabajador $trabajador)
    {
        $this->authorize('cambiarEstado', $trabajador);

        $datos = $request->validate([
            'activo' => 'required|boolean',
            'motivo_baja' => 'nullable|string|max:255',
            'fecha_baja' => 'nullable|date|before_or_equal:today',
        ]);

        $activo = (bool) $datos['activo'];

        $trabajador->update([
            'activo' => $activo,
            'fecha_baja' => $activo ? null : ($datos['fecha_baja'] ?? now()->toDateString()),
            'motivo_baja' => $activo ? null : ($datos['motivo_baja'] ?? null),
        ]);

        return back()->with(
            'success',
            $activo
                ? 'Trabajador reactivado.'
                : 'Trabajador inactivado. Su historial de actividades y costos se conserva.'
        );
    }

    /**
     * Solo permite corregir un alta equivocada. Con historial de por medio la
     * política lo impide y se responde explicando por qué.
     */
    public function destroy(Trabajador $trabajador)
    {
        if ($trabajador->tieneRegistrosRelacionados()) {
            $relacionados = $trabajador->registrosRelacionados();

            return back()->withErrors([
                'trabajador' => sprintf(
                    'No se puede eliminar: tiene %d actividad(es) y %d costo(s) registrados. Inactívalo para conservar su historial.',
                    $relacionados['actividades'],
                    $relacionados['costos']
                ),
            ]);
        }

        $this->authorize('delete', $trabajador);

        $trabajador->delete();

        return redirect()
            ->route('trabajadores.index')
            ->with('success', 'Trabajador eliminado.');
    }

    // ─── Apoyos ───────────────────────────────────────────────────────────

    /**
     * Convierte al trabajador en el arreglo que viaja al navegador, retirando
     * los campos sensibles cuando el usuario no tiene permiso para verlos.
     *
     * Se retiran del arreglo, no se enmascaran: lo que no se envía no se puede
     * leer desde las herramientas del navegador.
     */
    private function presentar(Trabajador $trabajador, bool $puedeVerSensibles): array
    {
        $datos = $trabajador->toArray();

        if (! $puedeVerSensibles) {
            foreach (Trabajador::CAMPOS_SENSIBLES as $campo) {
                unset($datos[$campo]);
            }
        }

        return $datos;
    }

    private function permisos(?Trabajador $trabajador = null): array
    {
        $user = Auth::user();

        return [
            'crear' => $user->can('create', Trabajador::class),
            'editar' => $trabajador
                ? $user->can('update', $trabajador)
                : $user->can('create', Trabajador::class),
            'cambiarEstado' => $trabajador
                ? $user->can('cambiarEstado', $trabajador)
                : $user->can('create', Trabajador::class),
            'verSensibles' => $user->can('verDatosSensibles', $trabajador ?? Trabajador::class),
            'verCostos' => $user->can('verCostosManoObra', $trabajador ?? Trabajador::class),
            'registrarActividad' => $user->can('registrarActividad', Trabajador::class),
        ];
    }

    /** Costos de mano de obra generados por esta persona. */
    private function costosDeManoObra(Trabajador $trabajador)
    {
        return Costo::deTrabajador($trabajador->id)
            ->with(['animal:id,arete,alias', 'lote:id,nombre'])
            ->orderByDesc('fecha')
            ->limit(50)
            ->get();
    }

    /**
     * Hechos registrados sobre la relación laboral.
     *
     * No es una bitácora campo por campo: el sistema no la lleva. Son los
     * eventos que sí quedan guardados con fecha, y se presentan como tales.
     */
    private function historialCambios(Trabajador $trabajador): array
    {
        $eventos = [];

        $eventos[] = [
            'fecha' => $trabajador->created_at?->toDateString(),
            'evento' => 'Alta en el sistema',
            'detalle' => $trabajador->registradoPor
                ? "Registrado por {$trabajador->registradoPor->name}"
                : null,
        ];

        if ($trabajador->fecha_contratacion) {
            $eventos[] = [
                'fecha' => $trabajador->fecha_contratacion->toDateString(),
                'evento' => 'Fecha de contratación',
                'detalle' => $trabajador->puesto?->nombre,
            ];
        }

        if (! $trabajador->activo && $trabajador->fecha_baja) {
            $eventos[] = [
                'fecha' => $trabajador->fecha_baja->toDateString(),
                'evento' => 'Inactivación',
                'detalle' => $trabajador->motivo_baja,
            ];
        }

        if ($trabajador->updated_at && $trabajador->updated_at->ne($trabajador->created_at)) {
            $eventos[] = [
                'fecha' => $trabajador->updated_at->toDateString(),
                'evento' => 'Última actualización de datos',
                'detalle' => null,
            ];
        }

        usort($eventos, fn ($a, $b) => strcmp((string) $a['fecha'], (string) $b['fecha']));

        return $eventos;
    }

    /**
     * Cuentas que todavía no están enlazadas a ningún trabajador.
     */
    private function usuariosDisponibles()
    {
        $enlazados = Trabajador::whereNotNull('user_id')->pluck('user_id');

        return User::whereNotIn('id', $enlazados)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}
