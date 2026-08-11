<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Pesaje;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\EstadoProductivoService;


class PesajeController extends Controller
{
    public function index(): Response
    {
        $animales = Animal::with(['pesajes' => function ($q) {
                $q->orderBy('fecha', 'desc');
            }])
            ->orderBy('arete')
            ->get([
                'id', 'arete', 'alias', 'especie', 'raza', 'sexo',
                'lote_id', 'peso', 'fecha_nac',
            ]);

        // El peso inicial pertenece al alta/nacimiento del animal. Desde el
        // primer pesaje agregado se calcula la diferencia contra esa base.
        $animales = $animales->map(function ($animal) {

    $pesajes = $animal->pesajes;

    if ($pesajes->isEmpty()) {
        $animal->peso_actual = null;
        $animal->ganancia_total = null;
        $animal->ganancia_diaria = null;
        $animal->dias_seguimiento = 0;

        return $animal;
    }

    $primerPesaje = $pesajes->sortBy('fecha')->first();
    $ultimoPesaje = $pesajes->first();

    $pesoInicial = (float) $primerPesaje->peso;
    $pesoActual = (float) $ultimoPesaje->peso;

    $diasSeguimiento = $primerPesaje->fecha
        ? (int) $primerPesaje->fecha->diffInDays($ultimoPesaje->fecha)
        : 0;

    $ganancia = $pesoActual - $pesoInicial;

    $animal->peso_actual = $pesoActual;
    $animal->ganancia_total = round($ganancia, 2);
    $animal->ganancia_diaria = $diasSeguimiento > 0
        ? round($ganancia / $diasSeguimiento, 3)
        : 0;

    $animal->dias_seguimiento = $diasSeguimiento;

    return $animal;
});
        return Inertia::render('Pesajes/Pesajes', [
            'animales' => $animales,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'animal_id' => ['required', 'exists:animals,id'],
            'fecha'     => ['required', 'date', 'before_or_equal:today'],
            'peso'      => ['required', 'numeric', 'min:0.01'],
            'notas'     => ['nullable', 'string', 'max:500'],
        ]);

        $animal = Animal::findOrFail($data['animal_id']);
    if (in_array($animal->estado_productivo,EstadoProductivoService::estadosSistema(),true)) {            return back()->withErrors([
                'animal_id' => 'No se pueden agregar pesajes a un animal muerto.',
            ]);
        }

        // Evitar duplicado exacto (mismo animal, misma fecha). Este es el
        // único límite de "un pesaje por día" que existe; un mismo animal
        // puede tener tantos pesajes como fechas distintas se registren.
        $existe = Pesaje::where('animal_id', $data['animal_id'])
            ->where('fecha', $data['fecha'])
            ->exists();

        if ($existe) {
            return back()->withErrors([
                'fecha' => 'Ya existe un pesaje para este animal en esa fecha.',
            ]);
        }

        Pesaje::create($data);

        // Actualizar el campo peso del animal con el pesaje de fecha más
        // reciente (no necesariamente el último insertado, por si se
        // registró un pesaje "atrasado" de una fecha anterior a otro ya existente).
        $ultimoPeso = Pesaje::where('animal_id', $data['animal_id'])
            ->orderByDesc('fecha')
            ->value('peso');

        Animal::where('id', $data['animal_id'])->update(['peso' => $ultimoPeso]);

        return back()->with('success', 'Pesaje registrado correctamente.');
    }

    public function update(Request $request, Pesaje $pesaje)
    {
        if ($pesaje->animal?->estado_productivo === 'muerto') {
            return back()->withErrors([
                'animal_id' => 'No se pueden modificar pesajes de un animal muerto.',
            ]);
        }

        $data = $request->validate([
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'peso'  => ['required', 'numeric', 'min:0.01'],
            'notas' => ['nullable', 'string', 'max:500'],
        ]);

        // Evitar duplicado en otra fila (misma fecha, mismo animal, distinto id)
        $existe = Pesaje::where('animal_id', $pesaje->animal_id)
            ->where('fecha', $data['fecha'])
            ->where('id', '!=', $pesaje->id)
            ->exists();

        if ($existe) {
            return back()->withErrors([
                'fecha' => 'Ya existe un pesaje para este animal en esa fecha.',
            ]);
        }

        $pesaje->update($data);

        // Sincronizar peso actual del animal
        $ultimoPeso = Pesaje::where('animal_id', $pesaje->animal_id)
            ->orderByDesc('fecha')
            ->value('peso');

        Animal::where('id', $pesaje->animal_id)->update([
            'peso' => $ultimoPeso,
        ]);

        return back()->with('success', 'Pesaje actualizado correctamente.');
    }

    public function destroy(Pesaje $pesaje)
    {
        if ($pesaje->animal?->estado_productivo === 'muerto') {
            return back()->withErrors([
                'animal_id' => 'No se pueden eliminar pesajes de un animal dado de baja.',
            ]);
        }

        $animalId = $pesaje->animal_id;
        $pesaje->delete();

        // Sincronizar peso actual del animal con el pesaje más reciente restante
        $ultimoPeso = Pesaje::where('animal_id', $animalId)
            ->orderByDesc('fecha')
            ->value('peso');

        Animal::where('id', $animalId)->update([
    'peso' => $ultimoPeso,
]);

        return back()->with('success', 'Pesaje eliminado correctamente.');
    }
}