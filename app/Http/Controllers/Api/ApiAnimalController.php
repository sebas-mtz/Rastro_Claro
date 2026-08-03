<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use Illuminate\Http\Request;

class ApiAnimalController extends Controller
{
    public function index(Request $request)
    {
        try {
            $animales = Animal::with('lote')
                ->when($request->search, function($q) use ($request) {
                    $q->where('arete', 'like', "%{$request->search}%")
                      ->orWhere('alias', 'like', "%{$request->search}%");
                })
                ->orderBy('created_at', 'desc')
                ->get();

            $result = [];
            foreach ($animales as $a) {
                $result[] = [
                    'id'                => $a->id,
                    'arete'             => $a->arete ?? '',
                    'alias'             => $a->alias ?? '',
                    'especie'           => $a->especie ?? 'Ovino',
                    'raza'              => $a->raza ?? '',
                    'sexo'              => $a->sexo ?? '',
                    'fecha_nac'         => $a->fecha_nac ?? '',
                    'peso'              => $a->peso ?? 0,
                    'estado_productivo' => $a->estado_productivo ?? '',
                    'lote_id'           => $a->lote_id ?? null,
                    'lote'              => $a->lote ? $a->lote->nombre : '',
                ];
            }

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'especie'           => 'required|string',
            'alias'             => 'nullable|string|max:255',
            'raza'              => 'nullable|string|max:255',
            'arete'             => 'required|string',
            'sexo'              => 'required|in:M,F',
            'fecha_nac'         => 'nullable|date',
            'peso'              => 'nullable|numeric',
            'estado_productivo' => 'nullable|string',
            'lote_id'           => 'nullable|exists:lotes,id',
        ]);

        if (Animal::where('arete', $request->arete)->exists()) {
            return response()->json(['message' => 'Ya existe un animal con este arete'], 422);
        }

        $animal = Animal::create($validated);

        return response()->json([
            'message' => 'Animal registrado correctamente',
            'animal'  => $animal,
        ], 201);
    }

    public function show(Animal $animal)
    {
        $animal->load('lote');
        return response()->json([
            'id'                => $animal->id,
            'arete'             => $animal->arete ?? '',
            'alias'             => $animal->alias ?? '',
            'especie'           => $animal->especie ?? 'Ovino',
            'raza'              => $animal->raza ?? '',
            'sexo'              => $animal->sexo ?? '',
            'fecha_nac'         => $animal->fecha_nac ?? '',
            'peso'              => $animal->peso ?? 0,
            'estado_productivo' => $animal->estado_productivo ?? '',
            'lote'              => $animal->lote ? $animal->lote->nombre : '',
        ]);
    }

    public function update(Request $request, Animal $animal)
    {
        $validated = $request->validate([
            'especie'           => 'sometimes|string',
            'alias'             => 'nullable|string|max:255',
            'raza'              => 'nullable|string|max:255',
            'arete'             => 'sometimes|string',
            'sexo'              => 'sometimes|in:M,F',
            'fecha_nac'         => 'nullable|date',
            'peso'              => 'nullable|numeric',
            'estado_productivo' => 'nullable|string',
            'lote_id'           => 'nullable|exists:lotes,id',
        ]);

        $animal->update($validated);

        return response()->json([
            'message' => 'Animal actualizado',
            'animal'  => $animal,
        ]);
    }

    public function destroy(Animal $animal)
    {
        $animal->delete();
        return response()->json(['message' => 'Animal eliminado']);
    }
}