<?php

namespace App\Jobs;

use App\Models\Alimentacion;
use App\Models\ProgramacionAlimentacion;
use App\Models\InventarioInsumo;
use App\Models\Racion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EjecutarProgramacionesAlimentacion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $hoy = Carbon::today();

        $programaciones = ProgramacionAlimentacion::with(['racion.insumos'])
            ->where('activa', true)
            ->where('fecha_inicio', '<=', $hoy)
            ->where(function ($q) use ($hoy) {
                $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $hoy);
            })
            ->get();

        foreach ($programaciones as $prog) {
            // Para frecuencia "una_vez", verificar que no se haya ejecutado ya
            if ($prog->frecuencia === 'una_vez') {
                $yaEjecutada = Alimentacion::where('programacion_id', $prog->id)->exists();
                if ($yaEjecutada) {
                    $prog->update(['activa' => false]);
                    continue;
                }
            }

            try {
                DB::transaction(function () use ($prog, $hoy) {
                    $racion = $prog->racion;

                    if (!$racion || !$racion->activo) {
                        Log::warning("Programacion {$prog->id}: ración inactiva o no encontrada.");
                        $prog->update(['activa' => false]);
                        return;
                    }

                    // Verificar stock antes de registrar
                    foreach ($racion->insumos as $insumo) {
                        $cantidadRequerida = (float) $insumo->pivot->cantidad * (float) $prog->cantidad;
                        $inventario = InventarioInsumo::find($insumo->id);

                        if (!$inventario || (float) $inventario->existencias < $cantidadRequerida) {
                            Log::warning("Programacion {$prog->id}: stock insuficiente de {$insumo->nombre}. Pausando programación.");
                            $prog->update(['activa' => false]);
                            return;
                        }
                    }

                    // Guardar snapshots del momento actual
                    $snapshotComposicion = $racion->generarSnapshotComposicion();
                    $snapshotNutricion   = $racion->generarSnapshotNutricion();

                    $alimentacion = new Alimentacion([
                        'fecha'                    => $hoy->toDateString(),
                        'hora'                     => $prog->hora,
                        'racion_id'                => $prog->racion_id,
                        'animal_id'                => $prog->animal_id,
                        'lote_id'                  => $prog->lote_id,
                        'cantidad'                 => $prog->cantidad,
                        'unidad'                   => $prog->unidad,
                        'notas'                    => $prog->notas,
                        'tipo'                     => 'racion',
                        'programacion_id'          => $prog->id,
                        'generado_automaticamente' => true,
                        'snapshot_composicion'     => $snapshotComposicion,
                        'snapshot_nutricion'       => $snapshotNutricion,
                    ]);
                    $alimentacion->setAttribute('owner_id', $prog->owner_id);
                    $alimentacion->save();

                    // Descontar inventario
                    foreach ($racion->insumos as $insumo) {
                        $cantidadInsumo = (float) $insumo->pivot->cantidad * (float) $prog->cantidad;

                        $inventario = InventarioInsumo::where('id', $insumo->id)
                            ->lockForUpdate()
                            ->first();

                        $inventario->existencias -= $cantidadInsumo;
                        $inventario->save();

                        // Pausar otras programaciones si el stock llegó a cero
                        if ($inventario->existencias <= 0) {
                            $this->pausarProgramacionesPorInsumo($insumo->id, $prog->id);
                        }
                    }
                });
            } catch (\Throwable $e) {
                Log::error("Error ejecutando programacion {$prog->id}: {$e->getMessage()}");
            }
        }
    }

    private function pausarProgramacionesPorInsumo(int $insumoId, int $exceptoProgramacionId): void
    {
        DB::table('programacion_alimentacions')
            ->where('activa', true)
            ->where('id', '!=', $exceptoProgramacionId)
            ->whereIn('racion_id', function ($query) use ($insumoId) {
                $query->select('racion_id')
                    ->from('racion_insumo')
                    ->where('inventario_insumo_id', $insumoId);
            })
            ->update(['activa' => false, 'updated_at' => now()]);
    }
}
