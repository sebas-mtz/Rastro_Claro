<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sacrificio;
use Illuminate\Http\Request;

class ApiSacrificiController extends Controller
{
    public function index(Request $request)
    {
        $sacrificios = Sacrificio::with('animal')
            ->orderBy('fecha', 'desc')
            ->get()
            ->map(fn($s) => [
                'id'           => $s->id,
                'fecha'        => $s->fecha,
                'arete'        => $s->animal?->arete,
                'alias'        => $s->animal?->alias,
                'animal_id'    => $s->animal_id,
                'peso_vivo'    => $s->peso_vivo ?? 0,
                'peso_canal'   => $s->peso_canal ?? 0,
                'rendimiento'  => $s->rendimiento ?? 0,
                'destino'      => $s->destino ?? '',
                'motivo'       => $s->motivo ?? 'Venta',
                'precio_kg'    => $s->precio_kg ?? 0,
                'total'        => $s->total ?? 0,
            ]);

        return response()->json($sacrificios);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'animal_id'  => 'required|exists:animals,id',
            'fecha'      => 'required|date',
            'peso_vivo'  => 'required|numeric',
            'peso_canal' => 'nullable|numeric',
            'destino'    => 'nullable|string',
            'motivo'     => 'nullable|string',
            'precio_kg'  => 'nullable|numeric',
            'total'      => 'nullable|numeric',
        ]);

        // Calcular rendimiento automático
        if (!empty($validated['peso_canal'])) {
            $validated['rendimiento'] = round(
                ($validated['peso_canal'] / $validated['peso_vivo']) * 100, 1
            );
        }

        $sacrificio = Sacrificio::create($validated);
        $sacrificio->load('animal');

        return response()->json([
            'message'    => 'Sacrificio registrado',
            'sacrificio' => $sacrificio,
        ], 201);
    }

    public function show(Sacrificio $sacrificio)
    {
        $sacrificio->load('animal');
        return response()->json($sacrificio);
    }

    public function destroy(Sacrificio $sacrificio)
    {
        $sacrificio->delete();
        return response()->json(['message' => 'Sacrificio eliminado']);
    }
}