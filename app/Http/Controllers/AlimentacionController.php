<?php

namespace App\Http\Controllers;

use App\Models\Alimentacion;
use App\Models\Animal;
use App\Models\Lote;
use App\Models\Racion;
use App\Models\InventarioInsumo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use App\Models\ProgramacionAlimentacion;
class AlimentacionController extends Controller
{
    public function index(): Response
    {
        $alimentaciones = Alimentacion::with([
            'animal',
            'lote',
            'racion',
            'programacion',
        ])
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->get();

        $animales = Animal::select('id', 'arete')->get();
        $lotes    = Lote::select('id', 'nombre')->get();

        // Solo raciones activas para el selector de nuevos consumos
        $raciones = Racion::activo()->with('insumos')->get();

        // Solo insumos activos para el inventario de referencia
        $inventario = InventarioInsumo::activo()->get([
            'id', 'nombre', 'tipo', 'existencias', 'unidad',
            'costo_promedio', 'marca', 'MS', 'PB', 'EM', 'FDN',
            'auto_rellenar', 'dias_rellenado', 'cantidad_rellenado',
        ]);

        $series  = [];
        $summary = [
            'best_value' => null,
            'best_label' => null,
            'average'    => null,
            'trend'      => null,
        ];

        if ($alimentaciones->count()) {
            $groupedByMonth = $alimentaciones
                ->filter(fn ($a) => $a->fecha && $a->animal_id)
                ->groupBy(fn ($a) => Carbon::parse($a->fecha)->format('Y-m'));

            foreach ($groupedByMonth as $ym => $rows) {
                $totalAlimento = $rows->sum('cantidad');
                $uniqueAnimals = max($rows->pluck('animal_id')->filter()->unique()->count(), 1);

                $series[] = [
                    'year_month' => $ym,
                    'mes'        => Carbon::parse($ym . '-01')->locale('es')->isoFormat('MMM'),
                    'valor'      => round($totalAlimento / $uniqueAnimals, 2),
                ];
            }

            $seriesCollection = collect($series)->sortBy('year_month')->values();

            if ($seriesCollection->isNotEmpty()) {
                $best = $seriesCollection->sortBy('valor')->first();
                $avg  = round($seriesCollection->avg('valor'), 1);

                $byMonth = $seriesCollection->groupBy('year_month')->sortKeys();
                $last    = $byMonth->last();
                $prev    = $byMonth->slice(-2, 1)->first();

                $trend = null;
                if ($last && $prev) {
                    $lastAvg = $last->avg('valor');
                    $prevAvg = $prev->avg('valor');
                    if ($prevAvg > 0) {
                        $trend = round(($prevAvg - $lastAvg) * 100 / $prevAvg, 1);
                    }
                }

                $summary = [
                    'best_value' => $best['valor'],
                    'best_label' => $best['mes'],
                    'average'    => $avg,
                    'trend'      => $trend,
                ];

                $series = $seriesCollection->toArray();
            }
        }

        return Inertia::render('Alimentacion/Alimentacion', [
            'alimentaciones'    => $alimentaciones,
            'animales'          => $animales,
            'lotes'             => $lotes,
            'raciones'          => $raciones,
            'inventario'        => $inventario,
            'conversionSeries'  => $series,
            'conversionSummary' => $summary,
        
            // ← Agrega esto:
            'programaciones' => ProgramacionAlimentacion::with(['animal', 'lote', 'racion'])
                                    ->orderByDesc('created_at')
                                    ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fecha'      => ['required', 'date'],
            'hora'       => ['nullable', 'date_format:H:i'],
            'animal_id'  => ['nullable', 'exists:animals,id'],
            'lote_id'    => ['nullable', 'exists:lotes,id'],
            'racion_id'  => ['required', 'exists:racions,id'],
            'cantidad'   => ['required', 'numeric', 'min:0.01'],
            'unidad'     => ['required', 'string', 'max:20'],
            'notas'      => ['nullable', 'string'],
        ]);

        if (
            (!empty($data['animal_id']) && !empty($data['lote_id'])) ||
            (empty($data['animal_id']) && empty($data['lote_id']))
        ) {
            return back()->withErrors([
                'destino' => 'Debes seleccionar un animal o un lote, pero no ambos.',
            ]);
        }

        try {
            DB::transaction(function () use ($data) {
                $racion = Racion::with('insumos')->findOrFail($data['racion_id']);

                // Guardar snapshots ANTES de descontar, para capturar el estado actual
                $snapshotComposicion = $racion->generarSnapshotComposicion();
                $snapshotNutricion   = $racion->generarSnapshotNutricion();

                Alimentacion::create([
                    'fecha'                => $data['fecha'],
                    'hora'                 => $data['hora'] ?? null,
                    'racion_id'            => $data['racion_id'],
                    'cantidad'             => $data['cantidad'],
                    'unidad'               => $data['unidad'],
                    'animal_id'            => $data['animal_id'] ?? null,
                    'lote_id'              => $data['lote_id'] ?? null,
                    'notas'                => $data['notas'] ?? null,
                    'tipo'                 => 'racion',
                    'generado_automaticamente' => false,
                    'snapshot_composicion' => $snapshotComposicion,
                    'snapshot_nutricion'   => $snapshotNutricion,
                ]);

                $this->descontarInventarioPorRacion($racion, (float) $data['cantidad']);
            });

            return back()->with('success', 'Consumo registrado correctamente.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Eliminar un consumo REPONE el inventario descontado originalmente.
     * Se usa el snapshot para saber exactamente cuánto se descontó de cada insumo.
     */
    public function destroy(Alimentacion $alimentacion)
    {
        DB::transaction(function () use ($alimentacion) {
            // Reponer inventario usando el snapshot si existe
            if ($alimentacion->snapshot_composicion) {
                $composicion = is_string($alimentacion->snapshot_composicion)
                    ? json_decode($alimentacion->snapshot_composicion, true)
                    : $alimentacion->snapshot_composicion;

                foreach ($composicion as $insumoSnap) {
                    $cantidadAReponer = (float) $insumoSnap['cantidad'] * (float) $alimentacion->cantidad;

                    $inventario = InventarioInsumo::where('id', $insumoSnap['insumo_id'])
                        ->lockForUpdate()
                        ->first();

                    if ($inventario) {
                        $inventario->existencias += $cantidadAReponer;
                        $inventario->save();
                    }
                }
            } elseif ($alimentacion->racion_id) {
                // Fallback para consumos anteriores a los snapshots
                $this->reponerInventarioPorRacion(
                    $alimentacion->racion_id,
                    (float) $alimentacion->cantidad
                );
            }

            $alimentacion->delete();
        });

        return back()->with('success', 'Consumo eliminado y stock repuesto.');
    }

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
                throw new \Exception("Stock insuficiente de {$insumo->nombre}. Disponible: {$inventario->existencias} {$inventario->unidad}, requerido: {$cantidadInsumo} {$inventario->unidad}.");
            }

            $inventario->existencias -= $cantidadInsumo;
            $inventario->save();

            // Si el stock llegó a cero, pausar programaciones que usen esta ración
            if ($inventario->existencias <= 0) {
                $this->pausarProgramacionesPorInsumo($insumo->id);
            }
        }
    }

    protected function reponerInventarioPorRacion(int $racionId, float $cantidadRacion): void
    {
        $racion = Racion::with('insumos')->find($racionId);
        if (!$racion) {
            return;
        }

        foreach ($racion->insumos as $insumo) {
            $cantidadInsumo = (float) $insumo->pivot->cantidad * $cantidadRacion;

            $inventario = InventarioInsumo::where('id', $insumo->id)
                ->lockForUpdate()
                ->first();

            if ($inventario) {
                $inventario->existencias += $cantidadInsumo;
                $inventario->save();
            }
        }
    }

    protected function pausarProgramacionesPorInsumo(int $insumoId): void
    {
        // Pausar todas las programaciones activas que usen una ración que contiene este insumo
        DB::table('programacion_alimentacions')
            ->where('activa', true)
            ->whereIn('racion_id', function ($query) use ($insumoId) {
                $query->select('racion_id')
                    ->from('racion_insumo')
                    ->where('inventario_insumo_id', $insumoId);
            })
            ->update([
                'activa'     => false,
                'updated_at' => now(),
            ]);
    }
}