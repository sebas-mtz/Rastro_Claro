<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventoSalud;
use App\Models\Animal;
use Illuminate\Http\Request;

class ApiSaludController extends Controller
{
    public function index(Request $request)
    {
        $eventos = EventoSalud::with('animal')
            ->when($request->animal_id, fn($q) => $q->where('animal_id', $request->animal_id))
            ->when($request->tipo,      fn($q) => $q->where('tipo', $request->tipo))
            ->when($request->estado,    fn($q) => $q->where('estado', $request->estado))
            ->orderBy('fecha', 'desc')
            ->get()
            ->map(fn($e) => [
                'id'           => $e->id,
                'animal_id'    => $e->animal_id,
                'arete'        => $e->animal?->arete,
                'alias'        => $e->animal?->alias,
                'tipo'         => $e->tipo,
                'descripcion'  => $e->descripcion,
                'fecha'        => $e->fecha,
                'veterinario'  => $e->veterinario ?? '',
                'estado'       => $e->estado ?? 'Pendiente',
                'costo'        => $e->costo ?? 0,
            ]);

        return response()->json($eventos);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'animal_id'   => 'required|exists:animals,id',
            'tipo'        => 'required|string',
            'descripcion' => 'required|string',
            'fecha'       => 'required|date',
            'veterinario' => 'nullable|string',
            'estado'      => 'nullable|string',
            'costo'       => 'nullable|numeric',
        ]);

        $evento = EventoSalud::create($validated);
        $evento->load('animal');

        return response()->json([
            'message' => 'Evento de salud registrado',
            'evento'  => $evento,
        ], 201);
    }

    public function storeMasivo(Request $request)
    {
        $validated = $request->validate([
            'lote_id'     => 'required|exists:lotes,id',
            'tipo'        => 'required|string',
            'descripcion' => 'required|string',
            'fecha'       => 'required|date',
            'veterinario' => 'nullable|string',
            'estado'      => 'nullable|string',
        ]);

        $animales = Animal::where('lote_id', $validated['lote_id'])->get();

        if ($animales->isEmpty()) {
            return response()->json(['message' => 'No hay borregas en este lote'], 422);
        }

        $eventos = [];
        foreach ($animales as $animal) {
            $eventos[] = EventoSalud::create([
                'animal_id'   => $animal->id,
                'tipo'        => $validated['tipo'],
                'descripcion' => $validated['descripcion'],
                'fecha'       => $validated['fecha'],
                'veterinario' => $validated['veterinario'] ?? 'Sin asignar',
                'estado'      => $validated['estado'] ?? 'Pendiente',
            ]);
        }

        return response()->json([
            'message' => 'Evento aplicado a ' . count($eventos) . ' borregas del lote',
            'total'   => count($eventos),
        ], 201);
    }

    public function show(EventoSalud $eventoSalud)
    {
        $eventoSalud->load('animal');
        return response()->json($eventoSalud);
    }

    public function update(Request $request, EventoSalud $eventoSalud)
    {
        $validated = $request->validate([
            'tipo'        => 'sometimes|string',
            'descripcion' => 'sometimes|string',
            'fecha'       => 'sometimes|date',
            'veterinario' => 'nullable|string',
            'estado'      => 'nullable|string',
            'costo'       => 'nullable|numeric',
        ]);

        $eventoSalud->update($validated);

        return response()->json([
            'message' => 'Evento actualizado',
            'evento'  => $eventoSalud,
        ]);
    }

    public function destroy(EventoSalud $eventoSalud)
    {
        $eventoSalud->delete();
        return response()->json(['message' => 'Evento eliminado']);
    }
}