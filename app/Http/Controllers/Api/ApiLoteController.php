<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lote;
use App\Models\Animal;
use Illuminate\Http\Request;

class ApiLoteController extends Controller
{
    public function index()
    {
        $lotes = Lote::withCount('animales')
            ->get()
            ->map(fn($l) => [
                'id'          => $l->id,
                'nombre'      => $l->nombre,
                'descripcion' => $l->descripcion ?? '',
                'total'       => $l->animales_count,
                'especie'     => $l->especie ?? 'Ovino',
            ]);

        return response()->json($lotes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'      => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'especie'     => 'nullable|string',
        ]);

        $lote = Lote::create($validated);

        return response()->json([
            'message' => 'Lote creado correctamente',
            'lote'    => $lote,
        ], 201);
    }

    public function show(Lote $lote)
    {
        $lote->load('animales');
        return response()->json([
            'id'          => $lote->id,
            'nombre'      => $lote->nombre,
            'descripcion' => $lote->descripcion,
            'animales'    => $lote->animales->map(fn($a) => [
                'id'                => $a->id,
                'arete'             => $a->arete,
                'alias'             => $a->alias,
                'raza'              => $a->raza,
                'sexo'              => $a->sexo,
                'peso'              => $a->peso,
                'estado_productivo' => $a->estado_productivo,
            ]),
        ]);
    }

    public function update(Request $request, Lote $lote)
    {
        $validated = $request->validate([
            'nombre'      => 'sometimes|string|max:255',
            'descripcion' => 'nullable|string',
            'especie'     => 'nullable|string',
        ]);

        $lote->update($validated);

        return response()->json([
            'message' => 'Lote actualizado',
            'lote'    => $lote,
        ]);
    }

    public function destroy(Lote $lote)
    {
        $lote->delete();
        return response()->json(['message' => 'Lote eliminado']);
    }
}