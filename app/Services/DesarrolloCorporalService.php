<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Pesaje;

/**
 * Desarrollo corporal del ejemplar ovino.
 *
 *   Ganancia_Total  = Peso_Actual − Peso_Anterior
 *   Ganancia_Diaria = (Peso_Actual − Peso_Anterior) / Días_Transcurridos
 *
 * Ambas fórmulas viven aquí, en el backend. La división entre cero y las
 * fechas invertidas se controlan devolviendo null en lugar de un número falso.
 */
class DesarrolloCorporalService
{
    /**
     * Historial de pesajes con la ganancia calculada respecto al anterior.
     */
    public function historial(Animal $animal): array
    {
        $pesajes = Pesaje::where('animal_id', $animal->id)
            ->orderBy('fecha')
            ->get();

        $nacimiento = AnimalValuationService::fechaNacimiento($animal);
        $filas = [];
        $anterior = null;

        foreach ($pesajes as $pesaje) {
            $ganancia = $this->ganancia($anterior, $pesaje);

            $filas[] = [
                'id' => $pesaje->id,
                'fecha' => $pesaje->fecha->toDateString(),
                'peso' => (float) $pesaje->peso,
                'unidad' => $pesaje->unidad ?? 'kg',
                'condicion_corporal' => $pesaje->condicion_corporal !== null
                    ? (float) $pesaje->condicion_corporal
                    : null,
                'metodo' => $pesaje->metodo_legible,
                'responsable' => $pesaje->responsable,
                // Un conteo de días es entero: Carbon devuelve float.
                'edad_dias' => $nacimiento ? (int) $nacimiento->diffInDays($pesaje->fecha) : null,
                'ganancia_total' => $ganancia['total'],
                'ganancia_diaria' => $ganancia['diaria'],
                'dias_transcurridos' => $ganancia['dias'],
                'notas' => $pesaje->notas,
            ];

            $anterior = $pesaje;
        }

        return $filas;
    }

    /**
     * Resumen del desarrollo: peso inicial, actual, ganancia acumulada y
     * ganancia diaria promedio en todo el periodo registrado.
     */
    public function resumen(Animal $animal): array
    {
        $pesajes = Pesaje::where('animal_id', $animal->id)->orderBy('fecha')->get();

        if ($pesajes->isEmpty()) {
            return [
                'total_pesajes' => 0,
                'peso_inicial' => null,
                'peso_actual' => null,
                'ganancia_acumulada' => null,
                'ganancia_diaria_promedio' => null,
                'aviso' => 'Este ejemplar todavía no tiene pesajes registrados.',
            ];
        }

        $primero = $pesajes->first();
        $ultimo = $pesajes->last();

        if ($pesajes->count() === 1) {
            return [
                'total_pesajes' => 1,
                'peso_inicial' => (float) $primero->peso,
                'peso_actual' => (float) $primero->peso,
                'ganancia_acumulada' => null,
                'ganancia_diaria_promedio' => null,
                'aviso' => 'Se necesita al menos un segundo pesaje para calcular la ganancia.',
            ];
        }

        $ganancia = $this->ganancia($primero, $ultimo);

        return [
            'total_pesajes' => $pesajes->count(),
            'peso_inicial' => (float) $primero->peso,
            'peso_actual' => (float) $ultimo->peso,
            'ganancia_acumulada' => $ganancia['total'],
            'ganancia_diaria_promedio' => $ganancia['diaria'],
            'dias_periodo' => $ganancia['dias'],
            'aviso' => $ganancia['diaria'] === null
                ? 'Los pesajes tienen la misma fecha: no es posible calcular ganancia diaria.'
                : null,
        ];
    }

    /**
     * @return array{total: float|null, diaria: float|null, dias: int|null}
     */
    private function ganancia(?Pesaje $anterior, Pesaje $actual): array
    {
        if (! $anterior) {
            return ['total' => null, 'diaria' => null, 'dias' => null];
        }

        $total = round((float) $actual->peso - (float) $anterior->peso, 2);

        // Fechas iguales o invertidas: no se fuerza una división inválida.
        $dias = $anterior->fecha->diffInDays($actual->fecha, false);

        if ($dias <= 0) {
            return ['total' => $total, 'diaria' => null, 'dias' => $dias > 0 ? $dias : null];
        }

        return [
            'total' => $total,
            'diaria' => round($total / $dias, 3),
            'dias' => (int) $dias,
        ];
    }
}
