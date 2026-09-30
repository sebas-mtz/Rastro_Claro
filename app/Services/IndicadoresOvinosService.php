<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Costo;
use App\Models\EventoReproductivo;
use App\Models\Parto;
use App\Models\Pesaje;
use App\Models\Venta;
use App\Models\Baja;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\DB;

/**
 * Indicadores generales del rebaño (dashboard/reportes).
 *
 * IMPORTANTE: "fertilidad" y "porcentaje_gestacion" deben coincidir
 * exactamente con las fórmulas de EventoReproductivoController::estadisticas(),
 * que ya está en producción:
 *   fertilidad          = partos / servicios × 100
 *   porcentaje_gestacion = gestantes / servicios × 100
 * No renombrar ni cambiar estas fórmulas sin actualizar también ese endpoint.
 */
class IndicadoresOvinosService
{
    public function resumen(?string $desde = null, ?string $hasta = null): array
    {
        return [
            'inventario' => $this->inventario(),
            'reproduccion' => $this->reproduccion($desde, $hasta),
            'desarrollo' => $this->desarrollo(),
            'economico' => $this->economico($desde, $hasta),
            'salidas' => $this->salidas($desde, $hasta),
            'periodo' => ['desde' => $desde, 'hasta' => $hasta],
        ];
    }

    public function inventario(): array
    {
        $animales = Animal::activo()->get();
        $hembras = $animales->filter(fn (Animal $a) => $a->esHembra());
        $machos = $animales->filter(fn (Animal $a) => strtoupper((string) $a->sexo) === 'M');
        $apto = fn (Animal $a) => $a->fecha_nac !== null && $a->esAptoParaReproduccion();

        return [
            'total_activos' => $animales->count(),
            'hembras' => $hembras->count(),
            'machos' => $machos->count(),
            'hembras_reproductoras' => $hembras->filter($apto)->count(),
            'sementales' => $machos->filter($apto)->count(),
            'sin_fecha_nacimiento' => $animales->filter(fn (Animal $a) => $a->fecha_nac === null)->count(),
            'sin_identificador' => $animales->filter(
                fn (Animal $a) => blank($a->arete) && blank($a->identificador) && blank($a->siniiga_id) && blank($a->numero_registro)
            )->count(),
        ];
    }

    public function reproduccion(?string $desde = null, ?string $hasta = null): array
    {
        $eventos = fn (string $tipo) => EventoReproductivo::query()
            ->where('tipo_evento', $tipo)
            ->when($desde, fn ($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha', '<=', $hasta));

        $totalServicios = (clone $eventos('servicio'))->count();
        $diagnosticos = (clone $eventos('diagnostico'))->count();
        $gestantes = (clone $eventos('diagnostico'))->whereHas('diagnostico', fn ($q) => $q->where('resultado', 'positivo'))->count();
        $partosIds = (clone $eventos('parto'))->pluck('id');
        $partos = $partosIds->count();

        $fertilidad = $totalServicios > 0 ? round(($partos / $totalServicios) * 100, 1) : 0;
        $porcentajeGestacion = $totalServicios > 0 ? round(($gestantes / $totalServicios) * 100, 1) : 0;

        $registrosParto = Parto::query()->whereIn('evento_id', $partosIds)->with(['crias:id,parto_id,condicion'])->get();
        $criasNacidas = (int) $registrosParto->sum(fn (Parto $p) => (int) $p->numero_crias);
        $criasRegistradas = $registrosParto->flatMap(fn (Parto $p) => $p->crias);
        $cond = fn ($c) => strtolower(trim((string) $c->condicion));
        $criasVivas = $criasRegistradas->filter(fn ($c) => in_array($cond($c), ['vivo', 'viva'], true))->count();
        $criasMuertas = $criasRegistradas->filter(fn ($c) => in_array($cond($c), ['muerto', 'muerta', 'nacido_muerto', 'nacida_muerta'], true))->count();
        $criasConCondicion = $criasVivas + $criasMuertas;

        $serviciosConParto = Parto::query()->whereIn('evento_id', $partosIds)->whereNotNull('servicio_evento_id')->distinct()->count('servicio_evento_id');

        return [
            'total_servicios' => $totalServicios,
            'gestantes' => $gestantes,
            'partos' => $partos,
            'fertilidad' => $fertilidad,
            'porcentaje_gestacion' => $porcentajeGestacion,
            'diagnosticos' => $diagnosticos,
            'partos_proximos' => $this->partosProximos()->count(),
            'crias_nacidas' => $criasNacidas,
            'crias_vivas' => $criasVivas,
            'crias_muertas' => $criasMuertas,
            'prolificidad' => $partos > 0 ? round($criasNacidas / $partos, 2) : null,
            'porcentaje_servicios_con_parto' => $totalServicios > 0 ? round(($serviciosConParto / $totalServicios) * 100, 2) : null,
            'porcentaje_diagnosticos_positivos' => $diagnosticos > 0 ? round(($gestantes / $diagnosticos) * 100, 2) : null,
            'porcentaje_supervivencia_crias' => $criasConCondicion > 0 ? round(($criasVivas / $criasConCondicion) * 100, 2) : null,
        ];
    }

    public function partosProximos(int $dias = 21)
    {
        return EventoReproductivo::query()
            ->where('tipo_evento', 'diagnostico')
            ->whereHas('diagnostico', fn ($q) => $q->where('resultado', 'positivo')
                ->whereNotNull('fecha_probable_parto')
                ->whereBetween('fecha_probable_parto', [now()->toDateString(), now()->addDays($dias)->toDateString()]))
            ->with(['hembra:id,arete,alias', 'diagnostico'])
            ->get()
            ->filter(fn (EventoReproductivo $e) => ! EventoReproductivo::query()
                ->where('hembra_id', $e->hembra_id)
                ->where('tipo_evento', 'parto')
                ->whereDate('fecha', '>', $e->fecha)
                ->exists())
            ->values();
    }

    public function desarrollo(): array
    {
        $animales = Animal::activo()->pluck('id');
        $pesajes = Pesaje::query()->whereIn('animal_id', $animales)->orderBy('animal_id')->orderBy('fecha')->get()->groupBy('animal_id');

        $ganancias = [];
        $pesosActuales = [];

        foreach ($pesajes as $pesajesAnimal) {
            $primero = $pesajesAnimal->first();
            $ultimo = $pesajesAnimal->last();
            if (! $ultimo) continue;

            $pesosActuales[$ultimo->animal_id] = (float) $ultimo->peso;
            if ($pesajesAnimal->count() < 2) continue;

            $dias = $primero->fecha->diffInDays($ultimo->fecha, false);
            if ($dias <= 0) continue;

            $ganancias[] = ((float) $ultimo->peso - (float) $primero->peso) / $dias;
        }

        return [
            'ejemplares_con_pesaje' => $pesajes->count(),
            'peso_promedio' => count($pesosActuales) > 0 ? round(array_sum($pesosActuales) / count($pesosActuales), 2) : null,
            'ganancia_diaria_promedio' => count($ganancias) > 0 ? round(array_sum($ganancias) / count($ganancias), 3) : null,
            'sin_pesaje' => $animales->count() - $pesajes->count(),
        ];
    }

    public function economico(?string $desde = null, ?string $hasta = null): array
    {
        $costos = Costo::query()->entreFechas($desde, $hasta)->get();
        $totalCostos = round((float) $costos->sum(fn ($c) => (float) $c->monto), 2);

        $ingresos = round((float) Venta::query()
            ->where('estado_venta', 'completada')
            ->when($desde, fn ($q) => $q->whereDate('fecha_venta', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha_venta', '<=', $hasta))
            ->sum('precio_total'), 2);

        $animalesActivos = Animal::activo()->pluck('id');
        $cantidadActivos = $animalesActivos->count();

        $precioRebano = round((float) DB::table('animal_valuations')
            ->where('estado', 'activa')
            ->whereIn('animal_id', $animalesActivos)
            ->when(AppServiceProvider::cuentaActiva(), fn ($q, $cuentaId) => $q->where('owner_id', $cuentaId))
            ->sum('precio_estimado'), 2);

        $utilidad = round($ingresos - $totalCostos, 2);

        return [
            'costos_totales' => $totalCostos,
            'costos_por_categoria' => $costos->groupBy('categoria')->map(fn ($g) => round($g->sum(fn ($c) => (float) $c->monto), 2)),
            'costo_promedio_por_ejemplar' => $cantidadActivos > 0 ? round($totalCostos / $cantidadActivos, 2) : null,
            'ingresos' => $ingresos,
            'utilidad' => $utilidad,
            'porcentaje_utilidad' => $totalCostos > 0 ? round(($utilidad / $totalCostos) * 100, 2) : null,
            'precio_estimado_rebano' => $precioRebano,
        ];
    }

    public function salidas(?string $desde = null, ?string $hasta = null): array
{
    $rebanoHistorico = Animal::query()->count();

    $bajas = Baja::query()
        ->when($desde, fn ($q) => $q->whereDate('fecha', '>=', $desde))
        ->when($hasta, fn ($q) => $q->whereDate('fecha', '<=', $hasta))
        ->get();

    $muertes = $bajas->where('tipo_salida', Baja::FALLECIMIENTO)->count();

    $otrasBajasPorTipo = $bajas
        ->where('tipo_salida', '!=', Baja::FALLECIMIENTO)
        ->groupBy('tipo_salida')
        ->map->count();

    $ventas = Animal::query()
        ->whereHas('ventas', function ($query) use ($desde, $hasta) {
            $query
                ->where('tipo_venta', 'animal')
                ->where('estado_venta', 'completada')
                ->when($desde, fn ($q) => $q->whereDate('fecha_venta', '>=', $desde))
                ->when($hasta, fn ($q) => $q->whereDate('fecha_venta', '<=', $hasta));
        })
        ->count();

    $faenados = Animal::query()
        ->whereHas('faenas', function ($query) use ($desde, $hasta) {
            $query
                ->when($desde, fn ($q) => $q->whereDate('fecha', '>=', $desde))
                ->when($hasta, fn ($q) => $q->whereDate('fecha', '<=', $hasta));
        })
        ->count();

    $sacrificados = Animal::query()
        ->whereHas('sacrificios', function ($query) use ($desde, $hasta) {
            $query
                ->when($desde, fn ($q) => $q->whereDate('fecha', '>=', $desde))
                ->when($hasta, fn ($q) => $q->whereDate('fecha', '<=', $hasta));
        })
        ->count();

    $totalSalidas = $muertes
        + $ventas
        + $faenados
        + $sacrificados
        + $otrasBajasPorTipo->sum();

    return [
        // Se conservan iguales — nada que ya consuma esto se rompe.
        'muertes' => $muertes,
        'ventas' => $ventas,
        'porcentaje_mortalidad' => $rebanoHistorico > 0
            ? round(($muertes / $rebanoHistorico) * 100, 2)
            : null,

        // Nuevo: las categorías que antes no se contaban.
        'faenados' => $faenados,
        'sacrificados' => $sacrificados,
        'otras_bajas_por_tipo' => $otrasBajasPorTipo,
        'total_salidas' => $totalSalidas,
        'porcentaje_salidas' => $rebanoHistorico > 0
            ? round(($totalSalidas / $rebanoHistorico) * 100, 2)
            : null,
    ];
}

    public function costosPorLote(?string $desde = null, ?string $hasta = null)
    {
        return Costo::query()->entreFechas($desde, $hasta)
            ->whereNotNull('lote_id')
            ->with('lote:id,nombre')
            ->get()
            ->groupBy('lote_id')
            ->map(fn ($g) => [
                'lote' => $g->first()->lote?->nombre,
                'total' => round($g->sum(fn ($c) => (float) $c->monto), 2),
                'movimientos' => $g->count(),
            ])
            ->values();
    }
}