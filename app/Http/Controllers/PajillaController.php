<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Pajilla;
use App\Models\Termo;
use App\Models\DonadorExterno;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PajillaController extends Controller
{
    public function index()
    {
        $pajillas = Pajilla::with(['termo', 'animal','donadorExterno'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Pajillas/Index', [
            'pajillas' => $pajillas,
        ]);
    }

    public function create()
    {
        return Inertia::render('Pajillas/Create', [
            'termos' => Termo::where('estado', 'activo')->get(),
           'animales' => Animal::where('sexo', 'M')
        ->orderBy('arete')
        ->get(),
        'donadoresExternos' => DonadorExterno::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'termo_id' => ['required', 'exists:termos,id'],
            'animal_id' => ['nullable', 'exists:animals,id'],
            'codigo' => ['required', 'string', 'max:50', 'unique:pajillas,codigo'],
            'lote' => ['nullable', 'string', 'max:100'],
            'fecha_ingreso' => ['nullable', 'date'],
            'fecha_vencimiento' => ['nullable', 'date', 'after_or_equal:fecha_ingreso'],
            'fecha_utilizacion' => ['nullable', 'date'],
            'estado' => ['required', 'in:disponible,utilizada,dañada,vencida'],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'donador_externo_id' => ['nullable', 'exists:donadores_externos,id'],
        ]);

        Pajilla::create($data);

        return redirect()
            ->route('genetica.index')
            ->with('success', 'Pajilla registrada correctamente.');
    }

    public function show(Pajilla $pajilla)
    {
        $pajilla->load(['termo', 'animal', 'donadorExterno']);

        return Inertia::render('Pajillas/Show', [
            'pajilla' => $pajilla,
        ]);
    }

    public function edit(Pajilla $pajilla)
    {
        return Inertia::render('Pajillas/Edit', [
            'pajilla' => $pajilla,
            'termos' => Termo::where('estado', 'activo')->get(),
        'animales' => Animal::where('sexo', 'M')->orderBy('arete')->get(),
'donadoresExternos' => DonadorExterno::orderBy('nombre')->get(),    
]);
    }

    public function update(Request $request, Pajilla $pajilla)
    {
        $data = $request->validate([
            'termo_id' => ['required', 'exists:termos,id'],
            'animal_id' => ['nullable', 'exists:animals,id'],
            'codigo' => ['required', 'string', 'max:50', 'unique:pajillas,codigo,' . $pajilla->id],
            'lote' => ['nullable', 'string', 'max:100'],
            'fecha_ingreso' => ['nullable', 'date'],
            'fecha_vencimiento' => ['nullable', 'date', 'after_or_equal:fecha_ingreso'],
            'fecha_utilizacion' => ['nullable', 'date'],
            'estado' => ['required', 'in:disponible,utilizada,dañada,vencida'],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'donador_externo_id' => ['nullable', 'exists:donadores_externos,id'],
        ]);

        if ($data['estado'] === 'utilizada' && empty($data['fecha_utilizacion'])) {
            $data['fecha_utilizacion'] = now();
        }

        $pajilla->update($data);

        return redirect()
            ->route('genetica.index')
            ->with('success', 'Pajilla actualizada correctamente.');
    }

    public function destroy(Pajilla $pajilla)
    {
        $pajilla->delete();

        return redirect()
            ->route('genetica.index')
            ->with('success', 'Pajilla eliminada correctamente.');
    }
}