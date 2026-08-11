<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\EventoReproductivo;
use App\Models\Lote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\DonadorExterno;
use App\Models\Pajilla;
use App\Services\EstadoProductivoService;
use App\Services\CriaDisponibilidadService;

class EventoReproductivoController extends Controller
{
    public function __construct(
        private readonly CriaDisponibilidadService $criaDisponibilidadService,
    ) {
    }

    // GET /reproduccion
    // Vista principal del módulo — devuelve Inertia con todos los datos
    public function index(Request $request): Response
    {
        // Todos los eventos del rancho con sus relaciones
        $eventos = EventoReproductivo::with([
            'hembra:id,arete,alias,sexo,especie,estado_productivo,lote_id',
            'lote:id,nombre',
            'registradoPor:id,name',
            'servicio.macho:id,arete,alias',
            'servicio.tecnico:id,name',
            'servicio.pajilla:id,codigo,estado,animal_id,donador_externo_id',
            'servicio.pajilla.animal:id,arete,alias',
            'servicio.pajilla.donadorExterno:id,nombre',
            'diagnostico',
            'parto.crias.animal:id,arete,alias,especie,estado_productivo,peso,lote_id,padre_id,padre_externo_id',
            'parto.crias.animal.padre:id,arete,alias',
            'parto.crias.animal.padreExterno:id,codigo,nombre',
            'parto.crias.animal.pesajes:id,animal_id,fecha,peso,notas',
            'parto.crias.animal.muerte:id,animal_id,fecha,causa,observaciones',
            'parto.crias.animal.ventas:id,vendible_id,vendible_type,estado_venta,fecha_venta,observaciones',
            'parto.destete.evento:id,fecha',
            'parto.destete.detalles',
        ])
        ->orderBy('fecha', 'desc')
        ->get()
        ->map(fn($e) => $this->formatearEvento($e));

        // Todos los animales con su lote para los selectores del modal
        $animales = Animal::with('lote:id,nombre')
            ->whereNotIn('estado_productivo', ['faeneado', 'vendido', 'sacrificado', 'muerto'])
            ->get()
            ->map(fn($a) => [
                'id'          => $a->id,
                'alias'       => $a->alias,
                'arete'       => $a->arete,
                'sexo' => strtoupper($a->sexo) === 'H' ? 'hembra' : 'macho',
                'especie'     => $a->especie,
                'lote_id'     => $a->lote_id,
                'lote_nombre' => $a->lote?->nombre,
                'peso' => $a->peso,
                'estado_productivo' => $a->estado_productivo,
            ]);
            $donadoresExternos = DonadorExterno::select(
                'id',
                'codigo',
                'nombre',
                'raza'
            )->orderBy('nombre')->get();

        $lotes = Lote::select('id', 'nombre')->get();
        $pajillas = Pajilla::with([
            'animal:id,arete,alias',
            'donadorExterno:id,codigo,nombre',
        ])
            ->where('estado', 'disponible')
            ->orderBy('codigo')
            ->get()
            ->map(fn ($pajilla) => [
                'id' => $pajilla->id,
                'codigo' => $pajilla->codigo,
                'estado' => $pajilla->estado,
                'tipo_donador' => $pajilla->donador_externo_id
                    ? 'externo'
                    : 'interno',
                'donador' => $pajilla->animal
                    ? [
                        'id' => $pajilla->animal->id,
                        'nombre' => $pajilla->animal->alias
                            ?? $pajilla->animal->arete,
                        'arete' => $pajilla->animal->arete,
                    ]
                    : ($pajilla->donadorExterno
                        ? [
                            'id' => $pajilla->donadorExterno->id,
                            'codigo' => $pajilla->donadorExterno->codigo,
                            'nombre' => $pajilla->donadorExterno->nombre,
                        ]
                        : null),
            ]);
        return Inertia::render('Reproduccion/Index', [
            'eventos'  => $eventos,
            'animales' => $animales,
            'lotes'    => $lotes,
            'pajillas' => $pajillas,
            'donadoresExternos' => $donadoresExternos,
            'estadosProductivos' => EstadoProductivoService::estadosManualesPorEspecie(),
        ]);
    }

    // GET /reproduccion/eventos/{id}
    // Detalle de un evento individual
    public function show(EventoReproductivo $eventoReproductivo): JsonResponse
    {
        $eventoReproductivo->load([
            'hembra:id,arete,alias,sexo,especie,estado_productivo,lote_id',
            'lote:id,nombre',
            'registradoPor:id,name',
            'servicio.macho:id,arete,alias',
            'servicio.tecnico:id,name',
            'servicio.pajilla:id,codigo,estado,animal_id,donador_externo_id',
            'servicio.pajilla.animal:id,arete,alias',
            'servicio.pajilla.donadorExterno:id,nombre',
            'diagnostico',
            'parto.crias.animal:id,arete,alias,especie,estado_productivo,peso,lote_id,padre_id,padre_externo_id',
            'parto.crias.animal.padre:id,arete,alias',
            'parto.crias.animal.padreExterno:id,codigo,nombre',
            'parto.crias.animal.pesajes:id,animal_id,fecha,peso,notas',
            'parto.crias.animal.muerte:id,animal_id,fecha,causa,observaciones',
            'parto.crias.animal.ventas:id,vendible_id,vendible_type,estado_venta,fecha_venta,observaciones',
            'parto.destete.evento:id,fecha',
            'parto.destete.detalles',
        ]);

        return response()->json($this->formatearEvento($eventoReproductivo));
    }

    // DELETE /reproduccion/eventos/{id}
    public function destroy(EventoReproductivo $eventoReproductivo): JsonResponse
    {
        $eventoReproductivo->delete();
        return response()->json(['message' => 'Evento eliminado correctamente']);
    }

    // GET /api/reproduccion/estadisticas
    public function estadisticas(): JsonResponse
    {
        $totalServicios = EventoReproductivo::where('tipo_evento', 'servicio')->count();

        $gestantes = EventoReproductivo::where('tipo_evento', 'diagnostico')
            ->whereHas('diagnostico', fn($q) => $q->where('resultado', 'positivo'))
            ->count();

        $partos = EventoReproductivo::where('tipo_evento', 'parto')->count();

        $fertilidad = $totalServicios > 0
            ? round(($gestantes / $totalServicios) * 100, 1)
            : 0;

        return response()->json([
            'total_servicios' => $totalServicios,
            'gestantes'       => $gestantes,
            'partos'          => $partos,
            'fertilidad'      => $fertilidad,
        ]);
    }

    // GET /api/reproduccion/alertas
    public function alertas(): JsonResponse
    {
        // Vacas con servicio hace más de 45 días sin diagnóstico
        $pendientesDiagnostico = EventoReproductivo::where('tipo_evento', 'servicio')
            ->where('fecha', '<=', now()->subDays(45))
            ->whereDoesntHave('hembra.eventosReproductivos', fn($q) =>
                $q->where('tipo_evento', 'diagnostico')
                  ->whereColumn('fecha', '>', 'evento_reproductivos.fecha')
            )
            ->with('hembra:id,arete,alias')
            ->get()
            ->map(fn($e) => [
                'tipo'    => 'pendiente_diagnostico',
                'nivel'   => 'warning',
                'mensaje' => 'Diagnóstico pendiente',
                'animal'  => $e->hembra?->alias,
                'fecha'   => $e->fecha->format('Y-m-d'),
            ]);

        // Vacas gestantes con parto en menos de 21 días
        $proximasAPari = EventoReproductivo::where('tipo_evento', 'diagnostico')
            ->whereHas('diagnostico', fn($q) =>
                $q->where('resultado', 'positivo')
                  ->whereBetween('fecha_probable_parto', [now(), now()->addDays(21)])
            )
            ->with(['hembra:id,arete,alias', 'diagnostico'])
            ->get()
            ->map(fn($e) => [
                'tipo'    => 'proxima_a_parir',
                'nivel'   => 'danger',
                'mensaje' => 'Próxima a parir',
                'animal'  => $e->hembra?->alias,
                'fecha'   => $e->diagnostico->fecha_probable_parto->format('Y-m-d'),
            ]);

        return response()->json([
            'pendientes_diagnostico' => $pendientesDiagnostico,
            'proximas_a_parir'       => $proximasAPari,
            'total'                  => $pendientesDiagnostico->count() + $proximasAPari->count(),
        ]);
    }

    // GET /api/reproduccion/calendario
    public function calendario(): JsonResponse
    {
        // Eventos para el calendario — los próximos 60 días y los últimos 30
        $eventos = EventoReproductivo::with([
            'hembra:id,arete,alias',
            'diagnostico',
        ])
        ->whereBetween('fecha', [now()->subDays(30), now()->addDays(60)])
        ->orderBy('fecha')
        ->get()
        ->map(fn($e) => [
            'id'    => $e->id,
            'fecha' => $e->fecha->format('Y-m-d'),
            'tipo'  => $e->tipo_evento,
            'label' => $e->hembra?->alias ?? 'Animal',
        ]);

        // Fechas probables de parto desde diagnósticos activos
        $partosProbables = EventoReproductivo::where('tipo_evento', 'diagnostico')
            ->whereHas('diagnostico', fn($q) =>
                $q->where('resultado', 'positivo')
                  ->whereNotNull('fecha_probable_parto')
                  ->where('fecha_probable_parto', '>=', now())
            )
            ->with(['hembra:id,arete,alias', 'diagnostico'])
            ->get()
            ->map(fn($e) => [
                'id'    => $e->id,
                'fecha' => $e->diagnostico->fecha_probable_parto->format('Y-m-d'),
                'tipo'  => 'parto_probable',
                'label' => $e->hembra?->alias ?? 'Animal',
            ]);

        return response()->json([
            'eventos'          => $eventos,
            'partos_probables' => $partosProbables,
        ]);
    }

    // ── Privado ───────────────────────────────────────────────────────────

    private function formatearEvento(EventoReproductivo $evento): array
    {
        $data = [
            'id'             => $evento->id,
            'hembra_id'      => $evento->hembra_id,
            'tipo_evento'    => $evento->tipo_evento,
            'fecha'          => $evento->fecha->format('Y-m-d'),
            'costo'          => $evento->costo,
            'observaciones'  => $evento->observaciones,
            'registrado_por' => $evento->registradoPor?->name,
            'hembra'         => $evento->hembra ? [
                'id'    => $evento->hembra->id,
                'alias' => $evento->hembra->alias,
                'arete' => $evento->hembra->arete,
                'especie' => $evento->hembra->especie,
                'estado_productivo' => $evento->hembra->estado_productivo,
            ] : null,
        ];

        $servicio = $evento->servicio;

        $data['servicio'] = $servicio ? [
            'tipo_servicio'   => $servicio->tipo_servicio,
            'descripcion'     => $servicio->descripcion,
            'numero_servicio' => $servicio->numero_servicio,
            'tecnico'         => $servicio->nombre_tecnico,
            'macho'           => $servicio->macho
                ? ['id' => $servicio->macho->id, 'arete' => $servicio->macho->arete]
                : null,
            'pajilla'         => $servicio->pajilla ? [
                'id'     => $servicio->pajilla->id,
                'codigo' => $servicio->pajilla->codigo,
                'estado' => $servicio->pajilla->estado,
                'donador' => $servicio->pajilla->animal
                    ? $servicio->pajilla->animal->alias ?? $servicio->pajilla->animal->arete
                    : ($servicio->pajilla->donadorExterno?->nombre ?? null),
            ] : null,
        ] : null;

        $data['diagnostico'] = $evento->diagnostico ? [
            'metodo'               => $evento->diagnostico->metodo,
            'resultado'            => $evento->diagnostico->resultado,
            'dias_gestacion'       => $evento->diagnostico->dias_gestacion_estimados,
            'fecha_probable_parto' => $evento->diagnostico->fecha_probable_parto?->format('Y-m-d'),
            'veterinario'          => $evento->diagnostico->nombre_veterinario,
        ] : null;

        $data['parto'] = $evento->parto ? [
            'id'                     => $evento->parto->id,
            'servicio_evento_id'     => $evento->parto->servicio_evento_id,
            'tipo_parto'             => $evento->parto->tipo_parto,
            'asistencia_requerida'   => $evento->parto->asistencia_requerida,
            'complicaciones'         => $evento->parto->complicaciones,
            'detalle_complicaciones' => $evento->parto->detalle_complicaciones,
            'numero_crias'           => $evento->parto->numero_crias,
            'salio_leche'            => $evento->parto->salio_leche,
            'tipo_nacimiento'        => $evento->parto->tipo_nacimiento,
'observaciones_leche'    => $evento->parto->observaciones_leche,
'facilidad_materna'      => $evento->parto->facilidad_materna,
'observaciones_maternas' => $evento->parto->observaciones_maternas,
            'destetado'              => $evento->parto->destete !== null,
            'created_at'             => $evento->parto->created_at?->format('Y-m-d H:i:s'),
            'destete'                => $evento->parto->destete ? [
                'fecha'           => $evento->parto->destete->evento?->fecha?->format('Y-m-d'),
                'estado_madre'    => $evento->parto->destete->estado_madre,
                'estado_productivo_madre' => $evento->parto->destete->estado_productivo_madre,
                'tipo_nacimiento' => $evento->parto->destete->tipo_nacimiento,
                'detalles'        => $evento->parto->destete->detalles->map(fn ($detalle) => [
                    'cria_id'        => $detalle->cria_id,
                    'peso_destete'   => $detalle->peso_destete,
                    'estado_destino' => $detalle->estado_destino,
                ])->toArray(),
            ] : null,
            'crias'                  => $evento->parto->crias->map(fn($c) => [
                ...$this->criaDisponibilidadService->clasificar($c),
                'id'              => $c->id,
                'sexo'            => $c->sexo,
                'peso_nacimiento' => $c->peso_nacimiento,
                'condicion'       => $c->condicion,
                'vigor'           => $c->vigor,
                'identificador'   => $c->identificador,
                'animal_id'       => $c->animal_id,
                'observaciones'   => $c->observaciones,
                'animal'          => $c->animal ? [
                    'id'                 => $c->animal->id,
                    'arete'              => $c->animal->arete,
                    'alias'              => $c->animal->alias,
                    'especie'            => $c->animal->especie,
                    'estado_productivo'  => $c->animal->estado_productivo,
                    'peso'               => $c->animal->peso,
                    'lote_id'            => $c->animal->lote_id,
                    'padre_id'           => $c->animal->padre_id,
                    'padre_externo_id'   => $c->animal->padre_externo_id,
                    'padre'              => $c->animal->padre ? [
                        'arete' => $c->animal->padre->arete,
                        'alias' => $c->animal->padre->alias,
                    ] : null,
                    'padre_externo'      => $c->animal->padreExterno ? [
                        'codigo' => $c->animal->padreExterno->codigo,
                        'nombre' => $c->animal->padreExterno->nombre,
                    ] : null,
                    'pesajes'            => $c->animal->pesajes
                        ->sortBy('fecha')
                        ->map(fn ($pesaje) => [
                            'fecha' => $pesaje->fecha->format('Y-m-d'),
                            'peso'  => $pesaje->peso,
                            'notas' => $pesaje->notas,
                        ])->values()->toArray(),
                    'muerte'             => $c->animal->muerte ? [
                        'fecha'         => $c->animal->muerte->fecha->format('Y-m-d'),
                        'causa'         => $c->animal->muerte->causa,
                        'observaciones' => $c->animal->muerte->observaciones,
                    ] : null,
                    'venta'              => $c->animal->ventas
                        ->filter(fn ($venta) =>
                            $venta->estado_venta === 'completada'
                            || (
                                $c->animal->estado_productivo === 'vendido'
                                && $venta->estado_venta !== 'cancelada'
                            )
                        )
                        ->sortByDesc('fecha_venta')
                        ->map(fn ($venta) => [
                            'fecha'         => $venta->fecha_venta?->format('Y-m-d'),
                            'observaciones' => $venta->observaciones,
                        ])->first(),
                ] : null,
            ])->toArray(),
        ] : null;

        return $data;
    }
}
