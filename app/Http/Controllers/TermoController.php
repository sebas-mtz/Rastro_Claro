<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\DonadorExterno;
use App\Models\Pajilla;
use App\Models\Termo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TermoController extends Controller
{
    public function index()
    {
        $termos = Termo::with([
                'pajillas.animal',
                'pajillas.donadorExterno',
            ])
            ->withCount([
                'pajillas',
                'pajillasDisponibles',
            ])
            ->latest()
            ->paginate(10);

        $pajillas = Pajilla::with([
                'termo',
                'animal',
                'donadorExterno',
            ])
            ->latest()
            ->paginate(10);

        $animales = Animal::where('sexo', 'M')
            ->orderBy('arete')
            ->get();

        $donadoresExternos = DonadorExterno::orderBy('nombre')
            ->get();

        return Inertia::render('Genetica/Index', [
            'termos' => $termos,
            'pajillas' => $pajillas,
            'animales' => $animales,
            'donadoresExternos' => $donadoresExternos,
            'activeTab' => 'termos',

            'stats' => [
                'termos_activos' => Termo::where('estado', 'activo')->count(),
                'pajillas_disponibles' => Pajilla::where('estado', 'disponible')->count(),
                'pajillas_vencidas' => Pajilla::where('estado', 'vencida')->count(),
                'pajillas_total' => Pajilla::count(),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Termos/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:50', 'unique:termos,codigo'],
            'nombre' => ['nullable', 'string', 'max:100'],
            'ubicacion' => ['nullable', 'string', 'max:150'],
            'capacidad' => ['nullable', 'integer', 'min:1'],
            'estado' => ['required', 'in:activo,inactivo,mantenimiento'],
            'descripcion' => ['nullable', 'string', 'max:500'],
        ]);

        Termo::create($data);

        return redirect()
            ->route('termos.index')
            ->with('success', 'Termo registrado correctamente.');
    }

    public function show(Termo $termo)
    {
        $termo->load([
            'pajillas.animal',
            'pajillas.donadorExterno',
        ]);

        return Inertia::render('Termos/Show', [
            'termo' => $termo,
        ]);
    }

    public function edit(Termo $termo)
    {
        return Inertia::render('Termos/Edit', [
            'termo' => $termo,
        ]);
    }

    public function update(Request $request, Termo $termo)
    {
        $data = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:50',
                'unique:termos,codigo,' . $termo->id,
            ],
            'nombre' => ['nullable', 'string', 'max:100'],
            'ubicacion' => ['nullable', 'string', 'max:150'],
            'capacidad' => ['nullable', 'integer', 'min:1'],
            'estado' => ['required', 'in:activo,inactivo,mantenimiento'],
            'descripcion' => ['nullable', 'string', 'max:500'],
        ]);

        $termo->update($data);

        return redirect()
            ->route('termos.index')
            ->with('success', 'Termo actualizado correctamente.');
    }

    public function destroy(Termo $termo)
    {
        $termo->delete();

        return redirect()
            ->route('termos.index')
            ->with('success', 'Termo eliminado correctamente.');
    }
}