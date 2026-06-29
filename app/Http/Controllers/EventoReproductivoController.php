<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\EventoReproductivo;
use App\Models\Lote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventoReproductivoController extends Controller
{
    // GET /reproduccion
    // Vista principal del módulo — devuelve Inertia con todos los datos
    public function index(Request $request): Response
    {
        // Todos los eventos del rancho con sus relaciones
        $eventos = EventoReproductivo::with([
            'hembra:id,arete,alias,sexo,especie,lote_id',
            'lote:id,nombre',
            'registradoPor:id,name',
            'servicio.macho:id,arete,alias',
            'servicio.tecnico:id,name',
            'servicio.pajilla:id,codigo,estado,animal_id,donador_externo_id',
            'servicio.pajilla.animal:id,arete,alias',
            'servicio.pajilla.donadorExterno:id,nombre',
            'diagnostico',
            'parto.crias.animal:id,arete,alias',
        ])
        ->orderBy('fecha', 'desc')
        ->get()
        ->map(fn($e) => $this->formatearEvento($e));

        // Todos los animales con su lote para los selectores del modal
        $animales = Animal::with('lote:id,nombre')
            ->whereNotIn('estado_productivo', ['faeneado', 'vendido', 'sacrificado'])
            ->get()
            ->map(fn($a) => [
                'id'          => $a->id,
                'alias'       => $a->alias,
                'arete'       => $a->arete,
                'sexo'        => in_array(strtolower($a->sexo), ['f', 'female', 'hembra']) ? 'hembra' : 'macho',
                'especie'     => $a->especie,
                'lote_id'     => $a->lote_id,
                'lote_nombre' => $a->lote?->nombre,
            ]);

        $lotes = Lote::select('id', 'nombre')->get();

        return Inertia::render('Reproduccion/Index', [
            'eventos'  => $eventos,
            'animales' => $animales,
            'lotes'    => $lotes,
        ]);
    }

    // GET /reproduccion/eventos/{id}
    // Detalle de un evento individual
    public function show(EventoReproductivo $eventoReproductivo): JsonResponse
    {
        $eventoReproductivo->load([
            'hembra:id,arete,alias',
            'lote:id,nombre',
            'registradoPor:id,name',
            'servicio.macho:id,arete,alias',
            'servicio.tecnico:id,name',
            'servicio.pajilla:id,codigo,estado,animal_id,donador_externo_id',
            'servicio.pajilla.animal:id,arete,alias',
            'servicio.pajilla.donadorExterno:id,nombre',
            'diagnostico',
            'parto.crias.animal:id,arete,alias',
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
            'tipo_parto'             => $evento->parto->tipo_parto,
            'asistencia_requerida'   => $evento->parto->asistencia_requerida,
            'complicaciones'         => $evento->parto->complicaciones,
            'detalle_complicaciones' => $evento->parto->detalle_complicaciones,
            'numero_crias'           => $evento->parto->numero_crias,
            'crias'                  => $evento->parto->crias->map(fn($c) => [
                'id'              => $c->id,
                'sexo'            => $c->sexo,
                'peso_nacimiento' => $c->peso_nacimiento,
                'condicion'       => $c->condicion,
                'identificador'   => $c->identificador,
                'animal_id'       => $c->animal_id,
            ])->toArray(),
        ] : null;

        return $data;
    }
}