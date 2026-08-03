<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use Illuminate\Http\Request;

class ApiVentaController extends Controller
{
    public function index(Request $request)
    {
        $ventas = Venta::with('comprador')
            ->when($request->estado, fn($q) => $q->where('estado_venta', $request->estado))
            ->orderBy('fecha_venta', 'desc')
            ->get()
            ->map(fn($v) => [
                'id'         => $v->id,
                'fecha'      => $v->fecha_venta,
                'comprador'  => $v->comprador?->nombre ?? $v->comprador_nombre ?? '',
                'animales'   => $v->cantidad ?? 0,
                'peso_total' => $v->peso_total ?? 0,
                'precio_kg'  => $v->precio_kg ?? 0,
                'total'      => $v->total ?? 0,
                'estado'     => $v->estado_venta ?? 'Pendiente',
                'tipo'       => $v->tipo_venta ?? 'Canal',
            ]);

        return response()->json($ventas);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'comprador_nombre' => 'required|string',
            'fecha_venta'      => 'required|date',
            'cantidad'         => 'required|integer',
            'peso_total'       => 'nullable|numeric',
            'precio_kg'        => 'nullable|numeric',
            'total'            => 'nullable|numeric',
            'tipo_venta'       => 'nullable|string',
            'estado_venta'     => 'nullable|string',
        ]);

        $venta = Venta::create($validated);

        return response()->json([
            'message' => 'Venta registrada',
            'venta'   => $venta,
        ], 201);
    }

    public function update(Request $request, Venta $venta)
    {
        $validated = $request->validate([
            'estado_venta' => 'sometimes|string',
            'total'        => 'sometimes|numeric',
        ]);

        $venta->update($validated);

        return response()->json([
            'message' => 'Venta actualizada',
            'venta'   => $venta,
        ]);
    }

    public function destroy(Venta $venta)
    {
        $venta->delete();
        return response()->json(['message' => 'Venta eliminada']);
    }
}