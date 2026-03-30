<?php

namespace App\Http\Controllers;

use App\Models\Cria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CriaController extends Controller
{
    // GET /reproduccion/crias/{cria}
    // Redirige a la ficha del animal vinculado si existe
    public function show(Cria $cria): RedirectResponse
    {
        $cria->load('animal');

        if ($cria->animal_id) {
            return redirect()->route('animales.show', $cria->animal_id);
        }

        return redirect()->route('reproduccion.index')
            ->with('info', 'Esta cría no tiene animal vinculado — nació muerta');
    }

    // PATCH /reproduccion/crias/{cria}/asignar-arete
    // Asigna arete definitivo a una cría que solo tenía arete temporal
    public function asignarArete(Request $request, Cria $cria): RedirectResponse
    {
        $datos = $request->validate([
            'arete' => 'required|string|max:100|unique:animals,arete',
        ]);

        if (!$cria->animal_id) {
            return redirect()->back()
                ->withErrors(['arete' => 'Esta cría no tiene animal asociado — nació muerta']);
        }

        try {
            DB::beginTransaction();

            $cria->animal->update(['arete' => $datos['arete']]);
            $cria->update(['arete_temporal' => null]);

            DB::commit();

            return redirect()->back()
                ->with('success', 'Arete asignado correctamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error al asignar el arete: ' . $e->getMessage());
        }
    }
}