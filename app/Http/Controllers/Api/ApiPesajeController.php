<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pesaje;
use App\Models\Animal;
use Illuminate\Http\Request;

class ApiPesajeController extends Controller
{
    public function index(Request $request)
    {
        $pesajes = Pesaje::with('animal')
            ->when($request->animal_id, fn($q) => $q->where('animal_id', $request->animal_id))
            ->orderBy('fecha', 'desc')
            ->get()
            ->map(fn($p) => [
                'id'             => $p->id,
                'animal_id'      => $p->animal_id,
                'arete'          => $p->animal?->arete,
                'alias'          => $p->animal?->alias,
                'peso'           => $p->peso,
                'fecha'          => $p->fecha,
                'lote'           => $p->animal?->lote?->nombre,
                'observaciones'  => $p->observaciones ?? '',
            ]);

        return response()->json($pesajes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'animal_id'     => 'required|exists:animals,id',
            'peso'          => 'required|numeric',
            'fecha'         => 'required|date',
            'observaciones' => 'nullable|string',
        ]);

        $pesaje = Pesaje::create($validated);
        $pesaje->load('animal');

        return response()->json([
            'message' => 'Pesaje registrado correctamente',
            'pesaje'  => $pesaje,
        ], 201);
    }

    public function show(Pesaje $pesaje)
    {
        $pesaje->load('animal');
        return response()->json($pesaje);
    }

    public function destroy(Pesaje $pesaje)
    {
        $pesaje->delete();
        return response()->json(['message' => 'Pesaje eliminado']);
    }
}