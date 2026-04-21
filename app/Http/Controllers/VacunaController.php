<?php

namespace App\Http\Controllers;

use App\Models\Vacuna;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VacunaController extends Controller
{
    public function index(): Response
    {
        $vacunas = Vacuna::orderBy('nombre')->get();

        return Inertia::render('Vacunas/Index', [
            'vacunas' => $vacunas,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Vacunas/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre'           => ['required', 'string', 'max:255'],
            'patogeno'         => ['nullable', 'string', 'max:255'],
            'pauta'            => ['nullable', 'string', 'max:255'],
            'refuerzo_dias'    => ['nullable', 'integer', 'min:1'],
            'especie_objetivo' => ['nullable', 'string', 'max:100'],
        ]);

        Vacuna::create($validated);

        return redirect()->route('vacunas.index')
            ->with('success', 'Vacuna registrada correctamente.');
    }

    public function show(Vacuna $vacuna): Response
    {
        // Cargamos los eventos de vacunación relacionados para mostrar historial
        $vacuna->load(['eventosSalud.animal']);

        return Inertia::render('Vacunas/Show', [
            'vacuna' => $vacuna,
        ]);
    }

    public function edit(Vacuna $vacuna): Response
    {
        return Inertia::render('Vacunas/Edit', [
            'vacuna' => $vacuna,
        ]);
    }

    public function update(Request $request, Vacuna $vacuna): RedirectResponse
    {
        $validated = $request->validate([
            'nombre'           => ['required', 'string', 'max:255'],
            'patogeno'         => ['nullable', 'string', 'max:255'],
            'pauta'            => ['nullable', 'string', 'max:255'],
            'refuerzo_dias'    => ['nullable', 'integer', 'min:1'],
            'especie_objetivo' => ['nullable', 'string', 'max:100'],
        ]);

        $vacuna->update($validated);

        return redirect()->route('vacunas.index')
            ->with('success', 'Vacuna actualizada correctamente.');
    }

    public function destroy(Vacuna $vacuna): RedirectResponse
    {
        $vacuna->delete();

        return redirect()->route('vacunas.index')
            ->with('success', 'Vacuna eliminada.');
    }
}