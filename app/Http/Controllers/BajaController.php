<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Baja;
use App\Services\AnimalValuationService;
use App\Services\BajaService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BajaController extends Controller
{
    public function __construct(protected BajaService $bajaService)
    {
    }

    public function index(Request $request)
    {
        $bajas = Baja::query()
            ->when($request->tipo_salida, fn ($q) => $q->where('tipo_salida', $request->tipo_salida))
            ->when($request->desde, fn ($q) => $q->whereDate('fecha', '>=', $request->desde))
            ->when($request->hasta, fn ($q) => $q->whereDate('fecha', '<=', $request->hasta))
            ->with(['animal:id,arete,alias,raza_original', 'responsable:id,name'])
            ->orderByDesc('fecha')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Bajas/Index', [
            'bajas' => $bajas,
            'indicadores' => $this->bajaService->indicadores($request->desde, $request->hasta),
            'tipos' => Baja::TIPOS,
            'tiposConPrecio' => Baja::TIPOS_CON_PRECIO,
            'filtros' => $request->only(['tipo_salida', 'desde', 'hasta']),
            // Solo se puede dar de baja a un ejemplar que siga en el rebaño.
            'animalesActivos' => Animal::activo()
                ->orderBy('arete')
                ->get(['id', 'arete', 'alias']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'animal_id' => 'required|exists:animals,id',
            'fecha' => 'required|date|before_or_equal:today',
            'tipo_salida' => ['required', Rule::in(array_keys(Baja::TIPOS))],
            'causa' => 'nullable|string|max:255',
            'diagnostico' => 'nullable|string|max:2000',
            'responsable_id' => 'nullable|exists:users,id',
            'precio_salida' => 'nullable|numeric|min:0',
            'observaciones' => 'nullable|string|max:2000',
            'documento' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $animal = Animal::findOrFail($validated['animal_id']);

        if (! $animal->activo) {
            return back()->withErrors([
                'animal_id' => 'Este ejemplar ya fue dado de baja del rebaño.',
            ])->withInput();
        }

        // La salida no puede ser anterior al nacimiento del ejemplar.
        $nacimiento = AnimalValuationService::fechaNacimiento($animal);

        if ($nacimiento && $nacimiento->gt($validated['fecha'])) {
            return back()->withErrors([
                'fecha' => 'La fecha de salida no puede ser anterior al nacimiento del ejemplar.',
            ])->withInput();
        }

        if ($request->hasFile('documento')) {
            $validated['documento'] = $request->file('documento')->store('bajas', 'public');
        }

        $this->bajaService->registrar($animal, $validated);

        return back()->with('success', 'Salida del rebaño registrada. El historial del ejemplar se conserva.');
    }

    public function destroy(Baja $baja)
    {
        $this->bajaService->revertir($baja);

        return back()->with('success', 'Baja revertida: el ejemplar volvió al rebaño.');
    }
}
