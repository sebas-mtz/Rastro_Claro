<?php

namespace App\Http\Controllers;

use App\Models\ProgramacionAlimentacion;
use App\Models\Animal;
use App\Models\Lote;
use App\Models\Racion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProgramacionAlimentacionController extends Controller
{
    public function index(): Response
    {
        $programaciones = ProgramacionAlimentacion::with([
            'animal',
            'lote',
            'racion',
        ])
            ->orderByDesc('created_at')
            ->get();

        $animales = Animal::select('id', 'arete')->get();
        $lotes    = Lote::select('id', 'nombre')->get();

        // Solo raciones activas para el selector — las archivadas no deben usarse en nuevas programaciones
        $raciones = Racion::activo()->select('id', 'nombre')->get();

        return Inertia::render('Alimentacion/tabs/AlimentaciónModal', [
            'programaciones' => $programaciones,
            'animales'       => $animales,
            'lotes'          => $lotes,
            'raciones'       => $raciones,
        ]);
    }

    // store(), update(), destroy() y toggleActiva() quedan exactamente igual que los tuyos.
    // Solo pega el resto de tu archivo original aquí abajo sin cambios.

    public function store(Request $request)
    {
        $data = $request->validate([
            'racion_id'    => ['required', 'exists:racions,id'],
            'animal_id'    => ['nullable', 'exists:animals,id'],
            'lote_id'      => ['nullable', 'exists:lotes,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin'    => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'hora'         => ['required', 'date_format:H:i'],
            'cantidad'     => ['required', 'numeric', 'min:0.01'],
            'unidad'       => ['required', 'string', 'max:20'],
            'frecuencia'   => ['required', 'in:una_vez,diaria'],
            'activa'       => ['nullable', 'boolean'],
            'notas'        => ['nullable', 'string'],
        ]);

        if (empty($data['animal_id']) && empty($data['lote_id'])) {
            return back()->withErrors([
                'destino' => 'Debes seleccionar un animal o un lote.',
            ]);
        }

        ProgramacionAlimentacion::create([
            'racion_id'    => $data['racion_id'],
            'animal_id'    => $data['animal_id'] ?? null,
            'lote_id'      => $data['lote_id'] ?? null,
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin'    => $data['fecha_fin'] ?? null,
            'hora'         => $data['hora'],
            'cantidad'     => $data['cantidad'],
            'unidad'       => $data['unidad'],
            'frecuencia'   => $data['frecuencia'],
            'activa'       => $data['activa'] ?? true,
            'notas'        => $data['notas'] ?? null,
        ]);

        return back()->with('success', 'Programación creada correctamente.');
    }

    public function update(Request $request, ProgramacionAlimentacion $programacionAlimentacion)
    {
        $data = $request->validate([
            'racion_id'    => ['required', 'exists:racions,id'],
            'animal_id'    => ['nullable', 'exists:animals,id'],
            'lote_id'      => ['nullable', 'exists:lotes,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin'    => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'hora'         => ['required', 'date_format:H:i'],
            'cantidad'     => ['required', 'numeric', 'min:0.01'],
            'unidad'       => ['required', 'string', 'max:20'],
            'frecuencia'   => ['required', 'in:una_vez,diaria'],
            'activa'       => ['nullable', 'boolean'],
            'notas'        => ['nullable', 'string'],
        ]);

        if (
            (!empty($data['animal_id']) && !empty($data['lote_id'])) ||
            (empty($data['animal_id']) && empty($data['lote_id']))
        ) {
            return back()->withErrors([
                'destino' => 'Debes seleccionar un animal o un lote, pero no ambos.',
            ]);
        }

        $programacionAlimentacion->update([
            'racion_id'    => $data['racion_id'],
            'animal_id'    => $data['animal_id'] ?? null,
            'lote_id'      => $data['lote_id'] ?? null,
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin'    => $data['fecha_fin'] ?? null,
            'hora'         => $data['hora'],
            'cantidad'     => $data['cantidad'],
            'unidad'       => $data['unidad'],
            'frecuencia'   => $data['frecuencia'],
            'activa'       => $data['activa'] ?? true,
            'notas'        => $data['notas'] ?? null,
        ]);

        return back()->with('success', 'Programación actualizada correctamente.');
    }

    public function destroy(ProgramacionAlimentacion $programacionAlimentacion)
    {
        $programacionAlimentacion->delete();

        return back()->with('success', 'Programación eliminada correctamente.');
    }

    public function toggleActiva(ProgramacionAlimentacion $programacionAlimentacion)
    {
        $programacionAlimentacion->update([
            'activa' => !$programacionAlimentacion->activa,
        ]);

        return back()->with('success', 'Estado de la programación actualizado.');
    }
}