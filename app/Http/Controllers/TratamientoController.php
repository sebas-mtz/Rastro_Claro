<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTratamientoRequest;
use App\Models\Animal;
use App\Models\Costo;
use App\Models\EventoSalud;
use App\Models\Lote;
use App\Models\Tratamiento;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TratamientoController extends Controller
{
    public function index(Request $request): Response
{
    Tratamiento::sincronizarVencidos();

    $query = Tratamiento::with(['animal', 'lote', 'eventoSalud', 'user'])
        ->latest('fecha_inicio');

    if ($request->filled('animal_id')) {
        $query->where('animal_id', $request->animal_id);
    }

    if ($request->filled('lote_id')) {
        $query->where('lote_id', $request->lote_id);
    }

    if ($request->filled('estado')) {
        $query->where('estado', $request->estado); // ahora acepta activo|vencido|completado
    }

    $tratamientos = $query->paginate(50)->withQueryString();

    $tratamientos->getCollection()->transform(function ($t) {
        $t->dias_restantes = $t->diasRestantes(); // se conserva para mostrar "3d restantes"
        return $t;
    });

    return Inertia::render('Tratamientos/Index', [
        'tratamientos' => $tratamientos,
        'filtros'      => $request->only(['animal_id', 'lote_id', 'estado']),
    ]);
}

    public function create(Request $request): Response
    {
        return Inertia::render('Tratamientos/Create', [
            'animal_id'     => $request->integer('animal_id') ?: null,
            'lote_id'       => $request->integer('lote_id') ?: null,
            'salud_id'      => $request->integer('salud_id') ?: null,
            'animales'      => Animal::orderBy('nombre')->get(['id', 'nombre', 'numero_arete']),
            'lotes'         => Lote::orderBy('nombre')->get(['id', 'nombre']),
            // Eventos de salud del animal o lote para vincular el tratamiento a un diagnóstico
            'eventos_salud' => match (true) {
                $request->filled('animal_id') => EventoSalud::where('animal_id', $request->animal_id)
                    ->orderByDesc('fecha_programada')
                    ->get(['id', 'diagnostico', 'fecha_programada']),
                $request->filled('lote_id') => EventoSalud::where('lote_id', $request->lote_id)
                    ->orderByDesc('fecha_programada')
                    ->get(['id', 'diagnostico', 'fecha_programada']),
                default => [],
            },
        ]);
    }

    public function store(StoreTratamientoRequest $request): RedirectResponse
    {
        $data            = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['estado']  = $data['estado'] ?? Tratamiento::ESTADO_ACTIVO;

        $tratamiento = Tratamiento::create($data);

        // Un solo registro: el costo capturado aquí se refleja en el módulo de
        // Costos sin volver a escribirlo.
        $this->sincronizarCosto($tratamiento);

        return back()->with('success', 'Tratamiento registrado correctamente.');
    }

    public function show(Tratamiento $tratamiento): Response
{
    // Por si este registro puntual no ha sido tocado desde que venció
    Tratamiento::sincronizarVencidos();
    $tratamiento->refresh(); // recarga por si acaba de cambiar de estado

    $tratamiento->load(['animal', 'lote', 'eventoSalud', 'user']);
    $tratamiento->dias_restantes = $tratamiento->diasRestantes();

    return Inertia::render('Tratamientos/Show', [
        'tratamiento' => $tratamiento,
    ]);
}

    public function edit(Tratamiento $tratamiento): Response
    {
        return Inertia::render('Tratamientos/Edit', [
            'tratamiento'   => $tratamiento->load(['eventoSalud']),
            'animales'      => Animal::orderBy('nombre')->get(['id', 'nombre', 'numero_arete']),
            'lotes'         => Lote::orderBy('nombre')->get(['id', 'nombre']),
            'eventos_salud' => EventoSalud::where(function ($q) use ($tratamiento) {
                    $q->where('animal_id', $tratamiento->animal_id)
                      ->orWhere('lote_id', $tratamiento->lote_id);
                })
                ->orderByDesc('fecha_programada')
                ->get(['id', 'diagnostico', 'fecha_programada']),
        ]);
    }

    public function update(Request $request, Tratamiento $tratamiento): RedirectResponse
    {
        $validated = $request->validate([
            'nombre'       => ['sometimes', 'string', 'max:255'],
            'fecha_inicio' => ['sometimes', 'date'],
            'fecha_fin'    => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'estado' => ['nullable', 'in:activo,vencido,completado'],
            'costo'        => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'notas'        => ['nullable', 'string'],
            'responsable'  => ['nullable', 'string', 'max:150'],
        ]);

        $tratamiento->update($validated);

        $this->sincronizarCosto($tratamiento);

        return redirect()->route('tratamientos.show', $tratamiento)
            ->with('success', 'Tratamiento actualizado.');
    }

    public function destroy(Tratamiento $tratamiento): RedirectResponse
    {
        // El costo generado por este tratamiento se va con él, para no dejar
        // gastos huérfanos en el módulo de Costos.
        Costo::where('origen_tipo', Tratamiento::class)
            ->where('origen_id', $tratamiento->id)
            ->delete();

        $tratamiento->delete();

        return redirect()->route('tratamientos.index')
            ->with('success', 'Tratamiento eliminado.');
    }

    /**
     * Marca un tratamiento como completado.
     * PATCH /tratamientos/{tratamiento}/completar
     */
    public function completar(Tratamiento $tratamiento): RedirectResponse
    {
        if ($tratamiento->estado === Tratamiento::ESTADO_COMPLETADO) {
            return back()->with('error', 'Este tratamiento ya está completado.');
        }

        $tratamiento->marcarCompletado();

        return back()->with('success', 'Tratamiento marcado como completado.');
    }

    /**
     * Completa automáticamente tratamientos cuya fecha_fin ya pasó.
     * POST /tratamientos/marcar-vencidos
     */
    public function marcarVencidos(): RedirectResponse
{
    $cantidad = Tratamiento::sincronizarVencidos();

    return back()->with('success', "$cantidad tratamiento(s) marcados como vencidos.");
}

    /**
     * Refleja el costo capturado en el tratamiento como una fila de la tabla
     * `costos`, ligada al tratamiento mediante origen_tipo/origen_id.
     *
     * Así el gasto se captura una sola vez: aparece en el módulo de Costos y
     * en la valuación del animal, y la deduplicación de AnimalValuationService
     * impide que se cuente dos veces.
     */
    private function sincronizarCosto(Tratamiento $tratamiento): void
    {
        $existente = Costo::where('origen_tipo', Tratamiento::class)
            ->where('origen_id', $tratamiento->id)
            ->first();

        // Sin monto, o sin animal concreto, no hay costo individual que registrar.
        // (Los tratamientos de lote se capturan desde el módulo de Costos.)
        if (blank($tratamiento->costo) || (float) $tratamiento->costo <= 0 || ! $tratamiento->animal_id) {
            $existente?->delete();

            return;
        }

        $atributos = [
            'concepto' => $tratamiento->nombre,
            'descripcion' => $tratamiento->notas,
            'categoria' => 'medicamentos',
            'tipo_costo' => 'directo',
            'monto' => $tratamiento->costo,
            'cantidad' => 1,
            'fecha' => $tratamiento->fecha_inicio,
            'animal_id' => $tratamiento->animal_id,
            'lote_id' => $tratamiento->lote_id,
            'proveedor' => $tratamiento->responsable,
            'observaciones' => 'Registrado automáticamente desde el módulo de Tratamientos.',
            'user_id' => $tratamiento->user_id ?? Auth::id(),
            'origen_tipo' => Tratamiento::class,
            'origen_id' => $tratamiento->id,
        ];

        if ($existente) {
            $existente->update($atributos);

            return;
        }

        Costo::create($atributos);
    }
}