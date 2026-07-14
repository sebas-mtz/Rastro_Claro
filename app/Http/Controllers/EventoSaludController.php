<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventoSaludRequest;
use App\Http\Requests\UpdateEventoSaludRequest;
use App\Models\Animal;
use App\Models\EventoSalud;
use App\Models\Lote;
use App\Models\Vacuna;
use App\Models\Tratamiento;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventoSaludController extends Controller
{
    public function index(Request $request): Response
    {
        Tratamiento::sincronizarVencidos();
    EventoSalud::sincronizarVencidos(); // ver punto 5
        $mesParam = $request->get('month', now()->format('Y-m'));
        $fecha    = Carbon::createFromFormat('Y-m', $mesParam)->startOfMonth();
        $year     = $fecha->year;
        $month    = $fecha->month;

        // Eventos del mes para pintar el calendario
        $eventosMes = EventoSalud::with(['animal', 'lote', 'vacuna'])
            ->whereYear('fecha_programada', $year)
            ->whereMonth('fecha_programada', $month)
            ->get();

        // Pintar días: rojo=vencida, amarillo=pendiente, verde=aplicada
        $events    = [];
        $prioridad = ['red' => 3, 'yellow' => 2, 'green' => 1, 'gray' => 0];

        foreach ($eventosMes as $e) {
            $key   = $e->fecha_programada->format('Y-m-d');
            $actual = $events[$key] ?? null;
            $nuevo  = match ($e->estado) {
                'vencida'   => 'red',
                'pendiente' => 'yellow',
                'aplicada'  => 'green',
                default     => 'gray',
            };
            if (!$actual || $prioridad[$nuevo] > $prioridad[$actual]) {
                $events[$key] = $nuevo;
            }
        }

        // Alertas globales (todos los meses, no solo el actual)
        $overdue = EventoSalud::where('estado', 'vencida')->count();
        $dueSoon = EventoSalud::proximas(7)->count();

        // Vacunaciones del mes: pendientes/vencidas y aplicadas por separado
        $vacunacionesMes = $eventosMes->where('tipo', 'vacunacion');

        $pending = $vacunacionesMes
            ->whereIn('estado', ['pendiente', 'vencida'])
            ->map(fn($e) => [
                'id'     => $e->id,
                'vacuna' => $e->vacuna?->nombre ?? 'Vacuna',
                'animal' => $e->animal_id
                    ? (trim(($e->animal?->arete ? "#{$e->animal->arete}" : '') . ' ' .
                             ($e->animal?->alias ? "- {$e->animal->alias}" : ''))
                       ?: "Animal #{$e->animal_id}")
                    : null,
                'lote'   => $e->lote_id
                    ? ($e->lote?->nombre ?? "Lote #{$e->lote_id}")
                    : null,
                'fecha'  => $e->fecha_programada->format('d/m/Y'),
                'estado' => $e->estado,
            ])->values();

        $done = $vacunacionesMes
            ->where('estado', 'aplicada')
            ->map(fn($e) => [
                'id'     => $e->id,
                'vacuna' => $e->vacuna?->nombre ?? 'Vacuna',
                'animal' => $e->animal_id
                    ? (trim(($e->animal?->arete ? "#{$e->animal->arete}" : '') . ' ' .
                             ($e->animal?->alias ? "- {$e->animal->alias}" : ''))
                       ?: "Animal #{$e->animal_id}")
                    : null,
                'lote'   => $e->lote_id
                    ? ($e->lote?->nombre ?? "Lote #{$e->lote_id}")
                    : null,
                'fecha'  => $e->fecha_aplicacion?->format('d/m/Y')
                            ?? $e->fecha_programada->format('d/m/Y'),
            ])->values();

        // Tratamientos activos (todos los meses)
        $treatments = Tratamiento::with(['animal', 'lote'])
    ->whereIn('estado', [Tratamiento::ESTADO_ACTIVO, Tratamiento::ESTADO_VENCIDO])
    ->orderBy('fecha_inicio', 'desc')
    ->get()
    ->map(fn($t) => [
        'id'             => $t->id,
        'nombre'         => $t->nombre,
        'animal'         => $t->animal_id
            ? (trim(($t->animal->arete ? "#{$t->animal->arete}" : '') . ' ' .
                    ($t->animal->alias ? "- {$t->animal->alias}" : ''))
               ?: "Animal #{$t->animal_id}")
            : null,
        'lote'           => $t->lote_id
            ? ($t->lote?->nombre ?? "Lote #{$t->lote_id}")
            : null,
        'estado'         => $t->estado,
        'dias_restantes' => $t->diasRestantes(),
        'rango'          => $t->fecha_inicio->format('d/m/Y') .
                            ($t->fecha_fin ? ' → ' . $t->fecha_fin->format('d/m/Y') : ' → en curso'),
        'notas'          => $t->notas,
    ]);

        // Consultas, revisiones y emergencias pendientes/vencidas (todos los meses)
        $eventos = EventoSalud::with(['animal', 'lote'])
            ->whereIn('tipo', ['consulta', 'revision', 'emergencia'])
            ->whereIn('estado', [EventoSalud::ESTADO_PENDIENTE, EventoSalud::ESTADO_VENCIDA])
            ->orderBy('fecha_programada')
            ->get()
            ->map(fn($e) => [
                'id'          => $e->id,
                'tipo'        => $e->tipo,
                'estado'      => $e->estado,
                'diagnostico' => $e->diagnostico,
                'animal'      => $e->animal_id
                    ? (trim(($e->animal?->arete ? "#{$e->animal->arete}" : '') . ' ' .
                            ($e->animal?->alias ? "- {$e->animal->alias}" : ''))
                       ?: "Animal #{$e->animal_id}")
                    : null,
                'lote'        => $e->lote_id
                    ? ($e->lote?->nombre ?? "Lote #{$e->lote_id}")
                    : null,
                'fecha'       => $e->fecha_programada->format('d/m/Y'),
            ]);

        // Catálogos para formularios
        $animals = Animal::orderBy('alias')
            ->get(['id', 'alias', 'arete', 'especie', 'raza', 'sexo']);
        $lotes   = Lote::orderBy('nombre')
            ->get(['id', 'nombre']);
        $vacunas = Vacuna::orderBy('nombre')
            ->get(['id', 'nombre', 'refuerzo_dias', 'patogeno', 'pauta', 'especie_objetivo']);

        return Inertia::render('Salud/Index', [
            'events'     => $events,
            'alerts'     => ['overdue' => $overdue, 'due_soon' => $dueSoon],
            'pending'    => $pending,
            'done'       => $done,
            'treatments' => $treatments,
            'eventos'    => $eventos,
            'animals'    => $animals,
            'lotes'      => $lotes,
            'vacunas'    => $vacunas,
            'year'       => $year,
            'month'      => $month,
            'month_iso'  => $fecha->format('Y-m'),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Salud/Create', [
            'animal_id' => $request->integer('animal_id') ?: null,
            'lote_id'   => $request->integer('lote_id') ?: null,
            'animales'  => Animal::orderBy('alias')->get(['id', 'alias', 'arete', 'especie']),
            'lotes'     => Lote::orderBy('nombre')->get(['id', 'nombre']),
            'vacunas'   => Vacuna::orderBy('nombre')->get(['id', 'nombre', 'refuerzo_dias', 'especie_objetivo']),
            'tipos'     => [
                EventoSalud::TIPO_CONSULTA,
                EventoSalud::TIPO_VACUNACION,
                EventoSalud::TIPO_REVISION,
                EventoSalud::TIPO_EMERGENCIA,
            ],
        ]);
    }

    public function store(StoreEventoSaludRequest $request): RedirectResponse
    {
        $data            = $request->validated();
        if ($data['tipo'] === EventoSalud::TIPO_VACUNACION && empty($data['diagnostico'])) {
            $vacuna = Vacuna::find($data['vacuna_id'] ?? null);

            $data['diagnostico'] = 'Vacunación programada' . ($vacuna ? ': ' . $vacuna->nombre : '');
        }
        $data['user_id'] = $request->user()->id;
        $data['estado']  = $data['estado'] ?? EventoSalud::ESTADO_PENDIENTE;

        $evento = EventoSalud::create($data);

        // Si se crea directamente como aplicado, registrar fecha de aplicación
        if ($evento->estado === EventoSalud::ESTADO_APLICADA && !$evento->fecha_aplicacion) {
            $evento->update(['fecha_aplicacion' => Carbon::today()]);
        }

        // Programar refuerzo automático si es vacunación con refuerzo configurado
        if ($evento->tipo === EventoSalud::TIPO_VACUNACION && $evento->vacuna_id) {
            $this->programarRefuerzo($evento);
        }

        return redirect()->route('salud.index')
            ->with('success', 'Evento de salud registrado correctamente.');
    }

    public function show(EventoSalud $eventoSalud): Response
    {
        $eventoSalud->load(['animal', 'lote', 'vacuna', 'user', 'tratamientos.user']);

        return Inertia::render('Salud/Show', [
            'evento' => $eventoSalud,
        ]);
    }

    public function edit(EventoSalud $eventoSalud): Response
    {
        return Inertia::render('Salud/Edit', [
            'evento'   => $eventoSalud->load(['vacuna']),
            'animales' => Animal::orderBy('alias')->get(['id', 'alias', 'arete', 'especie']),
            'lotes'    => Lote::orderBy('nombre')->get(['id', 'nombre']),
            'vacunas'  => Vacuna::orderBy('nombre')->get(['id', 'nombre', 'refuerzo_dias', 'especie_objetivo']),
            'tipos'    => [
                EventoSalud::TIPO_CONSULTA,
                EventoSalud::TIPO_VACUNACION,
                EventoSalud::TIPO_REVISION,
                EventoSalud::TIPO_EMERGENCIA,
            ],
        ]);
    }

    public function update(UpdateEventoSaludRequest $request, EventoSalud $eventoSalud): RedirectResponse
    {
        $eventoSalud->update($request->validated());

        return redirect()->route('salud.index')
            ->with('success', 'Evento actualizado correctamente.');
    }

    public function destroy(EventoSalud $eventoSalud): RedirectResponse
    {
        $eventoSalud->delete();

        return redirect()->route('salud.index')
            ->with('success', 'Evento eliminado.');
    }

    /**
     * Marca una vacunación como aplicada.
     * PATCH /eventos-salud/{eventoSalud}/aplicar
     */
    public function aplicar(Request $request, EventoSalud $eventoSalud): RedirectResponse
    {
        if ($eventoSalud->estado === EventoSalud::ESTADO_APLICADA) {
            return back()->with('error', 'Este evento ya fue aplicado.');
        }

        $fechaAplicacion = $request->filled('fecha_aplicacion')
            ? Carbon::parse($request->fecha_aplicacion)
            : Carbon::today();

        $eventoSalud->marcarAplicada($fechaAplicacion);

        // Programar refuerzo si no existe uno futuro pendiente
        if ($eventoSalud->tipo === EventoSalud::TIPO_VACUNACION && $eventoSalud->vacuna_id) {
            $yaExisteRefuerzo = EventoSalud::where(function ($q) use ($eventoSalud) {
                    $q->where('animal_id', $eventoSalud->animal_id)
                      ->orWhere('lote_id', $eventoSalud->lote_id);
                })
                ->where('vacuna_id', $eventoSalud->vacuna_id)
                ->where('estado', EventoSalud::ESTADO_PENDIENTE)
                ->where('fecha_programada', '>', $fechaAplicacion)
                ->exists();

            if (!$yaExisteRefuerzo) {
                $this->programarRefuerzo($eventoSalud, $fechaAplicacion);
            }
        }

        return back()->with('success', 'Vacunación marcada como aplicada.');
    }

    /**
     * Registra el resultado de una consulta, revisión o emergencia.
     * Opcionalmente crea un tratamiento vinculado.
     * PATCH /eventos-salud/{eventoSalud}/completar
     */
    public function completar(Request $request, EventoSalud $eventoSalud): RedirectResponse
    {
        if ($eventoSalud->tipo === EventoSalud::TIPO_VACUNACION) {
            return back()->with('error', 'Las vacunaciones se cierran con "Aplicar".');
        }

        if ($eventoSalud->estado === EventoSalud::ESTADO_APLICADA) {
            return back()->with('error', 'Este evento ya fue registrado.');
        }

        $validated = $request->validate([
            'diagnostico'           => ['nullable', 'string', 'max:1000'],
            'observaciones'         => ['nullable', 'string'],
            'crear_tratamiento'     => ['boolean'],
            'tratamiento_nombre'    => ['required_if:crear_tratamiento,true', 'nullable', 'string', 'max:255'],
            'tratamiento_notas'     => ['nullable', 'string'],
            'tratamiento_fecha_fin' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $eventoSalud->update([
            'diagnostico'      => $validated['diagnostico']   ?? $eventoSalud->diagnostico,
            'observaciones'    => $validated['observaciones'] ?? null,
            'estado'           => EventoSalud::ESTADO_APLICADA,
            'fecha_aplicacion' => Carbon::today(),
        ]);

        // Crear tratamiento vinculado si se solicitó
        if (!empty($validated['crear_tratamiento']) && !empty($validated['tratamiento_nombre'])) {
            Tratamiento::create([
                'animal_id'    => $eventoSalud->animal_id,
                'lote_id'      => $eventoSalud->lote_id,
                'salud_id'     => $eventoSalud->id,
                'nombre'       => $validated['tratamiento_nombre'],
                'notas'        => $validated['tratamiento_notas'] ?? null,
                'fecha_inicio' => Carbon::today(),
                'fecha_fin'    => $validated['tratamiento_fecha_fin'] ?? null,
                'estado'       => Tratamiento::ESTADO_ACTIVO,
                'user_id'      => $request->user()->id,
            ]);
        }

        return back()->with('success', 'Evento registrado correctamente.');
    }

    /**
     * Marca como vencidos todos los eventos pendientes con fecha pasada.
     * POST /eventos-salud/marcar-vencidos
     */
    public function marcarVencidos(): RedirectResponse
{
    $cantidad = EventoSalud::sincronizarVencidos();

    return back()->with('success', "$cantidad evento(s) marcados como vencidos.");
}

    // ─── Helper privado ───────────────────────────────────────────────────────

    private function programarRefuerzo(EventoSalud $evento, ?Carbon $fechaBase = null): ?EventoSalud
    {
        $vacuna = $evento->vacuna ?? Vacuna::find($evento->vacuna_id);

        if (!$vacuna || !$vacuna->refuerzo_dias) return null;

        $fechaBase     = $fechaBase ?? Carbon::parse($evento->fecha_programada);
        $fechaRefuerzo = $fechaBase->copy()->addDays($vacuna->refuerzo_dias);

        return EventoSalud::create([
            'animal_id'        => $evento->animal_id,
            'lote_id'          => $evento->lote_id,
            'tipo'             => EventoSalud::TIPO_VACUNACION,
            'vacuna_id'        => $evento->vacuna_id,
            'fecha_programada' => $fechaRefuerzo,
            'diagnostico'      => 'Refuerzo programado: ' . $vacuna->nombre,
            'estado'           => EventoSalud::ESTADO_PENDIENTE,
            'user_id'          => $evento->user_id,
        ]);
    }
}