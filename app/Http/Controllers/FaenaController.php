<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Faena;
use App\Services\HaciendaService;
use Inertia\Inertia;
use App\Models\Animal;

class FaenaController extends Controller
{
    protected $haciendaService;

    public function __construct(HaciendaService $haciendaService)
    {
        $this->haciendaService = $haciendaService;
    }

    public function index(Request $request)
    {
        $faenas = $this->haciendaService->getFaenasPaginated($request);
        $estadisticas = $this->haciendaService->getFaenaStatistics();

        return Inertia::render('Faenas/Index', [
            'faenas' => $faenas,
            'estadisticas' => $estadisticas,
            'animales' => $this->haciendaService->getAvailableAnimals(),
            'lotes' => $this->haciendaService->getLotes(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Faenas/Create', [
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
            'tipo_corte' => 'required|in:completo,media,cortes,deshuesado',
            'peso_canal' => 'required|numeric|min:0.1',
            'peso_carne' => 'required|numeric|min:0.1',
            'peso_cuero' => 'nullable|numeric|min:0',
            'peso_grasa' => 'nullable|numeric|min:0',
            'peso_plumas' => 'nullable|numeric|min:0',
            'peso_hueso' => 'nullable|numeric|min:0',
            'peso_visceras' => 'nullable|numeric|min:0',
            'observaciones' => 'nullable|string|max:500',
        ]);

        if (empty($validated['peso_plumas'])) {
            $animal = Animal::find($validated['animal_id']);
            if ($animal && in_array($animal->especie, ['Gallos', 'Aves de corral (gallinas y pollitos)', 'Ave', 'Pollo', 'Gallina'])) {
                $validated['peso_plumas'] = round($validated['peso_canal'] * 0.06, 2);
            }
        }

        $validated['rendimiento'] = round(($validated['peso_carne'] / $validated['peso_canal']) * 100, 2);

        Faena::create($validated);

        $animal = Animal::find($validated['animal_id']);
        $animal->estado_productivo = 'faeneado';
        $animal->save();

        return redirect()->route('faenas.index')->with([
            'message' => 'Faena registrada exitosamente',
            'type' => 'success'
        ]);
    }

    public function show(Faena $faena)
    {
        $faena->load(['animal', 'lote']);
        return Inertia::render('Faenas/Show', [
            'faena' => $faena, // Simplificado, ajusta si necesitas map
        ]);
    }

    public function edit(Faena $faena)
    {
        $faena->load(['animal', 'lote']);

        return Inertia::render('Faenas/Edit', [
            'faena' => $faena,
            'animales' => $this->haciendaService->getAvailableAnimals(),
            'lotes' => $this->haciendaService->getLotes(),
        ]);
    }

    public function update(Request $request, Faena $faena)
    {
        $validated = $request->validate([
            // Validaciones originales...
        ]);

        $validated['rendimiento'] = round(($validated['peso_carne'] / $validated['peso_canal']) * 100, 2);

        $faena->update($validated);

        return redirect()->route('faenas.index')->with([
            'message' => 'Faena actualizada exitosamente',
            'type' => 'success'
        ]);
    }

    public function destroy(Faena $faena)
    {
        $faena->delete();

        return redirect()->route('faenas.index')->with([
            'message' => 'Faena eliminada exitosamente',
            'type' => 'success'
        ]);
    }

    // Métodos API: Puedes moverlos al service si los usas mucho, pero por ahora quédate con ellos aquí.
    public function porLote($loteId)
    {
        // Lógica original...
    }

    public function estadisticas()
    {
        return response()->json($this->haciendaService->getFaenaStatistics());
    }
}