<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Costo;
use App\Models\Cria;
use App\Models\DiagnosticoGestacion;
use App\Models\EventoReproductivo;
use App\Models\Parto;
use App\Models\Pesaje;
use App\Models\Venta;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\DB;

/**
 * Indicadores del rebaño ovino.
 *
 * Todas las fórmulas viven aquí, en el backend, y cada una devuelve null
 * cuando no hay denominador en lugar de dividir entre cero o mostrar un 0
 * que parecería un dato real.
 *
 *   Prolificidad   = crías nacidas / partos ocurridos
 *   % Fertilidad   = (servicios que llegaron a parto / servicios) × 100
 *   % Gestación    = (diagnósticos positivos / diagnósticos realizados) × 100
 *   % Mortalidad   = (fallecimientos / rebaño histórico) × 100        [BajaService]
 *   Ganancia diaria = (peso final − peso inicial) / días transcurridos
 */
class IndicadoresOvinosService
{
    /** Meses a partir de los cuales una hembra se considera reproductora. */
    private const MESES_REPRODUCTIVA = EtapaVidaService::MESES_EDAD_REPRODUCTIVA;

    /** Gestación ovina: ~150 días. Se usa para estimar partos próximos. */
    public const DIAS_GESTACION = 150;

    public function __construct(
        protected BajaService $bajaService,
        protected CostoService $costoService,
    ) {
    }

    /**
     * Todos los indicadores en una sola llamada, para reportes y dashboard.
     */
    public function resumen(?string $desde = null, ?string $hasta = null): array
    {
        return [
            'inventario' => $this->inventario(),
            'reproduccion' => $this->reproduccion($desde, $hasta),
            'desarrollo' => $this->desarrollo(),
            'economico' => $this->economico($desde, $hasta),
            'salidas' => $this->bajaService->indicadores($desde, $hasta),
            'periodo' => ['desde' => $desde, 'hasta' => $hasta],
        ];
    }

    // ─── Inventario del rebaño ────────────────────────────────────────────

    public function inventario(): array
    {
        $activos = Animal::activo();

        $hembras = (clone $activos)->where('sexo', 'F');
        $machos = (clone $activos)->where('sexo', 'M');

        $limiteReproductiva = now()->subMonths(self::MESES_REPRODUCTIVA)->toDateString();

        return [
            'total_activos' => (clone $activos)->count(),
            'hembras' => (clone $hembras)->count(),
            'machos' => (clone $machos)->count(),
            // Reproductoras: hembras con edad suficiente y fecha de nacimiento conocida.
            'borregas_reproductoras' => (clone $hembras)
                ->whereNotNull('fecha_nac')
                ->whereDate('fecha_nac', '<=', $limiteReproductiva)
                ->count(),
            'sementales' => (clone $machos)
                ->whereNotNull('fecha_nac')
                ->whereDate('fecha_nac', '<=', $limiteReproductiva)
                ->count(),
            'sin_fecha_nacimiento' => (clone $activos)->whereNull('fecha_nac')->count(),
            'sin_identificador' => (clone $activos)
                ->whereNull('microchip_codigo')
                ->where(fn ($q) => $q->whereNull('arete')->orWhere('arete', ''))
                ->count(),
            'por_etapa' => (clone $activos)
                ->selectRaw('COALESCE(etapa_vida, "sin_definir") as etapa, COUNT(*) as total')
                ->groupByRaw('COALESCE(etapa_vida, "sin_definir")')
                ->pluck('total', 'etapa'),
        ];
    }

    // ─── Reproducción ─────────────────────────────────────────────────────

    public function reproduccion(?string $desde = null, ?string $hasta = null): array
    {
        $eventos = fn (string $tipo) => EventoReproductivo::where('tipo_evento', $tipo)
            ->when($desde, fn ($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha', '<=', $hasta));

        $servicios = (clone $eventos('servicio'))->count();
        $partosIds = (clone $eventos('parto'))->pluck('id');
        $partosOcurridos = $partosIds->count();

        // Diagnósticos del periodo y cuántos resultaron positivos
        $diagnosticoIds = (clone $eventos('diagnostico'))->pluck('id');
        $diagnosticos = DiagnosticoGestacion::whereIn('evento_id', $diagnosticoIds)->count();
        $positivos = DiagnosticoGestacion::whereIn('evento_id', $diagnosticoIds)
            ->where('resultado', 'positivo')
            ->count();

        // Crías del periodo, contadas desde los partos registrados
        $partos = Parto::whereIn('evento_id', $partosIds)->get();
        $criasNacidas = (int) $partos->sum(fn ($p) => (int) $p->numero_crias);
        $criasVivas = (int) $partos->sum(fn ($p) => (int) ($p->crias_vivas ?? 0));
        $criasMuertas = (int) $partos->sum(fn ($p) => (int) ($p->crias_muertas ?? 0));
        $abortos = (int) $partos->sum(fn ($p) => (int) ($p->abortos ?? 0));

        // Servicios que efectivamente terminaron en parto (fertilidad real)
        $serviciosConParto = Parto::whereNotNull('servicio_evento_id')
            ->whereIn('evento_id', $partosIds)
            ->distinct('servicio_evento_id')
            ->count('servicio_evento_id');

        return [
            'servicios' => $servicios,
            'diagnosticos' => $diagnosticos,
            'diagnosticos_positivos' => $positivos,
            'gestaciones_activas' => $this->gestacionesActivas(),
            'partos_ocurridos' => $partosOcurridos,
            'partos_proximos' => $this->partosProximos()->count(),
            'crias_nacidas' => $criasNacidas,
            'crias_vivas' => $criasVivas,
            'crias_muertas' => $criasMuertas,
            'abortos' => $abortos,

            // Prolificidad: crías por parto. Sin partos no hay promedio.
            'prolificidad' => $partosOcurridos > 0
                ? round($criasNacidas / $partosOcurridos, 2)
                : null,

            // Fertilidad: proporción de servicios que llegaron a parto.
            'porcentaje_fertilidad' => $servicios > 0
                ? round(($serviciosConParto / $servicios) * 100, 2)
                : null,

            // Gestación: proporción de diagnósticos que resultaron positivos.
            'porcentaje_gestacion' => $diagnosticos > 0
                ? round(($positivos / $diagnosticos) * 100, 2)
                : null,

            // Supervivencia de las crías al nacimiento.
            'porcentaje_supervivencia_crias' => $criasNacidas > 0
                ? round(($criasVivas / $criasNacidas) * 100, 2)
                : null,
        ];
    }

    /**
     * Hembras con diagnóstico positivo sin parto ni aborto posterior.
     */
    public function gestacionesActivas(): int
    {
        return DiagnosticoGestacion::where('resultado', 'positivo')
            ->with('evento')
            ->get()
            ->filter(function ($dx) {
                $evento = $dx->evento;

                if (! $evento) {
                    return false;
                }

                return ! EventoReproductivo::where('hembra_id', $evento->hembra_id)
                    ->whereIn('tipo_evento', ['parto', 'aborto'])
                    ->whereDate('fecha', '>', $evento->fecha)
                    ->exists();
            })
            ->count();
    }

    /**
     * Gestaciones cuyo parto probable cae dentro de los próximos N días.
     */
    public function partosProximos(int $dias = 21)
    {
        return DiagnosticoGestacion::where('resultado', 'positivo')
            ->whereNotNull('fecha_probable_parto')
            ->whereBetween('fecha_probable_parto', [now()->toDateString(), now()->addDays($dias)->toDateString()])
            ->with('evento.hembra:id,arete,alias')
            ->get()
            ->filter(function ($dx) {
                $evento = $dx->evento;

                if (! $evento) {
                    return false;
                }

                // Si ya parió, deja de ser un parto próximo.
                return ! EventoReproductivo::where('hembra_id', $evento->hembra_id)
                    ->whereIn('tipo_evento', ['parto', 'aborto'])
                    ->whereDate('fecha', '>', $evento->fecha)
                    ->exists();
            })
            ->values();
    }

    // ─── Desarrollo corporal del rebaño ───────────────────────────────────

    public function desarrollo(): array
    {
        $animales = Animal::activo()->pluck('id');

        $pesajes = Pesaje::whereIn('animal_id', $animales)
            ->orderBy('animal_id')
            ->orderBy('fecha')
            ->get()
            ->groupBy('animal_id');

        $ganancias = [];
        $pesosActuales = [];

        foreach ($pesajes as $delAnimal) {
            $primero = $delAnimal->first();
            $ultimo = $delAnimal->last();

            $pesosActuales[$ultimo->animal_id] = (float) $ultimo->peso;

            if ($delAnimal->count() < 2) {
                continue;
            }

            $dias = $primero->fecha->diffInDays($ultimo->fecha, false);

            if ($dias > 0) {
                $ganancias[] = ((float) $ultimo->peso - (float) $primero->peso) / $dias;
            }
        }

        // Peso promedio por etapa de vida confirmada
        $porEtapa = Animal::activo()
            ->whereNotNull('etapa_vida')
            ->whereNotNull('peso')
            ->selectRaw('etapa_vida, AVG(peso) as promedio, COUNT(*) as total')
            ->groupBy('etapa_vida')
            ->get()
            ->mapWithKeys(fn ($f) => [$f->etapa_vida => [
                'promedio' => round((float) $f->promedio, 2),
                'ejemplares' => (int) $f->total,
            ]]);

        return [
            'ejemplares_con_pesaje' => $pesajes->count(),
            'peso_promedio' => count($pesosActuales) > 0
                ? round(array_sum($pesosActuales) / count($pesosActuales), 2)
                : null,
            'ganancia_diaria_promedio' => count($ganancias) > 0
                ? round(array_sum($ganancias) / count($ganancias), 3)
                : null,
            'peso_promedio_por_etapa' => $porEtapa,
            'sin_pesaje' => Animal::activo()->count() - $pesajes->count(),
        ];
    }

    // ─── Económico ────────────────────────────────────────────────────────

    public function economico(?string $desde = null, ?string $hasta = null): array
    {
        $costos = Costo::query()->entreFechas($desde, $hasta)->get();

        $totalCostos = round((float) $costos->sum(fn ($c) => (float) $c->monto), 2);

        $ingresos = round((float) Venta::where('estado_venta', 'completada')
            ->when($desde, fn ($q) => $q->whereDate('fecha_venta', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('fecha_venta', '<=', $hasta))
            ->sum('precio_total'), 2);

        $activos = Animal::activo()->count();

        // Precio estimado del rebaño: suma de las cotizaciones activas.
        $precioRebano = round((float) DB::table('animal_valuations')
            ->join('animals', 'animal_valuations.animal_id', '=', 'animals.id')
            ->where('animal_valuations.estado', 'activa')
            ->where('animals.activo', true)
            // Consulta directa: el scope de Eloquent no interviene, así que el
            // filtro por rancho se pone a mano.
            ->when(
                AppServiceProvider::cuentaActiva(),
                fn ($q, $cuentaId) => $q->where('animal_valuations.owner_id', $cuentaId)
            )
            ->sum('animal_valuations.precio_estimado'), 2);

        return [
            'costos_totales' => $totalCostos,
            'costos_por_categoria' => $costos->groupBy('categoria')
                ->map(fn ($g) => round($g->sum(fn ($c) => (float) $c->monto), 2)),
            'costo_promedio_por_ejemplar' => $activos > 0
                ? round($totalCostos / $activos, 2)
                : null,
            'ingresos' => $ingresos,
            'utilidad' => round($ingresos - $totalCostos, 2),
            'porcentaje_utilidad' => $totalCostos > 0
                ? round((($ingresos - $totalCostos) / $totalCostos) * 100, 2)
                : null,
            'precio_estimado_rebano' => $precioRebano,
        ];
    }

    /**
     * Costos acumulados por lote, para comparar el gasto entre grupos.
     */
    public function costosPorLote(?string $desde = null, ?string $hasta = null)
    {
        return Costo::query()
            ->entreFechas($desde, $hasta)
            ->whereNotNull('lote_id')
            ->with('lote:id,nombre')
            ->get()
            ->groupBy('lote_id')
            ->map(fn ($grupo) => [
                'lote' => $grupo->first()->lote?->nombre,
                'total' => round($grupo->sum(fn ($c) => (float) $c->monto), 2),
                'movimientos' => $grupo->count(),
            ])
            ->values();
    }
}
