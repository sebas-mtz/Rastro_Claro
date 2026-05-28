<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ProgramacionAlimentacion;
use App\Models\Alimentacion;
use App\Models\Racion;
use App\Models\InventarioInsumo;

class GenerarAlimentacionesProgramadas extends Command
{
    protected $signature = 'alimentacion:generar-programadas';
    protected $description = 'Genera las alimentaciones automáticas del día a partir de programaciones activas';

    public function handle(): int
    {
        $hoy = Carbon::today()->toDateString();

        $programaciones = ProgramacionAlimentacion::with('racion.insumos')
            ->where('activa', true)
            ->whereDate('fecha_inicio', '<=', $hoy)
            ->where(function ($query) use ($hoy) {
                $query->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', $hoy);
            })
            ->get();

        foreach ($programaciones as $programacion) {
            if (!$this->debeAplicarseHoy($programacion, $hoy)) {
                continue;
            }

            try {
                DB::transaction(function () use ($programacion, $hoy) {
                    $existe = Alimentacion::where('programacion_alimentacion_id', $programacion->id)
                        ->whereDate('fecha', $hoy)
                        ->exists();

                    if ($existe) {
                        return;
                    }

                    $racion = $programacion->racion;

                    // Si la ración fue archivada, pausar la programación
                    if (!$racion || !$racion->activo) {
                        $programacion->update(['activa' => false]);
                        $this->warn("Programación {$programacion->id} pausada: la ración fue archivada.");
                        return;
                    }

                    // Verificar stock de todos los insumos antes de registrar
                    foreach ($racion->insumos as $insumo) {
                        $cantidadRequerida = (float) $insumo->pivot->cantidad * (float) $programacion->cantidad;
                        $inventario = InventarioInsumo::find($insumo->id);

                        if (!$inventario || (float) $inventario->existencias < $cantidadRequerida) {
                            $programacion->update(['activa' => false]);
                            $this->warn("Programación {$programacion->id} pausada: stock insuficiente de {$insumo->nombre}.");
                            return;
                        }
                    }

                    // Guardar snapshot de la composición y nutrición en este momento
                    $snapshotComposicion = $racion->generarSnapshotComposicion();
                    $snapshotNutricion   = $racion->generarSnapshotNutricion();

                    $alimentacion = Alimentacion::create([
                        'fecha'                          => $hoy,
                        'hora'                           => $programacion->hora,
                        'tipo'                           => 'racion_programada',
                        'cantidad'                       => $programacion->cantidad,
                        'unidad'                         => $programacion->unidad,
                        'animal_id'                      => $programacion->animal_id,
                        'lote_id'                        => $programacion->lote_id,
                        'racion_id'                      => $programacion->racion_id,
                        'programacion_alimentacion_id'   => $programacion->id,
                        'generado_automaticamente'       => true,
                        'notas'                          => $programacion->notas,
                        'snapshot_composicion'           => $snapshotComposicion,
                        'snapshot_nutricion'             => $snapshotNutricion,
                    ]);

                    $this->descontarInventarioPorRacion($racion, (float) $alimentacion->cantidad);
                });

                $this->info("Programación {$programacion->id} aplicada.");
            } catch (\Throwable $e) {
                $this->error("Error en programación {$programacion->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    protected function debeAplicarseHoy($programacion, string $hoy): bool
    {
        if ($programacion->frecuencia === 'diaria') {
            return true;
        }

        if ($programacion->frecuencia === 'una_vez') {
            return Carbon::parse($programacion->fecha_inicio)->toDateString() === $hoy;
        }

        return false;
    }

    // Recibe el modelo Racion ya cargado (con insumos) para no hacer query extra
    protected function descontarInventarioPorRacion(Racion $racion, float $cantidadRacion): void
    {
        foreach ($racion->insumos as $insumo) {
            $cantidadInsumo = (float) $insumo->pivot->cantidad * $cantidadRacion;

            $inventario = InventarioInsumo::where('id', $insumo->id)
                ->lockForUpdate()
                ->first();

            if (!$inventario) {
                throw new \Exception("Insumo no encontrado: {$insumo->nombre}");
            }

            if ((float) $inventario->existencias < $cantidadInsumo) {
                throw new \Exception("Stock insuficiente de {$insumo->nombre}");
            }

            $inventario->existencias -= $cantidadInsumo;
            $inventario->save();

            // Si el stock llegó a cero, pausar otras programaciones que usen este insumo
            if ($inventario->existencias <= 0) {
                $this->pausarProgramacionesPorInsumo($insumo->id);
            }
        }
    }

    protected function pausarProgramacionesPorInsumo(int $insumoId): void
    {
        $pausadas = DB::table('programacion_alimentacions')
            ->where('activa', true)
            ->whereIn('racion_id', function ($query) use ($insumoId) {
                $query->select('racion_id')
                    ->from('racion_insumo')
                    ->where('inventario_insumo_id', $insumoId);
            })
            ->update(['activa' => false, 'updated_at' => now()]);

        if ($pausadas > 0) {
            $this->warn("{$pausadas} programación(es) pausada(s) por stock agotado del insumo #{$insumoId}.");
        }
    }
}