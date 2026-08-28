<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActividadTrabajadorRequest;
use App\Models\ActividadTrabajador;
use App\Models\Animal;
use App\Models\Lote;
use App\Models\Trabajador;
use App\Services\ManoObraService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Registro del trabajo realizado y su costo de mano de obra.
 */
class ActividadTrabajadorController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected ManoObraService $manoObra)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Trabajador::class);

        $puedeVerCostos = Auth::user()->can('verCostosManoObra', Trabajador::class);

        $actividades = ActividadTrabajador::query()
            ->when($request->trabajador_id, fn ($q) => $q->where('trabajador_id', $request->trabajador_id))
            ->deTipo($request->tipo_actividad)
            ->entreFechas($request->desde, $request->hasta)
            ->with([
                'trabajador:id,nombre,apellido_paterno,apellido_materno,puesto_id',
                'trabajador.puesto:id,nombre',
                'animal:id,arete,alias',
                'lote:id,nombre',
            ])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(function (ActividadTrabajador $actividad) use ($puedeVerCostos) {
                $datos = $actividad->toArray();

                if (! $puedeVerCostos) {
                    unset($datos['costo_hora'], $datos['costo_jornada'], $datos['costo_total'], $datos['costo_por_animal']);
                }

                return $datos;
            });

        return Inertia::render('Trabajadores/Actividades', [
            'actividades' => $actividades,
            'trabajadores' => Trabajador::orderBy('nombre')->get(['id', 'nombre', 'apellido_paterno', 'apellido_materno', 'activo']),
            'tiposActividad' => ActividadTrabajador::TIPOS,
            'modalidadesPago' => ActividadTrabajador::MODALIDADES_PAGO,
            'animales' => Animal::activo()->orderBy('arete')->get(['id', 'arete', 'alias']),
            'lotes' => Lote::orderBy('nombre')->get(['id', 'nombre']),
            'filtros' => $request->only(['trabajador_id', 'tipo_actividad', 'desde', 'hasta']),
            'permisos' => [
                'registrarActividad' => Auth::user()->can('registrarActividad', Trabajador::class),
                'verCostos' => $puedeVerCostos,
            ],
            'totales' => $puedeVerCostos ? $this->totales($request) : null,
        ]);
    }

    public function store(StoreActividadTrabajadorRequest $request)
    {
        $datos = $request->validated();
        $trabajador = Trabajador::findOrFail($datos['trabajador_id']);

        // Un doble clic en el botón no debe generar dos veces el mismo costo.
        if ($this->manoObra->existeDuplicadoReciente($trabajador, $datos)) {
            return back()->withErrors([
                'tipo_actividad' => 'Esta actividad ya se registró hace unos segundos. Verifica antes de volver a guardarla.',
            ])->withInput();
        }

        $actividad = $this->manoObra->registrar($trabajador, $datos);

        return back()->with(
            'success',
            $actividad->animales_atendidos > 1
                ? "Actividad registrada. El costo se repartió entre {$actividad->animales_atendidos} ejemplares."
                : 'Actividad registrada y su costo de mano de obra quedó en el módulo de costos.'
        );
    }

    public function update(StoreActividadTrabajadorRequest $request, ActividadTrabajador $actividad)
    {
        $this->manoObra->actualizar($actividad, $request->validated());

        return back()->with('success', 'Actividad actualizada. Su costo se recalculó.');
    }

    /**
     * Borra la actividad y, con ella, los costos que había generado.
     *
     * Aquí sí se borra físicamente: una actividad mal capturada dejaría un
     * costo falso sumando en la valuación de un ejemplar. Los costos que se
     * eliminan son solo los que esta actividad creó, identificados por su
     * origen; los capturados a mano en el módulo de costos no se tocan.
     */
    public function destroy(ActividadTrabajador $actividad)
    {
        $this->authorize('cambiarEstado', $actividad->trabajador);

        $actividad->costosGenerados()->delete();
        $actividad->delete();

        return back()->with('success', 'Actividad eliminada junto con su costo de mano de obra.');
    }

    /**
     * Vista previa del cálculo, sin guardar nada.
     * El importe definitivo lo vuelve a calcular el backend al guardar.
     */
    public function calcular(Request $request)
    {
        $this->authorize('registrarActividad', Trabajador::class);

        $datos = $request->validate([
            'trabajador_id' => 'required|exists:trabajadores,id',
            'modalidad_pago' => 'required|in:hora,jornada',
            'hora_inicio' => 'nullable|date_format:H:i',
            'hora_fin' => 'nullable|date_format:H:i',
            'horas_trabajadas' => 'nullable|numeric|min:0|max:24',
            'jornadas' => 'nullable|numeric|min:0|max:31',
            'costo_hora' => 'nullable|numeric|min:0',
            'costo_jornada' => 'nullable|numeric|min:0',
            'animal_id' => 'nullable|exists:animals,id',
            'lote_id' => 'nullable|exists:lotes,id',
            'distribuir_entre_animales' => 'boolean',
        ]);

        $trabajador = Trabajador::findOrFail($datos['trabajador_id']);

        $calculo = $this->manoObra->calcular($trabajador, $datos);

        // La lista de ids no le sirve al navegador y son datos de más.
        unset($calculo['animales']);

        return response()->json($calculo);
    }

    private function totales(Request $request): array
    {
        $consulta = ActividadTrabajador::query()
            ->when($request->trabajador_id, fn ($q) => $q->where('trabajador_id', $request->trabajador_id))
            ->deTipo($request->tipo_actividad)
            ->entreFechas($request->desde, $request->hasta);

        return [
            'actividades' => (clone $consulta)->count(),
            'horas' => round((float) (clone $consulta)->sum('horas_trabajadas'), 2),
            'jornadas' => round((float) (clone $consulta)->sum('jornadas'), 2),
            'costo' => round((float) (clone $consulta)->sum('costo_total'), 2),
        ];
    }
}
