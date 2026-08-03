<?php

namespace App\Observers;

use App\Models\Animal;
use App\Models\AnimalValuation;
use App\Models\AnimalValuationHistorial;
use App\Models\Costo;
use App\Models\Alimentacion;
use App\Models\EventoSalud;
use App\Models\Tratamiento;
use App\Services\AnimalValuationService;
use Illuminate\Database\Eloquent\Model;

/**
 * Recalcula la cotización cuando aparece o cambia un gasto ligado a un animal
 * que ya tiene cotización activa.
 *
 * El recálculo nunca es silencioso: siempre deja un movimiento en el historial
 * nombrando el registro que lo disparó.
 */
class ValuacionRecalculoObserver
{
    /** Evita recalcular varias veces dentro de la misma petición. */
    private static array $enProceso = [];

    public function __construct(protected AnimalValuationService $valuationService)
    {
    }

    public function created(Model $model): void
    {
        $this->recalcular($model, 'registrado');
    }

    public function updated(Model $model): void
    {
        $this->recalcular($model, 'actualizado');
    }

    public function deleted(Model $model): void
    {
        $this->recalcular($model, 'eliminado');
    }

    private function recalcular(Model $model, string $accion): void
    {
        $animalId = $model->animal_id ?? null;

        if (! $animalId) {
            return;   // gastos de lote o generales no disparan recálculo individual
        }

        $animal = Animal::find($animalId);

        if (! $animal) {
            return;
        }

        $valuacion = $animal->valuaciones()
            ->where('estado', AnimalValuation::ESTADO_ACTIVA)
            ->latest()
            ->first();

        // Sin cotización activa no hay nada que actualizar: la valuación se
        // calculará en vivo la próxima vez que se abra la ficha.
        if (! $valuacion) {
            return;
        }

        $llave = $animal->id;

        if (isset(self::$enProceso[$llave])) {
            return;
        }

        self::$enProceso[$llave] = true;

        try {
            $this->valuationService->guardar(
                $animal,
                [
                    'porcentaje_margen_genetico' => (float) $valuacion->porcentaje_margen_genetico,
                    'estado_reproductivo_valuacion' => $valuacion->estado_reproductivo_valuacion,
                    'plus_reproductivo' => (float) $valuacion->plus_reproductivo,
                    'ajuste_manual' => (float) $valuacion->ajuste_manual,
                    'motivo_ajuste' => $valuacion->motivo_ajuste,
                ],
                AnimalValuationHistorial::TIPO_NUEVO_GASTO,
                $this->describir($model, $accion),
                [
                    'tipo' => $model::class,
                    'id' => $model->getKey(),
                    'concepto' => $this->concepto($model),
                    'valor' => $this->valor($model),
                ],
            );
        } finally {
            unset(self::$enProceso[$llave]);
        }
    }

    private function describir(Model $model, string $accion): string
    {
        $etiqueta = match ($model::class) {
            EventoSalud::class => 'Evento de salud',
            Tratamiento::class => 'Tratamiento',
            Alimentacion::class => 'Consumo de alimento',
            Costo::class => 'Costo',
            default => 'Registro',
        };

        return "{$etiqueta} {$accion}: se recalculó la cotización automáticamente.";
    }

    private function concepto(Model $model): ?string
    {
        return match ($model::class) {
            EventoSalud::class => $model->vacuna?->nombre ?? $model->diagnostico,
            Tratamiento::class => $model->nombre,
            Alimentacion::class => $model->racion?->nombre ?? $model->tipo,
            Costo::class => $model->concepto,
            default => null,
        };
    }

    private function valor(Model $model): ?float
    {
        return match ($model::class) {
            EventoSalud::class, Tratamiento::class => $model->costo !== null ? (float) $model->costo : null,
            Alimentacion::class => $model->costoTotal(),
            Costo::class => (float) $model->monto,
            default => null,
        };
    }
}
