<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\DonadorExterno;
use App\Models\EventoReproductivo;
use App\Models\ServicioReproductivo;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Resuelve qué servicio(s) reproductivo(s) pudieron haber originado un
 * parto, y qué padre corresponde a cada uno.
 *
 * IMPORTANTE: esta clase NO decide un padre automáticamente. Cuando hay más
 * de un servicio dentro de la ventana de gestación esperada (por ejemplo,
 * dos montas con machos distintos con pocos días de diferencia), la
 * decisión final la toma la persona usuaria desde el frontend, eligiendo
 * uno de los candidatos que aquí se calculan.
 *
 * Esta clase es independiente de HistorialNacimientoService a propósito:
 * HistorialNacimientoService reconstruye un servicio/parto HISTÓRICO
 * ficticio cuando se da de alta un animal directamente desde el CRUD (sin
 * que haya existido un registro reproductivo real). Esta clase, en cambio,
 * busca entre EventoReproductivo/ServicioReproductivo que YA EXISTEN en la
 * base de datos, para el flujo real de registro de partos
 * (ServicioReproductivoController → PartoController). Mezclar ambas
 * responsabilidades haría más difícil mantener cada flujo por separado.
 */
class PaternidadProbableService
{
    /**
     * Ventana de tolerancia (en días) alrededor de la duración de
     * gestación esperada dentro de la cual un servicio se considera un
     * candidato plausible para un parto determinado. Ajustable por llamada
     * si en algún caso se necesita una ventana distinta.
     */
    private const TOLERANCIA_DIAS_DEFECTO = 15;

    /**
     * Busca los servicios reproductivos de una hembra que pudieron haber
     * originado un parto en la fecha indicada, y los ordena del más al
     * menos probable según qué tan cerca cae su fecha del día "ideal" de
     * concepción (fecha de parto − días de gestación promedio).
     *
     * @return Collection<int, array{
     *     servicio_evento_id: int,
     *     fecha_servicio: string,
     *     tipo_servicio: string,
     *     dias_gestacion_reales: int,
     *     diferencia_dias: int,
     *     probabilidad: float,
     *     padre_id: ?int,
     *     padre_externo_id: ?int,
     *     padre_nombre: ?string,
     * }>
     */
    public function candidatosParaParto(
        Animal $hembra,
        Carbon $fechaParto,
        int $diasGestacionPromedio,
        ?int $toleranciaDias = null
    ): Collection {
        $toleranciaDias ??= self::TOLERANCIA_DIAS_DEFECTO;

        $ventanaInicio = $fechaParto->copy()->subDays($diasGestacionPromedio + $toleranciaDias);
        $ventanaFin = $fechaParto->copy()->subDays($diasGestacionPromedio - $toleranciaDias);

        return EventoReproductivo::where('hembra_id', $hembra->id)
            ->where('tipo_evento', 'servicio')
            ->whereDate('fecha', '>=', $ventanaInicio->toDateString())
            ->whereDate('fecha', '<=', $ventanaFin->toDateString())
            ->with('servicio.pajilla')
            ->get()
            ->filter(fn (EventoReproductivo $evento) => $evento->servicio !== null)
            ->map(function (EventoReproductivo $evento) use ($fechaParto, $diasGestacionPromedio) {
                $diasReales = Carbon::parse($evento->fecha)->diffInDays($fechaParto);
                $diferencia = abs($diasReales - $diasGestacionPromedio);

                [$padreId, $padreExternoId, $padreNombre] = $this->resolverPadre($evento->servicio);

                return [
                    'servicio_evento_id' => $evento->id,
                    'fecha_servicio' => Carbon::parse($evento->fecha)->toDateString(),
                    'tipo_servicio' => $evento->servicio->tipo_servicio,
                    'dias_gestacion_reales' => $diasReales,
                    'diferencia_dias' => $diferencia,
                    // Señal simple para que el frontend pueda ordenar o
                    // mostrar un indicador visual de qué tan probable es
                    // cada candidato. Entre más cerca de 0 esté la
                    // diferencia, más alta la probabilidad. Es un heurístico
                    // simple, no un cálculo estadístico riguroso — ajustable
                    // si más adelante quieres algo más sofisticado.
                    'probabilidad' => max(0, 100 - ($diferencia * 5)),
                    'padre_id' => $padreId,
                    'padre_externo_id' => $padreExternoId,
                    'padre_nombre' => $padreNombre,
                ];
            })
            ->sortBy('diferencia_dias')
            ->values();
    }

    /**
     * Resuelve el padre real de un servicio ya confirmado: macho_id directo
     * si fue monta, o a través de la pajilla (animal interno o donador
     * externo) si fue un método artificial.
     *
     * Pública porque PartoController también la necesita al momento de
     * confirmar el servicio elegido por la persona usuaria — así no se
     * duplica la lógica entre el cálculo de candidatos y el registro final
     * del parto.
     *
     * @return array{0: ?int, 1: ?int, 2: ?string} [padre_id, padre_externo_id, nombre]
     */
    public function resolverPadre(?ServicioReproductivo $servicio): array
    {
        if (!$servicio) {
            return [null, null, null];
        }

        if (in_array($servicio->tipo_servicio, ['monta_natural', 'monta_controlada'])) {
            if (empty($servicio->macho_id)) {
                return [null, null, null];
            }

            $macho = Animal::find($servicio->macho_id);

            return [$servicio->macho_id, null, $macho?->alias ?: $macho?->arete];
        }

        if (in_array($servicio->tipo_servicio, ['inseminacion_artificial', 'iatf', 'transferencia_embriones', 'fiv'])) {
            $pajilla = $servicio->pajilla;

            if (!$pajilla) {
                return [null, null, null];
            }

            if (!empty($pajilla->animal_id)) {
                $animal = Animal::find($pajilla->animal_id);

                return [$pajilla->animal_id, null, $animal?->alias ?: $animal?->arete];
            }

            if (!empty($pajilla->donador_externo_id)) {
                $donador = DonadorExterno::find($pajilla->donador_externo_id);

                return [null, $pajilla->donador_externo_id, $donador?->nombre];
            }
        }

        return [null, null, null];
    }
}