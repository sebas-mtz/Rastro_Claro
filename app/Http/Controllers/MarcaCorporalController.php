<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\MarcaCorporal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Marcas sobre el cuerpo del ejemplar.
 *
 * El aislamiento por rancho lo aplica el scope global de los modelos, así que
 * un ejemplar de otra cuenta ni siquiera se resuelve en la ruta.
 */
class MarcaCorporalController extends Controller
{
    public function store(Request $request, Animal $animal)
    {
        $validated = $request->validate([
            'zona' => ['required', Rule::in(array_keys(MarcaCorporal::ZONAS))],
            'tipo' => ['required', Rule::in(array_keys(MarcaCorporal::TIPOS))],
            'descripcion' => 'nullable|string|max:1000',
            'fecha' => 'required|date|before_or_equal:today',
        ], [
            'fecha.before_or_equal' => 'La fecha no puede estar en el futuro.',
        ]);

        // Una marca no puede ser anterior al nacimiento del ejemplar.
        $nacimiento = \App\Services\AnimalValuationService::fechaNacimiento($animal);

        if ($nacimiento && $nacimiento->gt($validated['fecha'])) {
            return back()->withErrors([
                'fecha' => 'La fecha no puede ser anterior al nacimiento del ejemplar.',
            ])->withInput();
        }

        $animal->marcasCorporales()->create(array_merge($validated, [
            'estado' => MarcaCorporal::ACTIVA,
            'registrado_por' => Auth::id(),
        ]));

        return back()->with('success', 'Marca registrada sobre el cuerpo del ejemplar.');
    }

    /**
     * Da por resuelta una marca, o la reabre.
     *
     * No se borra: que una lesión sanara es parte del historial del animal.
     */
    public function resolver(Request $request, MarcaCorporal $marca)
    {
        $validated = $request->validate([
            'estado' => ['required', Rule::in(array_keys(MarcaCorporal::ESTADOS))],
            'fecha_resolucion' => 'nullable|date|before_or_equal:today',
        ]);

        $resuelta = $validated['estado'] === MarcaCorporal::RESUELTA;

        $marca->update([
            'estado' => $validated['estado'],
            'fecha_resolucion' => $resuelta
                ? ($validated['fecha_resolucion'] ?? now()->toDateString())
                : null,
        ]);

        return back()->with(
            'success',
            $resuelta ? 'Marca marcada como resuelta.' : 'Marca reabierta.'
        );
    }

    /**
     * Borrado físico. Solo para corregir una captura equivocada del mismo día:
     * pasado ese punto la marca ya es historial y se resuelve, no se borra.
     */
    public function destroy(MarcaCorporal $marca)
    {
        if (! $marca->created_at?->isToday()) {
            return back()->withErrors([
                'marca' => 'Solo se puede borrar una marca el mismo día en que se capturó. '
                    . 'Si ya no aplica, márcala como resuelta para conservar el historial.',
            ]);
        }

        $marca->delete();

        return back()->with('success', 'Marca eliminada.');
    }
}
