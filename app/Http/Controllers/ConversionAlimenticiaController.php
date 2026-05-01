<?php

namespace App\Http\Controllers;

use App\Models\Alimentacion;
use App\Models\Animal;
use App\Models\Lote;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConversionAlimenticiaController extends Controller
{
    public function index(Request $request): Response
    {
        $fechaFin    = $request->input('fecha_fin',    now()->toDateString());
        $fechaInicio = $request->input('fecha_inicio', now()->subDays(30)->toDateString());

        // ─── Alimentaciones en el rango con su ración ─────────────────────────
        $alimentaciones = Alimentacion::with('racion')
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->get();

        // ─── Animales con sus pesajes y lote ──────────────────────────────────
        $animales = Animal::with([
                'lote.animales',
                'pesajes' => fn($q) => $q->orderBy('fecha'),
            ])
            ->get();

        // ─── Lotes con animales ───────────────────────────────────────────────
        $lotes = Lote::with('animales')->get();

        // ─── Mapas de consumo: animal_id / lote_id → {kg, costo} ─────────────
        $consumoPorAnimal = [];
        $consumoPorLote   = [];

        foreach ($alimentaciones as $ali) {
            // Costo por kg de ración: primero desde precio_kg, luego desde snapshot
            $costoKg = $ali->racion?->precio_kg
                ?? $this->costoKgDesdeSnapshot($ali);

            $costo = ($costoKg ?? 0) * (float) $ali->cantidad;

            if ($ali->animal_id) {
                $consumoPorAnimal[$ali->animal_id]['kg']    = ($consumoPorAnimal[$ali->animal_id]['kg']    ?? 0) + (float) $ali->cantidad;
                $consumoPorAnimal[$ali->animal_id]['costo'] = ($consumoPorAnimal[$ali->animal_id]['costo'] ?? 0) + $costo;
            }

            if ($ali->lote_id) {
                $consumoPorLote[$ali->lote_id]['kg']    = ($consumoPorLote[$ali->lote_id]['kg']    ?? 0) + (float) $ali->cantidad;
                $consumoPorLote[$ali->lote_id]['costo'] = ($consumoPorLote[$ali->lote_id]['costo'] ?? 0) + $costo;
            }
        }

        // ─── Vista por animal ─────────────────────────────────────────────────
        $porAnimal = $animales->map(function ($animal) use ($consumoPorAnimal, $consumoPorLote, $fechaInicio, $fechaFin) {
            $kgDirecto    = $consumoPorAnimal[$animal->id]['kg']    ?? 0;
            $costoDirecto = $consumoPorAnimal[$animal->id]['costo'] ?? 0;

            // Parte proporcional del consumo del lote (partes iguales)
            $kgLote    = 0;
            $costoLote = 0;
            if ($animal->lote_id && isset($consumoPorLote[$animal->lote_id])) {
                $n          = max($animal->lote->animales->count(), 1);
                $kgLote     = ($consumoPorLote[$animal->lote_id]['kg']    ?? 0) / $n;
                $costoLote  = ($consumoPorLote[$animal->lote_id]['costo'] ?? 0) / $n;
            }

            $kgTotal    = $kgDirecto + $kgLote;
            $costoTotal = $costoDirecto + $costoLote;

            $pesoInicio = $this->pesoEnFecha($animal->pesajes, $fechaInicio);
            $pesoFin    = $this->pesoEnFecha($animal->pesajes, $fechaFin);

            $ganancia        = ($pesoInicio !== null && $pesoFin !== null) ? round($pesoFin - $pesoInicio, 2) : null;
            $conversion      = ($ganancia !== null && $ganancia > 0)       ? round($kgTotal / $ganancia, 2)   : null;
            $costoKgGanancia = ($ganancia !== null && $ganancia > 0 && $costoTotal > 0)
                ? round($costoTotal / $ganancia, 2)
                : null;

            return [
                'animal' => [
                    'id'      => $animal->id,
                    'arete'   => $animal->arete,
                    'alias'   => $animal->alias,
                    'especie' => $animal->especie,
                    'raza'    => $animal->raza,
                    'lote'    => $animal->lote?->nombre,
                ],
                'kg_total'          => round($kgTotal, 2),
                'kg_directo'        => round($kgDirecto, 2),
                'kg_lote'           => round($kgLote, 2),
                'costo_total'       => round($costoTotal, 2),
                'peso_inicio'       => $pesoInicio,
                'peso_fin'          => $pesoFin,
                'ganancia_peso'     => $ganancia,
                'conversion'        => $conversion,
                'costo_kg_ganancia' => $costoKgGanancia,
                'tiene_datos_peso'  => $pesoInicio !== null && $pesoFin !== null,
            ];
        })
        ->filter(fn($r) => $r['kg_total'] > 0 || $r['tiene_datos_peso'])
        ->values();

        // ─── Vista por lote ───────────────────────────────────────────────────
        $porLote = $lotes->map(function ($lote) use ($consumoPorAnimal, $consumoPorLote, $fechaInicio, $fechaFin, $animales) {
            $animalesDelLote = $animales->where('lote_id', $lote->id);
            $n               = max($animalesDelLote->count(), 1);

            $kgLoteDirecto    = $consumoPorLote[$lote->id]['kg']    ?? 0;
            $costoLoteDirecto = $consumoPorLote[$lote->id]['costo'] ?? 0;

            $kgIndividual    = 0;
            $costoIndividual = 0;
            foreach ($animalesDelLote as $a) {
                $kgIndividual    += $consumoPorAnimal[$a->id]['kg']    ?? 0;
                $costoIndividual += $consumoPorAnimal[$a->id]['costo'] ?? 0;
            }

            $kgTotal    = $kgLoteDirecto + $kgIndividual;
            $costoTotal = $costoLoteDirecto + $costoIndividual;

            // Detalle por animal dentro del lote
            $gananciaTotal     = 0;
            $animalesConPesaje = 0;
            $detalleAnimales   = [];

            foreach ($animalesDelLote as $a) {
                $pesoInicio = $this->pesoEnFecha($a->pesajes, $fechaInicio);
                $pesoFin    = $this->pesoEnFecha($a->pesajes, $fechaFin);

                // Consumo del animal = directo + parte proporcional del lote
                $kgAnimal    = ($consumoPorAnimal[$a->id]['kg']    ?? 0) + ($kgLoteDirecto / $n);
                $costoAnimal = ($consumoPorAnimal[$a->id]['costo'] ?? 0) + ($costoLoteDirecto / $n);

                $ganancia = null;
                if ($pesoInicio !== null && $pesoFin !== null) {
                    $ganancia       = round($pesoFin - $pesoInicio, 2);
                    $gananciaTotal += $ganancia;
                    $animalesConPesaje++;
                }

                $detalleAnimales[] = [
                    'id'               => $a->id,
                    'arete'            => $a->arete,
                    'alias'            => $a->alias,
                    'peso_inicio'      => $pesoInicio,
                    'peso_fin'         => $pesoFin,
                    'ganancia'         => $ganancia,
                    'kg_total'         => round($kgAnimal, 2),
                    'costo_total'      => round($costoAnimal, 2),
                    'conversion'       => ($ganancia > 0) ? round($kgAnimal / $ganancia, 2)                              : null,
                    'costo_kg_ganancia'=> ($ganancia > 0 && $costoAnimal > 0) ? round($costoAnimal / $ganancia, 2) : null,
                ];
            }

            $gananciaTotal   = round($gananciaTotal, 2);
            $conversion      = ($gananciaTotal > 0) ? round($kgTotal / $gananciaTotal, 2) : null;
            $costoKgGanancia = ($gananciaTotal > 0 && $costoTotal > 0) ? round($costoTotal / $gananciaTotal, 2) : null;

            return [
                'lote'                => ['id' => $lote->id, 'nombre' => $lote->nombre],
                'num_animales'        => $n,
                'animales_con_pesaje' => $animalesConPesaje,
                'kg_total'            => round($kgTotal, 2),
                'kg_lote_directo'     => round($kgLoteDirecto, 2),
                'kg_individual'       => round($kgIndividual, 2),
                'costo_total'         => round($costoTotal, 2),
                'ganancia_total'      => $gananciaTotal,
                'conversion'          => $conversion,
                'costo_kg_ganancia'   => $costoKgGanancia,
                'detalle_animales'    => $detalleAnimales,
            ];
        })
        ->filter(fn($r) => $r['kg_total'] > 0)
        ->values();

        return Inertia::render('/ConversionAlimenticia', [
            'porAnimal'   => $porAnimal,
            'porLote'     => $porLote,
            'fechaInicio' => $fechaInicio,
            'fechaFin'    => $fechaFin,
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Devuelve el peso del pesaje más reciente en o antes de $fecha.
     * Retorna null si no existe ninguno.
     */
    private function pesoEnFecha($pesajes, string $fecha): ?float
    {
        $candidatos = $pesajes->filter(fn($p) => $p->fecha->toDateString() <= $fecha);
        if ($candidatos->isEmpty()) return null;
        return (float) $candidatos->sortByDesc('fecha')->first()->peso;
    }

    /**
     * Calcula el costo por kg de ración desde el snapshot_composicion.
     * Útil cuando la ración fue eliminada y precio_kg no está disponible.
     * Fórmula: Σ(cantidad_insumo_por_kg_racion × costo_promedio_insumo)
     */
    private function costoKgDesdeSnapshot(Alimentacion $ali): ?float
    {
        if (empty($ali->snapshot_composicion)) return null;

        $costoTotal    = 0;
        $cantidadTotal = 0;

        foreach ($ali->snapshot_composicion as $insumo) {
            $cantidad      = (float) ($insumo['cantidad']      ?? 0);
            $costoPromedio = (float) ($insumo['costo_promedio'] ?? 0);
            $costoTotal   += $cantidad * $costoPromedio;
            $cantidadTotal += $cantidad;
        }

        return $cantidadTotal > 0 ? round($costoTotal / $cantidadTotal, 4) : null;
    }
}