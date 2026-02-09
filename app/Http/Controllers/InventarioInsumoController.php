<?php

namespace App\Http\Controllers;

use App\Models\InventarioInsumo;
use Illuminate\Http\Request;

class InventarioInsumoController extends Controller
{
    // Crear NUEVO alimento en inventario
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'         => ['required', 'string', 'max:255'],
            'tipo'           => ['nullable', 'string', 'max:255'],
            'existencias'    => ['required', 'numeric', 'min:0'],
            'unidad'         => ['nullable', 'string', 'max:50'],
            'costo_promedio' => ['nullable', 'numeric', 'min:0'],
        ]);

        InventarioInsumo::create($data);

        return back()->with('success', 'Alimento agregado al inventario.');
    }

    // Reabastecer: sumar cantidad a existencias
    public function reabastecer(Request $request, InventarioInsumo $item)
    {
        $data = $request->validate([
            'cantidad' => ['required', 'numeric', 'min:0'],
        ]);

        $item->existencias += $data['cantidad'];
        $item->save();

        return back()->with('success', 'Inventario actualizado correctamente.');
    }
}
