<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\CondicionCorporal;
use App\Models\Pesaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
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
            'metodos' => Pesaje::METODOS,
            'escalaCondicionCorporal' => CondicionCorporal::ESCALA,
            'rangoOptimoCC' => CondicionCorporal::RANGO_OPTIMO,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->reglas() + [
            'animal_id' => ['required', 'exists:animals,id'],
        ]);

        // Evitar duplicado exacto (mismo animal, misma fecha)
        $existe = Pesaje::where('animal_id', $data['animal_id'])
            ->where('fecha', $data['fecha'])
            ->exists();

        if ($existe) {
            return back()->withErrors([
                'fecha' => 'Ya existe un pesaje para este ejemplar en esa fecha.',
            ]);
        }

        $pesaje = Pesaje::create($data);

        $this->sincronizarCondicionCorporal($pesaje);

        // Actualizar el campo peso del animal con el último pesaje
        $ultimoPeso = Pesaje::where('animal_id', $data['animal_id'])
            ->orderByDesc('fecha')
            ->value('peso');

        Animal::where('id', $data['animal_id'])->update(['peso' => $ultimoPeso]);

        return back()->with('success', 'Pesaje registrado correctamente.');
    }

    public function update(Request $request, Pesaje $pesaje)
    {
        $data = $request->validate($this->reglas());

        // Evitar duplicado en otra fila (misma fecha, mismo animal, distinto id)
        $existe = Pesaje::where('animal_id', $pesaje->animal_id)
            ->where('fecha', $data['fecha'])
            ->where('id', '!=', $pesaje->id)
            ->exists();

        if ($existe) {
            return back()->withErrors([
                'fecha' => 'Ya existe un pesaje para este ejemplar en esa fecha.',
            ]);
        }

        $pesaje->update($data);

        $this->sincronizarCondicionCorporal($pesaje);

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

        // La condición corporal capturada desde este pesaje se va con él.
        CondicionCorporal::where('origen_tipo', Pesaje::class)
            ->where('origen_id', $pesaje->id)
            ->delete();

        return back()->with('success', 'Pesaje eliminado correctamente.');
    }

    /**
     * Reglas comunes de alta y edición del pesaje ovino.
     */
    private function reglas(): array
    {
        return [
            'fecha'              => ['required', 'date', 'before_or_equal:today'],
            'peso'               => ['required', 'numeric', 'min:0.01'],
            'unidad'             => ['nullable', 'string', 'max:10'],
            'condicion_corporal' => ['nullable', 'numeric', 'min:1', 'max:5'],
            'metodo'             => ['nullable', Rule::in(array_keys(Pesaje::METODOS))],
            'responsable'        => ['nullable', 'string', 'max:150'],
            'notas'              => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Refleja la condición corporal capturada durante el pesaje como un
     * registro del historial de CC, ligado al pesaje por origen_tipo/origen_id
     * para no duplicar la información.
     */
    private function sincronizarCondicionCorporal(Pesaje $pesaje): void
    {
        $existente = CondicionCorporal::where('origen_tipo', Pesaje::class)
            ->where('origen_id', $pesaje->id)
            ->first();

        if (blank($pesaje->condicion_corporal)) {
            $existente?->delete();

            return;
        }

        $atributos = [
            'animal_id'          => $pesaje->animal_id,
            'fecha'              => $pesaje->fecha,
            'calificacion'       => $pesaje->condicion_corporal,
            'etapa_reproductiva' => $pesaje->animal?->estado_reproductivo,
            'responsable'        => $pesaje->responsable,
            'observaciones'      => 'Capturada durante el pesaje del ' . $pesaje->fecha->format('d/m/Y') . '.',
            'origen_tipo'        => Pesaje::class,
            'origen_id'          => $pesaje->id,
            'registrado_por'     => Auth::id(),
        ];

        if ($existente) {
            $existente->update($atributos);

            return;
        }

        CondicionCorporal::create($atributos);
    }
}