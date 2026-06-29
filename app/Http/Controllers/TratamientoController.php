<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTratamientoRequest;
use App\Models\Animal;
use App\Models\EventoSalud;
use App\Models\Lote;
use App\Models\Tratamiento;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TratamientoController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Tratamiento::with(['animal', 'lote', 'eventoSalud', 'user'])
            ->latest('fecha_inicio');

        if ($request->filled('animal_id')) {
            $query->where('animal_id', $request->animal_id);
        }

        if ($request->filled('lote_id')) {
            $query->where('lote_id', $request->lote_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $tratamientos = $query->paginate(50)->withQueryString();

        // Enriquecer con datos calculados que React mostrará directamente
        $tratamientos->getCollection()->transform(function ($t) {
            $t->dias_restantes = $t->diasRestantes();
            $t->esta_vencido   = $t->estaVencido();
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

        Tratamiento::create($data);

        return redirect()->route('tratamientos.index')
            ->with('success', 'Tratamiento registrado correctamente.');
    }

    public function show(Tratamiento $tratamiento): Response
    {
        $tratamiento->load(['animal', 'lote', 'eventoSalud', 'user']);
        $tratamiento->dias_restantes = $tratamiento->diasRestantes();
        $tratamiento->esta_vencido   = $tratamiento->estaVencido();

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
            'estado'       => ['nullable', 'in:activo,completado'],
            'notas'        => ['nullable', 'string'],
            'responsable'  => ['nullable', 'string', 'max:150'],
        ]);

        $tratamiento->update($validated);

        return redirect()->route('tratamientos.show', $tratamiento)
            ->with('success', 'Tratamiento actualizado.');
    }

    public function destroy(Tratamiento $tratamiento): RedirectResponse
    {
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
        $cantidad = Tratamiento::where('estado', Tratamiento::ESTADO_ACTIVO)
            ->whereNotNull('fecha_fin')
            ->where('fecha_fin', '<', Carbon::today())
            ->update(['estado' => Tratamiento::ESTADO_COMPLETADO]);

        return back()->with('success', "$cantidad tratamiento(s) completados automáticamente.");
    }
}