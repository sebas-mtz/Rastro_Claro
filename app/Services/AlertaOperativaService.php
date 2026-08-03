<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Cria;
use App\Models\EventoSalud;
use App\Models\Parto;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\DB;

/**
 * Alertas operativas del manejo ovino.
 *
 * Son avisos de trabajo pendiente en el rebaño, NO errores del sistema.
 * Cada alerta indica qué pasa, a cuántos ejemplares afecta y a dónde ir.
 */
class AlertaOperativaService
{
    public const CRITICA = 'critica';
    public const ATENCION = 'atencion';
    public const INFORMATIVA = 'informativa';

    public function __construct(protected IndicadoresOvinosService $indicadores)
    {
    }

    /**
     * Todas las alertas vigentes, ordenadas por urgencia.
     */
    public function todas(): array
    {
        $alertas = array_filter([
            $this->partosProximos(),
            $this->vacunasVencidas(),
            $this->vacunasProximas(),
            $this->enPeriodoRetiro(),
            $this->diagnosticosPendientes(),
            $this->criasSinIdentificar(),
            $this->destetesProximos(),
            $this->ejemplaresSinIdentificador(),
            $this->identificadoresDuplicados(),
            $this->vendidosAunActivos(),
            $this->ejemplaresSinGenealogia(),
            $this->ejemplaresSinPesajeReciente(),
        ]);

        $orden = [self::CRITICA => 0, self::ATENCION => 1, self::INFORMATIVA => 2];

        usort($alertas, fn ($a, $b) => $orden[$a['nivel']] <=> $orden[$b['nivel']]);

        return array_values($alertas);
    }

    private function alerta(string $nivel, string $titulo, string $detalle, int $cantidad, ?string $ruta = null): ?array
    {
        if ($cantidad <= 0) {
            return null;
        }

        return [
            'nivel' => $nivel,
            'titulo' => $titulo,
            'detalle' => $detalle,
            'cantidad' => $cantidad,
            'ruta' => $ruta,
        ];
    }

    // ─── Reproducción ─────────────────────────────────────────────────────

    private function partosProximos(): ?array
    {
        $proximos = $this->indicadores->partosProximos(21);

        return $this->alerta(
            self::CRITICA,
            'Borregas próximas al parto',
            'Tienen parto probable dentro de los próximos 21 días. Conviene moverlas al lote de maternidad.',
            $proximos->count(),
            '/reproduccion'
        );
    }

    private function diagnosticosPendientes(): ?array
    {
        // Servicios de hace más de 45 días sin diagnóstico posterior.
        $limite = now()->subDays(45)->toDateString();

        $pendientes = DB::table('evento_reproductivos as s')
            ->where('s.tipo_evento', 'servicio')
            ->whereDate('s.fecha', '<=', $limite)
            // Consulta directa: el scope de Eloquent no interviene, así que el
            // filtro por rancho se pone a mano.
            ->when(AppServiceProvider::cuentaActiva(), fn ($q, $cuentaId) => $q->where('s.owner_id', $cuentaId))
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('evento_reproductivos as d')
                    ->whereColumn('d.hembra_id', 's.hembra_id')
                    ->where('d.tipo_evento', 'diagnostico')
                    ->whereColumn('d.fecha', '>', 's.fecha');
            })
            ->count();

        return $this->alerta(
            self::ATENCION,
            'Diagnósticos de gestación pendientes',
            'Servicios de hace más de 45 días que todavía no tienen diagnóstico registrado.',
            $pendientes,
            '/reproduccion'
        );
    }

    private function destetesProximos(): ?array
    {
        // Crías vivas de entre 60 y 90 días sin fecha de destete registrada.
        $proximos = Cria::where('condicion', 'vivo')
            ->whereNull('fecha_destete')
            ->whereHas('parto.evento', function ($q) {
                $q->whereBetween('fecha', [
                    now()->subDays(90)->toDateString(),
                    now()->subDays(60)->toDateString(),
                ]);
            })
            ->count();

        return $this->alerta(
            self::ATENCION,
            'Destetes próximos',
            'Crías de entre 60 y 90 días de edad que aún no tienen destete registrado.',
            $proximos,
            '/reproduccion'
        );
    }

    private function criasSinIdentificar(): ?array
    {
        $sinArete = Cria::where('condicion', 'vivo')
            ->whereNull('animal_id')
            ->count();

        return $this->alerta(
            self::ATENCION,
            'Crías pendientes de identificar',
            'Nacieron vivas pero todavía no tienen arete definitivo ni ficha propia en el rebaño.',
            $sinArete,
            '/reproduccion'
        );
    }

    // ─── Sanidad ──────────────────────────────────────────────────────────

    private function vacunasVencidas(): ?array
    {
        $vencidas = EventoSalud::where('estado', EventoSalud::ESTADO_PENDIENTE)
            ->whereDate('fecha_programada', '<', now()->toDateString())
            ->count();

        return $this->alerta(
            self::CRITICA,
            'Actividades sanitarias vencidas',
            'Vacunas, desparasitaciones o revisiones programadas cuya fecha ya pasó.',
            $vencidas,
            '/salud'
        );
    }

    private function vacunasProximas(): ?array
    {
        $proximas = EventoSalud::where('estado', EventoSalud::ESTADO_PENDIENTE)
            ->whereBetween('fecha_programada', [
                now()->toDateString(),
                now()->addDays(7)->toDateString(),
            ])
            ->count();

        return $this->alerta(
            self::ATENCION,
            'Actividades sanitarias de los próximos 7 días',
            'Programadas para esta semana.',
            $proximas,
            '/salud'
        );
    }

    /**
     * Ejemplares que recibieron un medicamento y siguen dentro del periodo de
     * retiro: no deben destinarse a consumo ni a venta para sacrificio.
     */
    private function enPeriodoRetiro(): ?array
    {
        $enRetiro = EventoSalud::whereNotNull('fecha_fin_retiro')
            ->whereDate('fecha_fin_retiro', '>=', now()->toDateString())
            ->whereNotNull('animal_id')
            ->distinct('animal_id')
            ->count('animal_id');

        return $this->alerta(
            self::CRITICA,
            'Ejemplares en periodo de retiro',
            'Recibieron medicamento y todavía no pueden destinarse a consumo ni a venta para sacrificio.',
            $enRetiro,
            '/calendario-sanitario'
        );
    }

    // ─── Identificación y datos ───────────────────────────────────────────

    private function ejemplaresSinIdentificador(): ?array
    {
        $sinId = Animal::activo()
            ->whereNull('microchip_codigo')
            ->where(fn ($q) => $q->whereNull('arete')->orWhere('arete', ''))
            ->count();

        return $this->alerta(
            self::ATENCION,
            'Ejemplares sin identificación',
            'No tienen arete ni microchip registrados.',
            $sinId,
            '/animales'
        );
    }

    /**
     * Aretes o microchips repetidos dentro de la misma cuenta.
     */
    private function identificadoresDuplicados(): ?array
    {
        $aretes = Animal::activo()
            ->whereNotNull('arete')->where('arete', '!=', '')
            ->select('arete')
            ->groupBy('arete')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        $microchips = Animal::activo()
            ->whereNotNull('microchip_codigo')
            ->select('microchip_codigo')
            ->groupBy('microchip_codigo')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        return $this->alerta(
            self::CRITICA,
            'Identificadores duplicados',
            'Hay aretes o microchips repetidos entre ejemplares activos. Revisa la captura.',
            $aretes + $microchips,
            '/animales'
        );
    }

    private function vendidosAunActivos(): ?array
    {
        // Ejemplares con venta completada que siguen contando como activos.
        $inconsistentes = Animal::activo()
            ->whereHas('ventas', fn ($q) => $q
                ->where('tipo_venta', 'animal')
                ->where('estado_venta', 'completada'))
            ->count();

        return $this->alerta(
            self::CRITICA,
            'Ejemplares vendidos que siguen activos',
            'Tienen una venta completada pero no se les ha registrado la salida del rebaño.',
            $inconsistentes,
            '/bajas'
        );
    }

    private function ejemplaresSinGenealogia(): ?array
    {
        $sinPadres = Animal::activo()
            ->whereNull('madre_id')
            ->whereNull('padre_id')
            ->whereNull('padre_externo_id')
            ->count();

        return $this->alerta(
            self::INFORMATIVA,
            'Ejemplares sin genealogía registrada',
            'No tienen madre ni padre capturados, lo que limita la trazabilidad y el control de consanguinidad.',
            $sinPadres,
            '/animales'
        );
    }

    private function ejemplaresSinPesajeReciente(): ?array
    {
        $limite = now()->subDays(90)->toDateString();

        $sinPesaje = Animal::activo()
            ->whereDoesntHave('pesajes', fn ($q) => $q->whereDate('fecha', '>=', $limite))
            ->count();

        return $this->alerta(
            self::INFORMATIVA,
            'Ejemplares sin pesaje reciente',
            'Sin registro de peso en los últimos 90 días.',
            $sinPesaje,
            '/pesajes'
        );
    }
}
