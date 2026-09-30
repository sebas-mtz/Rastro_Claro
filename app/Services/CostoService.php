<?php

namespace App\Services;

use App\Models\Costo;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CostoService
{
    /**
     * Aplica los filtros comunes de la petición sobre el query de costos.
     */
    public function aplicarFiltros(Request $request)
    {
        return Costo::query()
            ->categoria($request->categoria)
            ->entreFechas($request->fecha_desde, $request->fecha_hasta)
            ->deAnimal($request->animal_id)
            ->deLote($request->lote_id)
            ->when($request->faena_id, fn ($q) => $q->where('faena_id', $request->faena_id))
            ->when($request->sacrificio_id, fn ($q) => $q->where('sacrificio_id', $request->sacrificio_id))
            ->when($request->tipo_costo, fn ($q) => $q->where('tipo_costo', $request->tipo_costo))
            ->when($request->concepto, fn ($q) => $q->where('concepto', 'like', '%' . $request->concepto . '%'));
    }

    public function totales(Request $request): array
    {
        $costos = $this->aplicarFiltros($request)->get();

        $totalGeneral = (float) $costos->sum(fn ($c) => (float) $c->monto);

        $porCategoria = $costos->groupBy('categoria')->map(
            fn ($grupo) => round($grupo->sum(fn ($c) => (float) $c->monto), 2)
        );

        $porAnimal = $costos->whereNotNull('animal_id')->groupBy('animal_id')->map(
            fn ($grupo) => round($grupo->sum(fn ($c) => (float) $c->monto), 2)
        );

        $porLote = $costos->whereNotNull('lote_id')->groupBy('lote_id')->map(
            fn ($grupo) => round($grupo->sum(fn ($c) => (float) $c->monto), 2)
        );

        $porPeriodo = $costos->groupBy(fn ($c) => $c->fecha->format('Y-m'))->map(
            fn ($grupo) => round($grupo->sum(fn ($c) => (float) $c->monto), 2)
        )->sortKeys();

        $animalesConCosto = $porAnimal->count();
        $costoPromedioPorAnimal = $animalesConCosto > 0
            ? round($porAnimal->sum() / $animalesConCosto, 2)
            : 0.0;

        return [
            'total_general' => round($totalGeneral, 2),
            'total_por_categoria' => $porCategoria,
            'total_por_animal' => $porAnimal,
            'total_por_lote' => $porLote,
            'total_por_periodo' => $porPeriodo,
            'costo_promedio_por_animal' => $costoPromedioPorAnimal,
            'costo_total_produccion' => round($totalGeneral, 2),
            'cantidad_registros' => $costos->count(),
        ];
    }

    public function compararIngresosCostos(?string $desde = null, ?string $hasta = null): array
    {
        $costos = Costo::query()
            ->entreFechas($desde, $hasta)
            ->sum('monto');

        $ingresos = Venta::query()
            ->where('estado_venta', 'completada')
            ->when($desde, fn ($q) => $q->whereDate('fecha_venta', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha_venta', '<=', $hasta))
            ->sum('precio_total');

        $costos = round((float) $costos, 2);
        $ingresos = round((float) $ingresos, 2);
        $utilidad = round($ingresos - $costos, 2);

        return [
            'ingresos' => $ingresos,
            'costos' => $costos,
            'utilidad' => $utilidad,
            'estado' => $utilidad >= 0 ? 'utilidad' : 'perdida',
        ];
    }

    /**
     * El cotejo es por rancho, no por persona.
     *
     * Mientras cada cuenta tenía un solo usuario daba igual; ahora que varias
     * personas comparten rebaño, dos capturas idénticas del mismo gasto en
     * cinco minutos son un duplicado aunque las haya hecho gente distinta.
     */
    public function existeDuplicadoReciente(array $datos, ?int $cuentaId): bool
    {
        return Costo::query()
            ->when($cuentaId, fn ($q) => $q->where('owner_id', $cuentaId))
            ->where('categoria', $datos['categoria'])
            ->where('concepto', $datos['concepto'])
            ->where('monto', $datos['monto'])
            ->whereDate('fecha', $datos['fecha'])
            ->where('animal_id', $datos['animal_id'] ?? null)
            ->where('lote_id', $datos['lote_id'] ?? null)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();
    }
}
