<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faena;
use Illuminate\Http\Request;

class ApiCostoController extends Controller
{
    public function index(Request $request)
    {
        $costos = Faena::when($request->categoria, fn($q) => $q->where('categoria', $request->categoria))
            ->orderBy('fecha', 'desc')
            ->get()
            ->map(fn($c) => [
                'id'           => $c->id,
                'categoria'    => $c->categoria ?? 'General',
                'descripcion'  => $c->descripcion ?? $c->nombre ?? '',
                'monto'        => $c->monto ?? $c->costo ?? 0,
                'fecha'        => $c->fecha,
                'responsable'  => $c->responsable ?? '',
                'comprobante'  => $c->comprobante ?? '',
            ]);

        return response()->json($costos);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'categoria'   => 'required|string',
            'descripcion' => 'required|string',
            'monto'       => 'required|numeric',
            'fecha'       => 'required|date',
            'responsable' => 'nullable|string',
            'comprobante' => 'nullable|string',
        ]);

        $costo = Faena::create($validated);

        return response()->json([
            'message' => 'Costo registrado',
            'costo'   => $costo,
        ], 201);
    }

    public function update(Request $request, Faena $faena)
    {
        $validated = $request->validate([
            'categoria'   => 'sometimes|string',
            'descripcion' => 'sometimes|string',
            'monto'       => 'sometimes|numeric',
            'fecha'       => 'sometimes|date',
            'responsable' => 'nullable|string',
            'comprobante' => 'nullable|string',
        ]);

        $faena->update($validated);

        return response()->json([
            'message' => 'Costo actualizado',
            'costo'   => $faena,
        ]);
    }

    public function destroy(Faena $faena)
    {
        $faena->delete();
        return response()->json(['message' => 'Costo eliminado']);
    }
}