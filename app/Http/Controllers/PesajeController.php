<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Pesaje;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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

        // Calculamos para cada animal:
        // - peso_inicial: primer pesaje registrado (o el campo peso del animal si no hay pesajes)
        // - peso_actual:  último pesaje registrado
        // - ganancia_total: peso_actual - peso_inicial
        // - ganancia_diaria: ganancia_total / días entre primer y último pesaje
        $animales = $animales->map(function ($animal) {
            $pesajes = $animal->pesajes; // ya ordenados desc

            if ($pesajes->isEmpty()) {
                $animal->peso_inicial    = $animal->peso ?? null;
                $animal->peso_actual     = $animal->peso ?? null;
                $animal->ganancia_total  = null;
                $animal->ganancia_diaria = null;
                $animal->dias_seguimiento = null;
                return $animal;
            }

            $ultimo  = $pesajes->first();  // más reciente (desc)
            $primero = $pesajes->last();   // más antiguo

            $diasSeguimiento = (int) $primero->fecha->diffInDays($ultimo->fecha);

            $animal->peso_inicial     = (float) $primero->peso;
            $animal->peso_actual      = (float) $ultimo->peso;
            $animal->ganancia_total   = round($ultimo->peso - $primero->peso, 2);
            $animal->ganancia_diaria  = $diasSeguimiento > 0
                ? round(($ultimo->peso - $primero->peso) / $diasSeguimiento, 3)
                : null;
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
            'fecha'     => ['required', 'date'],
            'peso'      => ['required', 'numeric', 'min:0.01'],
            'notas'     => ['nullable', 'string', 'max:500'],
        ]);

        // Evitar duplicado exacto (mismo animal, misma fecha)
        $existe = Pesaje::where('animal_id', $data['animal_id'])
            ->where('fecha', $data['fecha'])
            ->exists();

        if ($existe) {
            return back()->withErrors([
                'fecha' => 'Ya existe un pesaje para este animal en esa fecha.',
            ]);
        }

        Pesaje::create($data);

        // Actualizar el campo peso del animal con el último pesaje
        $ultimoPeso = Pesaje::where('animal_id', $data['animal_id'])
            ->orderByDesc('fecha')
            ->value('peso');

        Animal::where('id', $data['animal_id'])->update(['peso' => $ultimoPeso]);

        return back()->with('success', 'Pesaje registrado correctamente.');
    }

    public function update(Request $request, Pesaje $pesaje)
    {
        $data = $request->validate([
            'fecha' => ['required', 'date'],
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

        Animal::where('id', $pesaje->animal_id)->update(['peso' => $ultimoPeso]);

        return back()->with('success', 'Pesaje actualizado correctamente.');
    }

    public function destroy(Pesaje $pesaje)
    {
        $animalId = $pesaje->animal_id;
        $pesaje->delete();

        // Sincronizar peso actual del animal con el pesaje más reciente restante
        $ultimoPeso = Pesaje::where('animal_id', $animalId)
            ->orderByDesc('fecha')
            ->value('peso');

        Animal::where('id', $animalId)->update(['peso' => $ultimoPeso]);

        return back()->with('success', 'Pesaje eliminado correctamente.');
    }
}