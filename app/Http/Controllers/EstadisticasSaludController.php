<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\EventoSalud;
use App\Models\Tratamiento;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class EstadisticasSaludController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $hoy         = Carbon::today();
        $hace12Meses = $hoy->copy()->subMonths(11)->startOfMonth();

        // ── KPIs globales ─────────────────────────────────────────────────────
        $kpis = [
            'total_eventos'        => EventoSalud::count(),
            'aplicados'            => EventoSalud::where('estado', EventoSalud::ESTADO_APLICADA)->count(),
            'pendientes'           => EventoSalud::where('estado', EventoSalud::ESTADO_PENDIENTE)->count(),
            'vencidos'             => EventoSalud::where('estado', EventoSalud::ESTADO_VENCIDA)->count(),
            'tratamientos_activos' => Tratamiento::where('estado', Tratamiento::ESTADO_ACTIVO)->count(),
            'vacunaciones_mes'     => EventoSalud::where('tipo', EventoSalud::TIPO_VACUNACION)
                ->where('estado', EventoSalud::ESTADO_APLICADA)
                ->whereMonth('fecha_aplicacion', $hoy->month)
                ->whereYear('fecha_aplicacion', $hoy->year)
                ->count(),
        ];

        // ── Distribución por estado ───────────────────────────────────────────
        $porEstado = EventoSalud::selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

        // ── Distribución por tipo ─────────────────────────────────────────────
        $porTipo = EventoSalud::selectRaw('tipo, count(*) as total')
            ->groupBy('tipo')
            ->pluck('total', 'tipo')
            ->toArray();

        // ── Tendencia mensual: últimos 12 meses ───────────────────────────────
        $tendenciaMensual = EventoSalud::selectRaw(
                "DATE_FORMAT(fecha_programada, '%Y-%m')                          AS mes,
                 SUM(CASE WHEN estado = 'aplicada'  THEN 1 ELSE 0 END)          AS aplicadas,
                 SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END)          AS pendientes,
                 SUM(CASE WHEN estado = 'vencida'   THEN 1 ELSE 0 END)          AS vencidas"
            )
            ->where('fecha_programada', '>=', $hace12Meses)
            ->groupByRaw("DATE_FORMAT(fecha_programada, '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(fecha_programada, '%Y-%m')")
            ->get()
            ->map(fn($row) => [
                'mes'        => $row->mes,
                'label'      => Carbon::createFromFormat('Y-m', $row->mes)
                                    ->locale('es')->isoFormat('MMM YY'),
                'aplicadas'  => (int) $row->aplicadas,
                'pendientes' => (int) $row->pendientes,
                'vencidas'   => (int) $row->vencidas,
            ]);

        // ── Cobertura de vacunación (animales individuales) ───────────────────
        $totalAnimales     = Animal::count();
        $animalesVacunados = EventoSalud::where('tipo', EventoSalud::TIPO_VACUNACION)
            ->where('estado', EventoSalud::ESTADO_APLICADA)
            ->where('fecha_programada', '>=', $hace12Meses)
            ->whereNotNull('animal_id')
            ->distinct('animal_id')
            ->count('animal_id');

        $coberturaVacunacion = [
            'total'      => $totalAnimales,
            'vacunados'  => $animalesVacunados,
            'porcentaje' => $totalAnimales > 0
                ? round($animalesVacunados / $totalAnimales * 100, 1)
                : 0,
        ];

        // ── Top 5 diagnósticos (excluye texto automático de vacunas) ──────────
        $topDiagnosticos = EventoSalud::selectRaw('diagnostico, count(*) as total')
            ->whereNotNull('diagnostico')
            ->where('diagnostico', 'not like', 'Vacunación programada%')
            ->where('diagnostico', 'not like', 'Refuerzo programado%')
            ->groupBy('diagnostico')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'nombre' => $r->diagnostico,
                'total'  => (int) $r->total,
            ]);

        // ── Tratamientos por estado ───────────────────────────────────────────
        $tratamientosPorEstado = Tratamiento::selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

        return response()->json([
            'kpis'                  => $kpis,
            'por_estado'            => $porEstado,
            'por_tipo'              => $porTipo,
            'tendencia_mensual'     => $tendenciaMensual,
            'cobertura_vacunacion'  => $coberturaVacunacion,
            'top_diagnosticos'      => $topDiagnosticos,
            'tratamientos_por_estado' => $tratamientosPorEstado,
        ]);
    }
}