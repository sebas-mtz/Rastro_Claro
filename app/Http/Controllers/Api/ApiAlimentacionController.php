<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventarioInsumo;
use App\Models\Racion;
use Illuminate\Http\Request;

class ApiAlimentacionController extends Controller
{
    // ── Inventario ────────────────────────────────────────────────────────
    public function inventario(Request $request)
    {
        $items = InventarioInsumo::orderBy('nombre')
            ->get()
            ->map(fn($i) => [
                'id'         => $i->id,
                'nombre'     => $i->nombre,
                'cantidad'   => $i->cantidad,
                'unidad'     => $i->unidad,
                'minimo'     => $i->minimo ?? 0,
                'categoria'  => $i->categoria ?? 'General',
                'proveedor'  => $i->proveedor ?? '',
                'bajo_stock' => $i->cantidad <= ($i->minimo ?? 0),
            ]);

        return response()->json($items);
    }

    public function storeInventario(Request $request)
    {
        $validated = $request->validate([
            'nombre'    => 'required|string|max:255',
            'cantidad'  => 'required|numeric',
            'unidad'    => 'required|string',
            'minimo'    => 'nullable|numeric',
            'categoria' => 'nullable|string',
            'proveedor' => 'nullable|string',
        ]);

        $item = InventarioInsumo::create($validated);

        return response()->json([
            'message' => 'Producto agregado al inventario',
            'item'    => $item,
        ], 201);
    }

    public function updateInventario(Request $request, InventarioInsumo $inventarioInsumo)
    {
        $validated = $request->validate([
            'nombre'    => 'sometimes|string|max:255',
            'cantidad'  => 'sometimes|numeric',
            'unidad'    => 'sometimes|string',
            'minimo'    => 'nullable|numeric',
            'categoria' => 'nullable|string',
            'proveedor' => 'nullable|string',
        ]);

        $inventarioInsumo->update($validated);

        return response()->json([
            'message' => 'Producto actualizado',
            'item'    => $inventarioInsumo,
        ]);
    }

    // ── Raciones ─────────────────────────────────────────────────────────
    public function raciones(Request $request)
    {
        $raciones = Racion::with('lote')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($r) => [
                'id'       => $r->id,
                'lote'     => $r->lote?->nombre ?? '',
                'lote_id'  => $r->lote_id,
                'tipo'     => $r->tipo ?? '',
                'alimento' => $r->alimento ?? '',
                'cantidad' => $r->cantidad,
                'unidad'   => $r->unidad ?? 'kg/día',
                'hora'     => $r->hora ?? '',
            ]);

        return response()->json($raciones);
    }

    public function storeRacion(Request $request)
    {
        $validated = $request->validate([
            'lote_id'  => 'required|exists:lotes,id',
            'tipo'     => 'nullable|string',
            'alimento' => 'required|string',
            'cantidad' => 'required|numeric',
            'unidad'   => 'nullable|string',
            'hora'     => 'nullable|string',
        ]);

        $racion = Racion::create($validated);

        return response()->json([
            'message' => 'Ración registrada',
            'racion'  => $racion,
        ], 201);
    }
}