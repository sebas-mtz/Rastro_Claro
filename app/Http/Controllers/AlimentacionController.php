<?php

namespace App\Http\Controllers;

use App\Models\Alimentacion;
use App\Models\Animal;
use App\Models\Lote;
use App\Models\Racion;
use App\Models\InventarioInsumo;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Illuminate\Http\Request;
class AlimentacionController extends Controller
{
    /**
     * Módulo de alimentación (tabs) + listado de raciones.
     */
   public function index(): Response
{
    // RACIONES
    $alimentaciones = Alimentacion::with(['animal', 'lote', 'racion'])
        ->orderByDesc('fecha')
        ->get();

    $animales = Animal::select('id', 'arete')->get();
    $lotes    = Lote::select('id', 'nombre')->get();
    $raciones = Racion::select('id', 'nombre')->get();

    // INVENTARIO
    $inventario = InventarioInsumo::select(
        'id',
        'nombre',
        'tipo',
        'existencias',
        'unidad',
        'costo_promedio'
    )->get();

    // CONVERSIÓN ALIMENTICIA (a partir de alimentacions)
    $series   = [];
    $summary  = [
        'best_value' => null,
        'best_label' => null,
        'average'    => null,
        'trend'      => null,
    ];

    if ($alimentaciones->count()) {

        // Agrupamos por mes (YYYY-MM)
        $groupedByMonth = $alimentaciones
            ->filter(fn ($a) => $a->fecha && $a->animal_id) // solo registros con fecha y animal
            ->groupBy(function ($a) {
                return Carbon::parse($a->fecha)->format('Y-m');
            });

        foreach ($groupedByMonth as $ym => $rows) {
            $totalAlimento = $rows->sum('consumo_kg');
            $uniqueAnimals = max(
                $rows->pluck('animal_id')->filter()->unique()->count(),
                1
            );

            $ratio = $totalAlimento / $uniqueAnimals; // kg alimento / animal en ese mes

            $series[] = [
                'year_month' => $ym,
                'mes'        => Carbon::parse($ym . '-01')->locale('es')->isoFormat('MMM'),
                'valor'      => round($ratio, 2),
            ];
        }

        $seriesCollection = collect($series)->sortBy('year_month')->values();

        if ($seriesCollection->isNotEmpty()) {
            // Mejor conversión = menor ratio
            $best = $seriesCollection->sortBy('valor')->first();
            $avg  = round($seriesCollection->avg('valor'), 1);

            // Tendencia: último mes vs penúltimo
            $byMonth = $seriesCollection->groupBy('year_month')->sortKeys();
            $last    = $byMonth->last();
            $prev    = $byMonth->slice(-2, 1)->first(); // penúltimo grupo (si existe)

            $trend = null;
            if ($last && $prev) {
                $lastAvg = $last->avg('valor');
                $prevAvg = $prev->avg('valor');
                if ($prevAvg > 0) {
                    // % de mejora (positivo = mejora)
                    $trend = round(($prevAvg - $lastAvg) * 100 / $prevAvg, 1);
                }
            }

            $summary = [
                'best_value' => $best['valor'],
                'best_label' => $best['mes'],
                'average'    => $avg,
                'trend'      => $trend,
            ];

            // Guardamos la serie ya ordenada
            $series = $seriesCollection->toArray();
        }
    }

    return Inertia::render('Alimentacion/Alimentacion', [
        'alimentaciones'   => $alimentaciones,
        'animales'         => $animales,
        'lotes'            => $lotes,
        'raciones'         => $raciones,
        'inventario'       => $inventario,
        'conversionSeries' => $series,
        'conversionSummary'=> $summary,
    ]);
}}