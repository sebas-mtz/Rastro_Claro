<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventoSaludRequest;
use App\Http\Requests\UpdateEventoSaludRequest;
use App\Models\Animal;
use App\Models\EventoSalud;
use App\Models\Vacuna;
use App\Models\Tratamiento;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventoSaludController extends Controller
{
    /**
     * Lista general de eventos, con filtros opcionales.
     * También se puede usar como vista de salud de un animal específico
     * pasando ?animal_id=X en la query.
     */
    /**
 * Vista principal del módulo de salud (Inertia)
 * GET /salud
 */
public function index(Request $request): Response
{
    $mesParam = $request->get('month', now()->format('Y-m'));
    $fecha    = Carbon::createFromFormat('Y-m', $mesParam)->startOfMonth();
    $year     = $fecha->year;
    $month    = $fecha->month;

    // Eventos del mes para pintar el calendario
    $eventosMes = EventoSalud::with(['animal', 'vacuna'])
        ->whereYear('fecha_programada', $year)
        ->whereMonth('fecha_programada', $month)
        ->get();

    // Pintar días del calendario: rojo=vencida, amarillo=pendiente, verde=aplicada
    $events = [];
    foreach ($eventosMes as $e) {
        $key    = $e->fecha_programada->format('Y-m-d');
        $actual = $events[$key] ?? null;
        $nuevo  = match ($e->estado) {
            'vencida'  => 'red',
            'pendiente'=> 'yellow',
            'aplicada' => 'green',
            default    => 'gray',
        };
        // Prioridad: red > yellow > green
        $prioridad = ['red' => 3, 'yellow' => 2, 'green' => 1, 'gray' => 0];
        if (!$actual || $prioridad[$nuevo] > $prioridad[$actual]) {
            $events[$key] = $nuevo;
        }
    }

    // Alertas globales (todos los meses)
    $overdue  = EventoSalud::with('animal')->where('estado', 'vencida')->count();
    $dueSoon  = EventoSalud::with('animal')->proximas(7)->count();

    // Vacunaciones del mes separadas en pendientes y completadas
    $vacunacionesMes = $eventosMes->where('tipo', 'vacunacion');

    $pending = $vacunacionesMes->whereIn('estado', ['pendiente', 'vencida'])
        ->map(fn($e) => [
            'id'     => $e->id,
            'vacuna' => $e->vacuna?->nombre ?? 'Vacuna',
            'animal' => ($e->animal->arete ? "#{$e->animal->arete}" : '') .
                        ($e->animal->alias ? " - {$e->animal->alias}" : ''),
            'fecha'  => $e->fecha_programada->format('d/m/Y'),
            'estado' => $e->estado,
        ])->values();

    $done = $vacunacionesMes->where('estado', 'aplicada')
        ->map(fn($e) => [
            'id'     => $e->id,
            'vacuna' => $e->vacuna?->nombre ?? 'Vacuna',
            'animal' => ($e->animal->arete ? "#{$e->animal->arete}" : '') .
                        ($e->animal->alias ? " - {$e->animal->alias}" : ''),
            'fecha'  => $e->fecha_aplicacion?->format('d/m/Y') ?? $e->fecha_programada->format('d/m/Y'),
        ])->values();

    // Tratamientos activos
    $treatments = Tratamiento::with('animal')
        ->where('estado', 'activo')
        ->orderBy('fecha_inicio', 'desc')
        ->get()
        ->map(fn($t) => [
            'id'              => $t->id,
            'nombre'          => $t->nombre,
            'animal'          => ($t->animal->arete ? "#{$t->animal->arete}" : '') .
                                 ($t->animal->alias ? " - {$t->animal->alias}" : ''),
            'estado'          => $t->estado,
            'dias_restantes'  => $t->diasRestantes(),
            'esta_vencido'    => $t->estaVencido(),
            'rango'           => $t->fecha_inicio->format('d/m/Y') .
                                 ($t->fecha_fin ? ' → ' . $t->fecha_fin->format('d/m/Y') : ''),
            'notas'           => $t->notas,
        ]);
        $eventosNoVacuna = EventoSalud::with('animal')
        ->whereIn('tipo', ['consulta', 'revision', 'emergencia'])
        ->whereIn('estado', [EventoSalud::ESTADO_PENDIENTE, EventoSalud::ESTADO_VENCIDA])
        ->orderBy('fecha_programada')
        ->get()
        ->map(fn($e) => [
            'id'          => $e->id,
            'tipo'        => $e->tipo,
            'estado'      => $e->estado,
            'diagnostico' => $e->diagnostico,
            'animal'      => ($e->animal->arete ? "#{$e->animal->arete}" : '') .
                             ($e->animal->alias ? " - {$e->animal->alias}" : ''),
            'fecha'       => $e->fecha_programada->format('d/m/Y'),
        ]);
     
    // Animales y vacunas para el formulario
    $animals = Animal::orderBy('alias')->get(['id', 'alias', 'arete', 'especie', 'raza', 'sexo']);
    $vacunas = Vacuna::orderBy('nombre')->get(['id', 'nombre', 'refuerzo_dias', 'especie_objetivo']);

    return Inertia::render('Salud/Index', [
        'events'     => $events,
        'alerts'     => ['overdue' => $overdue, 'due_soon' => $dueSoon],
        'pending'    => $pending,
        'done'       => $done,
        'treatments' => $treatments,
        'animals'    => $animals,
        'vacunas'    => $vacunas,
        'year'       => $year,
        'month'      => $month,
        'month_iso'  => $fecha->format('Y-m'),
    ]);
}
    public function create(Request $request): Response
    {
        return Inertia::render('Salud/Create', [
            // Si viene desde la ficha de un animal, pre-seleccionamos el animal
            'animal_id' => $request->integer('animal_id') ?: null,
            'animales'  => Animal::orderBy('nombre')->get(['id', 'nombre', 'numero_arete']),
            'vacunas'   => Vacuna::orderBy('nombre')->get(['id', 'nombre', 'refuerzo_dias']),
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
        $data['user_id'] = $request->user()->id;
        $data['estado']  = $data['estado'] ?? EventoSalud::ESTADO_PENDIENTE;

        $evento = EventoSalud::create($data);

        // Si el evento ya se crea como aplicado, registrar fecha si no viene
        if ($evento->estado === EventoSalud::ESTADO_APLICADA && !$evento->fecha_aplicacion) {
            $evento->update(['fecha_aplicacion' => Carbon::today()]);
        }

        // Programar refuerzo automático de vacunación
        if ($evento->tipo === EventoSalud::TIPO_VACUNACION && $evento->vacuna_id) {
            $this->programarRefuerzo($evento);
        }

        return redirect()->route('eventos-salud.index')
            ->with('success', 'Evento de salud registrado correctamente.');
    }

    public function show(EventoSalud $eventoSalud): Response
    {
        $eventoSalud->load(['animal', 'vacuna', 'user', 'tratamientos.user']);

        return Inertia::render('Salud/Show', [
            'evento' => $eventoSalud,
        ]);
    }

    public function edit(EventoSalud $eventoSalud): Response
    {
        return Inertia::render('Salud/Edit', [
            'evento'  => $eventoSalud->load(['vacuna']),
            'animales' => Animal::orderBy('nombre')->get(['id', 'nombre', 'numero_arete']),
            'vacunas'  => Vacuna::orderBy('nombre')->get(['id', 'nombre', 'refuerzo_dias']),
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

        return redirect()->route('eventos-salud.show', $eventoSalud)
            ->with('success', 'Evento actualizado correctamente.');
    }

    public function destroy(EventoSalud $eventoSalud): RedirectResponse
    {
        $eventoSalud->delete();

        return redirect()->route('eventos-salud.index')
            ->with('success', 'Evento eliminado.');
    }

    /**
     * Marca un evento como aplicado.
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

        // Verificar si ya existe un refuerzo futuro antes de crear otro
        if ($eventoSalud->tipo === EventoSalud::TIPO_VACUNACION && $eventoSalud->vacuna_id) {
            $yaExisteRefuerzo = EventoSalud::where('animal_id', $eventoSalud->animal_id)
                ->where('vacuna_id', $eventoSalud->vacuna_id)
                ->where('estado', EventoSalud::ESTADO_PENDIENTE)
                ->where('fecha_programada', '>', $fechaAplicacion)
                ->exists();

            if (!$yaExisteRefuerzo) {
                $this->programarRefuerzo($eventoSalud, $fechaAplicacion);
            }
        }

        return back()->with('success', 'Evento marcado como aplicado.');
    }
    public function completar(Request $request, EventoSalud $eventoSalud): RedirectResponse
    {
        // Solo aplica a eventos no-vacunación
        if ($eventoSalud->tipo === EventoSalud::TIPO_VACUNACION) {
            return back()->with('error', 'Las vacunaciones se cierran con "Aplicar".');
        }
     
        if ($eventoSalud->estado === EventoSalud::ESTADO_APLICADA) {
            return back()->with('error', 'Este evento ya fue registrado.');
        }
     
        $validated = $request->validate([
            'diagnostico'          => ['nullable', 'string', 'max:1000'],
            'observaciones'        => ['nullable', 'string'],
            'crear_tratamiento'    => ['boolean'],
            'tratamiento_nombre'   => ['required_if:crear_tratamiento,true', 'nullable', 'string', 'max:255'],
            'tratamiento_notas'    => ['nullable', 'string'],
            'tratamiento_fecha_fin'=> ['nullable', 'date', 'after_or_equal:today'],
        ]);
     
        // Actualizar el evento con el resultado
        $eventoSalud->update([
            'diagnostico'      => $validated['diagnostico']   ?? $eventoSalud->diagnostico,
            'observaciones'    => $validated['observaciones'] ?? null,
            'estado'           => EventoSalud::ESTADO_APLICADA,
            'fecha_aplicacion' => Carbon::today(),
        ]);
     
        // Crear tratamiento vinculado si el usuario lo pidió
        if (!empty($validated['crear_tratamiento']) && !empty($validated['tratamiento_nombre'])) {
            Tratamiento::create([
                'animal_id'   => $eventoSalud->animal_id,
                'salud_id'    => $eventoSalud->id,         // vinculado al evento
                'nombre'      => $validated['tratamiento_nombre'],
                'notas'       => $validated['tratamiento_notas'] ?? null,
                'fecha_inicio'=> Carbon::today(),
                'fecha_fin'   => $validated['tratamiento_fecha_fin'] ?? null,
                'estado'      => Tratamiento::ESTADO_ACTIVO,
                'user_id'     => $request->user()->id,
            ]);
        }
     
        return back()->with('success', 'Evento registrado correctamente.');
    }
    /**
     * Recorre pendientes con fecha pasada y los marca como vencidos.
     * POST /eventos-salud/marcar-vencidos
     * Llámalo desde un botón de admin o desde un Scheduled Command.
     */
    public function marcarVencidos(): RedirectResponse
    {
        $cantidad = EventoSalud::where('estado', EventoSalud::ESTADO_PENDIENTE)
            ->where('fecha_programada', '<', Carbon::today())
            ->update(['estado' => EventoSalud::ESTADO_VENCIDA]);

        return back()->with('success', "$cantidad evento(s) marcados como vencidos.");
    }

    // ─── Helper privado ───────────────────────────────────────────

    private function programarRefuerzo(EventoSalud $evento, ?Carbon $fechaBase = null): ?EventoSalud
    {
        $vacuna = $evento->vacuna ?? Vacuna::find($evento->vacuna_id);

        if (!$vacuna || !$vacuna->refuerzo_dias) return null;

        $fechaBase     = $fechaBase ?? Carbon::parse($evento->fecha_programada);
        $fechaRefuerzo = $fechaBase->copy()->addDays($vacuna->refuerzo_dias);

        return EventoSalud::create([
            'animal_id'        => $evento->animal_id,
            'tipo'             => EventoSalud::TIPO_VACUNACION,
            'vacuna_id'        => $evento->vacuna_id,
            'fecha_programada' => $fechaRefuerzo,
            'diagnostico'      => 'Refuerzo programado: ' . $vacuna->nombre,
            'estado'           => EventoSalud::ESTADO_PENDIENTE,
            'user_id'          => $evento->user_id,
        ]);
    }
}