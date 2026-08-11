<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Muerte;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MuerteController extends Controller
{
    public function store(Request $request, Animal $animal): RedirectResponse
    {
        $datos = $request->validate([
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'causa' => ['required', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($animal->muerte || $animal->estado_productivo === 'muerto') {
            return back()->withErrors([
                'animal' => 'La muerte de este animal ya fue registrada.',
            ]);
        }

        DB::transaction(function () use ($animal, $datos) {
            Muerte::create([
                'animal_id' => $animal->id,
                ...$datos,
            ]);

            $animal->update(['estado_productivo' => 'muerto']);
        });

        return back()->with('success', 'Muerte registrada correctamente.');
    }
}
