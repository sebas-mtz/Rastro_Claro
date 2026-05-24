<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produccion;
use App\Models\Animal;
use App\Services\HaciendaService;
use Inertia\Inertia;

class ProduccionController extends Controller
{
    protected $haciendaService;

    public function __construct(HaciendaService $haciendaService)
    {
        $this->haciendaService = $haciendaService;
    }

    public function index(Request $request)
    {
        $producciones = $this->haciendaService->getProduccionesPaginated($request);
        $stats = $this->haciendaService->getProduccionStatistics();
        $ingresos = $this->haciendaService->getProduccionIngresos();

        // ✅ CORRECCIÓN: Combina ingresos con resumen
        $resumen = array_merge($stats['resumen'], [
            'ingresos' => $ingresos // 👈 Mantener ingresos separados
        ]);

        return Inertia::render('Producciones/Index', [
            'producciones' => $producciones,
            'datos' => $stats['datos'],
            'mejores' => $stats['mejores'],
            'tendencias' => $stats['tendencias'],
            'resumen' => $resumen,
            // ✅ AGREGAR: Inventario de subproductos para el frontend
            'inventarioSubproductos' => $this->haciendaService->getInventarioSubproductos(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Producciones/Create', [
            'animales' => $this->haciendaService->getAvailableAnimals(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'animal_id' => 'required|exists:animals,id',
            'fecha' => 'required|date',
            'tipo' => 'required|in:leche,lana,huevo',
            'valor' => 'required|numeric|min:0',
            'unidad' => 'nullable|string|max:50',
        ]);

        // ✅ AGREGAR: Unidad por defecto según el tipo
        if (empty($validated['unidad'])) {
            $validated['unidad'] = match($validated['tipo']) {
                'leche' => 'litros',
                'huevo' => 'unidades', 
                'lana' => 'kg',
                default => 'unidades'
            };
        }

        Produccion::create($validated);

        return redirect()->route('producciones.index')->with([
            'message' => 'Registro de producción creado exitosamente',
            'type' => 'success'
        ]);
    }

    public function show(Produccion $produccion)
    {
        $produccion->load('animal.lote');
        return Inertia::render('Producciones/Show', compact('produccion'));
    }

    public function edit(Produccion $produccion)
    {
        return Inertia::render('Producciones/Edit', [
            'produccion' => $produccion,
            'animales' => $this->haciendaService->getAvailableAnimals(),
        ]);
    }

    public function update(Request $request, Produccion $produccion)
    {
        $validated = $request->validate([
            'animal_id' => 'required|exists:animals,id',
            'fecha' => 'required|date',
            'tipo' => 'required|in:leche,lana,huevo',
            'valor' => 'required|numeric|min:0',
            'unidad' => 'nullable|string|max:50',
        ]);

        // ✅ AGREGAR: Unidad por defecto si está vacía
        if (empty($validated['unidad'])) {
            $validated['unidad'] = match($validated['tipo']) {
                'leche' => 'litros',
                'huevo' => 'unidades',
                'lana' => 'kg',
                default => 'unidades'
            };
        }

        $produccion->update($validated);

        return redirect()->route('producciones.index')->with([
            'message' => 'Registro actualizado correctamente',
            'type' => 'success'
        ]);
    }

    public function destroy(Produccion $produccion)
    {
        $produccion->delete();

        return redirect()->route('producciones.index')->with([
            'message' => 'Registro de producción eliminado exitosamente',
            'type' => 'success'
        ]);
    }

    // ✅ AGREGAR: Método para obtener producciones de un animal (para el modal)
    public function getProduccionesAnimal(Animal $animal)
    {
        $producciones = Produccion::where('animal_id', $animal->id)
            ->orderBy('fecha', 'desc')
            ->get()
            ->map(function ($produccion) {
                return [
                    'id' => $produccion->id,
                    'fecha' => $produccion->fecha,
                    'tipo' => $produccion->tipo,
                    'valor' => $produccion->valor,
                    'unidad' => $produccion->unidad,
                    'created_at' => $produccion->created_at,
                ];
            });

        return response()->json($producciones);
    }
}