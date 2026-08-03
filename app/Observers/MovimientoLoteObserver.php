<?php

namespace App\Observers;

use App\Models\Animal;
use App\Models\MovimientoLote;
use Illuminate\Support\Facades\Auth;

/**
 * Registra el cambio de lote de un ejemplar ANTES de que se sobrescriba.
 *
 * Cumple la regla de no perder el lote anterior: cada reasignación deja su
 * movimiento con fecha y responsable, sin importar desde qué parte del sistema
 * se haya hecho el cambio.
 */
class MovimientoLoteObserver
{
    public function updating(Animal $animal): void
    {
        if (! $animal->isDirty('lote_id')) {
            return;
        }

        $anterior = $animal->getOriginal('lote_id');
        $nuevo = $animal->lote_id;

        if ((int) $anterior === (int) $nuevo) {
            return;
        }

        MovimientoLote::create([
            'animal_id' => $animal->id,
            'lote_anterior_id' => $anterior,
            'lote_nuevo_id' => $nuevo,
            'fecha' => now()->toDateString(),
            // El motivo viaja en una propiedad de PHP del modelo, no como
            // atributo, para no intentar guardarlo como columna.
            'motivo' => $animal->motivoMovimientoLote,
            'responsable_id' => Auth::id(),
        ]);
    }
}
