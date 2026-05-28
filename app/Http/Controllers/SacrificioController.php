<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sacrificio;
use App\Services\HaciendaService;
use Inertia\Inertia;
use App\Models\Animal;

class SacrificioController extends Controller
{
    protected $haciendaService;

    public function __construct(HaciendaService $haciendaService)
    {
        $this->haciendaService = $haciendaService;
    }

    public function index(Request $request)
    {
        $sacrificios = $this->haciendaService->getSacrificiosPaginated($request);
        $estadisticas = $this->haciendaService->getSacrificioStatistics();

        return Inertia::render('Sacrificios/Index', [
            'sacrificios' => $sacrificios,
            'estadisticas' => $estadisticas,
            'animales' => $this->haciendaService->getAvailableAnimals(),
            'lotes' => $this->haciendaService->getLotes(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Sacrificios/Create', [
            'animales' => $this->haciendaService->getAvailableAnimals(),
            'lotes' => $this->haciendaService->getLotes(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'animal_id' => 'required|exists:animals,id',
            'lote_id' => 'nullable|exists:lotes,id',
            'fecha' => 'required|date',
            'motivo' => 'required|in:descarte,enfermedad,accidente,autoconsumo',
            'peso_vivo' => 'required|numeric|min:0.1',
            'peso_canal' => 'required|numeric|min:0.1',
            'cuero' => 'boolean',
            'grasa' => 'boolean',
            'visceras' => 'boolean',
            'plumas' => 'boolean',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $validated['rendimiento'] = ($validated['peso_canal'] / $validated['peso_vivo']) * 100;

        $animal = Animal::find($validated['animal_id']);
        if (in_array($animal->especie, ['Gallos', 'Aves de corral (gallinas y pollitos)']) &&
            !isset($validated['plumas'])) {
            $validated['plumas'] = true;
        }

        $sacrificio = Sacrificio::create($validated);

        $animal->update(['estado_productivo' => 'sacrificado']);

        return redirect()->route('sacrificios.index')->with([
            'message' => 'Sacrificio registrado exitosamente',
            'type' => 'success'
        ]);
    }

    public function show(Sacrificio $sacrificio)
    {
        $sacrificio->load(['animal', 'lote']);
        return Inertia::render('Sacrificios/Show', [
            'sacrificio' => $sacrificio,
        ]);
    }

    public function edit(Sacrificio $sacrificio)
    {
        $sacrificio->load(['animal', 'lote']);

        return Inertia::render('Sacrificios/Edit', [
            'sacrificio' => $sacrificio,
            'animales' => $this->haciendaService->getAvailableAnimals(),
            'lotes' => $this->haciendaService->getLotes(),
        ]);
    }

    public function update(Request $request, Sacrificio $sacrificio)
    {
        $validated = $request->validate([
            // Validaciones originales...
        ]);

        $validated['rendimiento'] = ($validated['peso_canal'] / $validated['peso_vivo']) * 100;

        $sacrificio->update($validated);

        return redirect()->route('sacrificios.index')->with([
            'message' => 'Sacrificio actualizado exitosamente',
            'type' => 'success'
        ]);
    }

    public function destroy(Sacrificio $sacrificio)
    {
        $sacrificio->delete();

        return redirect()->route('sacrificios.index')->with([
            'message' => 'Sacrificio eliminado exitosamente',
            'type' => 'success'
        ]);
    }

    // Métodos API
    public function porMotivo($motivo)
    {
        // Lógica original...
    }

    public function estadisticas()
    {
        return response()->json($this->haciendaService->getSacrificioStatistics());
    }

    public function tendencias()
    {
        return response()->json($this->haciendaService->getSacrificioStatistics()['tendencias'] ?? []); // Ajusta si necesitas
    }
}