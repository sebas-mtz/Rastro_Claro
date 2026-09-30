<?php

namespace App\Services;

use App\Models\ActividadTrabajador;
use App\Models\Animal;
use App\Models\Costo;
use App\Models\Lote;
use App\Models\Trabajador;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Cálculo del costo de mano de obra y su reflejo en el módulo de costos.
 *
 * Todo el dinero se calcula aquí, en el servidor. El navegador puede mostrar
 * una vista previa, pero el importe que se guarda es siempre el que sale de
 * este servicio a partir de las horas y la tarifa.
 *
 * Fórmulas:
 *   Por hora    → Costo = Horas_Trabajadas × Costo_Por_Hora
 *   Por jornada → Costo = Jornadas × Costo_Por_Jornada
 *
 * Cuando la actividad abarca varios ejemplares:
 *   Costo_Individual = Costo_Total / Número_De_Animales_Atendidos
 */
class ManoObraService
{
    /** Categoría bajo la que la mano de obra entra al módulo de costos. */
    public const CATEGORIA_COSTO = 'mano_obra';

    /** Ventana para detectar un doble envío accidental del mismo formulario. */
    private const MINUTOS_ANTIDUPLICADO = 2;

    // ─── Cálculo ──────────────────────────────────────────────────────────

    /**
     * Horas entre dos marcas de reloj del mismo día.
     * Devuelve null si falta alguna; negativas nunca: el llamador valida antes.
     */
    public function horasEntre(?string $inicio, ?string $fin): ?float
    {
        if (! $inicio || ! $fin) {
            return null;
        }

        $desde = Carbon::createFromFormat('H:i', $this->normalizarHora($inicio));
        $hasta = Carbon::createFromFormat('H:i', $this->normalizarHora($fin));

        if ($hasta->lessThanOrEqualTo($desde)) {
            return null;
        }

        return round($desde->diffInMinutes($hasta) / 60, 2);
    }

    /**
     * Resuelve tiempo, tarifas e importes de una actividad sin persistir nada.
     * Es lo que usa tanto el guardado como la vista previa.
     *
     * @return array{
     *     modalidad_pago: string, horas_trabajadas: ?float, jornadas: ?float,
     *     costo_hora: ?float, costo_jornada: ?float, costo_total: float,
     *     animales_atendidos: int, costo_por_animal: ?float,
     *     metodo_distribucion: ?string, animales: array<int>
     * }
     */
    public function calcular(Trabajador $trabajador, array $datos): array
    {
        $modalidad = $datos['modalidad_pago'] ?? ActividadTrabajador::PAGO_HORA;

        // Las horas capturadas mandan; si no vienen, se derivan del reloj.
        $horas = isset($datos['horas_trabajadas']) && $datos['horas_trabajadas'] !== null
            ? round((float) $datos['horas_trabajadas'], 2)
            : $this->horasEntre($datos['hora_inicio'] ?? null, $datos['hora_fin'] ?? null);

        $jornadas = isset($datos['jornadas']) && $datos['jornadas'] !== null
            ? round((float) $datos['jornadas'], 2)
            : null;

        // Tarifas: la capturada en la actividad tiene prioridad sobre la ficha
        // del trabajador, y queda congelada en la fila.
        $costoHora = isset($datos['costo_hora']) && $datos['costo_hora'] !== null
            ? round((float) $datos['costo_hora'], 2)
            : $trabajador->tarifaHora();

        $costoJornada = isset($datos['costo_jornada']) && $datos['costo_jornada'] !== null
            ? round((float) $datos['costo_jornada'], 2)
            : $trabajador->tarifaJornada();

        if ($modalidad === ActividadTrabajador::PAGO_JORNADA) {
            // Sin jornadas explícitas se asume una completa.
            $jornadas ??= 1.0;
            $costoTotal = round($jornadas * (float) ($costoJornada ?? 0), 2);
        } else {
            $costoTotal = round((float) ($horas ?? 0) * (float) ($costoHora ?? 0), 2);
        }

        $distribucion = $this->resolverDistribucion($datos, $costoTotal);

        return [
            'modalidad_pago' => $modalidad,
            'horas_trabajadas' => $horas,
            'jornadas' => $jornadas,
            'costo_hora' => $costoHora,
            'costo_jornada' => $costoJornada,
            'costo_total' => $costoTotal,
            'animales_atendidos' => $distribucion['animales_atendidos'],
            'costo_por_animal' => $distribucion['costo_por_animal'],
            'metodo_distribucion' => $distribucion['metodo_distribucion'],
            'animales' => $distribucion['animales'],
        ];
    }

    /**
     * Decide entre qué ejemplares se reparte el costo y deja escrito el método.
     *
     * @return array{animales: array<int>, animales_atendidos: int, costo_por_animal: ?float, metodo_distribucion: ?string}
     */
    private function resolverDistribucion(array $datos, float $costoTotal): array
    {
        $vacio = [
            'animales' => [],
            'animales_atendidos' => 0,
            'costo_por_animal' => null,
            'metodo_distribucion' => null,
        ];

        // Un solo ejemplar: todo el costo es suyo.
        if (! empty($datos['animal_id'])) {
            return [
                'animales' => [(int) $datos['animal_id']],
                'animales_atendidos' => 1,
                'costo_por_animal' => $costoTotal,
                'metodo_distribucion' => 'Actividad dirigida a un solo ejemplar.',
            ];
        }

        $distribuir = (bool) ($datos['distribuir_entre_animales'] ?? false);

        if (! $distribuir || empty($datos['lote_id'])) {
            return $vacio;
        }

        $lote = Lote::find($datos['lote_id']);

        if (! $lote) {
            return $vacio;
        }

        $animales = Animal::where('lote_id', $lote->id)
            ->activo()
            ->pluck('id')
            ->all();

        $total = count($animales);

        if ($total === 0) {
            return [
                'animales' => [],
                'animales_atendidos' => 0,
                'costo_por_animal' => null,
                // Se dice explícitamente en vez de dejar la impresión de que
                // el costo se repartió.
                'metodo_distribucion' => 'El lote no tiene ejemplares activos: el costo quedó a nivel de lote, sin repartir.',
            ];
        }

        return [
            'animales' => $animales,
            'animales_atendidos' => $total,
            'costo_por_animal' => round($costoTotal / $total, 2),
            'metodo_distribucion' => sprintf(
                'Costo repartido en partes iguales entre los %d ejemplares que estaban en el lote «%s» al registrar la actividad.',
                $total,
                $lote->nombre
            ),
        ];
    }

    // ─── Registro ─────────────────────────────────────────────────────────

    /**
     * Guarda la actividad y genera sus costos, todo dentro de una transacción.
     */
    public function registrar(Trabajador $trabajador, array $datos): ActividadTrabajador
    {
        $calculo = $this->calcular($trabajador, $datos);

        return DB::transaction(function () use ($trabajador, $datos, $calculo) {
            $actividad = ActividadTrabajador::create([
                'trabajador_id' => $trabajador->id,
                'tipo_actividad' => $datos['tipo_actividad'],
                'animal_id' => $datos['animal_id'] ?? null,
                'lote_id' => $datos['lote_id'] ?? null,
                'faena_id' => $datos['faena_id'] ?? null,
                'fecha' => $datos['fecha'],
                'hora_inicio' => $datos['hora_inicio'] ?? null,
                'hora_fin' => $datos['hora_fin'] ?? null,
                'modalidad_pago' => $calculo['modalidad_pago'],
                'horas_trabajadas' => $calculo['horas_trabajadas'],
                'jornadas' => $calculo['jornadas'],
                'costo_hora' => $calculo['costo_hora'],
                'costo_jornada' => $calculo['costo_jornada'],
                'costo_total' => $calculo['costo_total'],
                'animales_atendidos' => $calculo['animales_atendidos'],
                'costo_por_animal' => $calculo['costo_por_animal'],
                'distribuir_entre_animales' => (bool) ($datos['distribuir_entre_animales'] ?? false),
                'metodo_distribucion' => $calculo['metodo_distribucion'],
                'descripcion' => $datos['descripcion'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
                'registrado_por' => Auth::id(),
            ]);

            $this->sincronizarCostos($actividad, $calculo['animales']);

            return $actividad->fresh();
        });
    }

    /**
     * Reaplica el cálculo sobre una actividad ya guardada y rehace sus costos.
     */
    public function actualizar(ActividadTrabajador $actividad, array $datos): ActividadTrabajador
    {
        $trabajador = $actividad->trabajador;
        $calculo = $this->calcular($trabajador, $datos);

        return DB::transaction(function () use ($actividad, $datos, $calculo) {
            $actividad->update([
                'tipo_actividad' => $datos['tipo_actividad'],
                'animal_id' => $datos['animal_id'] ?? null,
                'lote_id' => $datos['lote_id'] ?? null,
                'faena_id' => $datos['faena_id'] ?? null,
                'fecha' => $datos['fecha'],
                'hora_inicio' => $datos['hora_inicio'] ?? null,
                'hora_fin' => $datos['hora_fin'] ?? null,
                'modalidad_pago' => $calculo['modalidad_pago'],
                'horas_trabajadas' => $calculo['horas_trabajadas'],
                'jornadas' => $calculo['jornadas'],
                'costo_hora' => $calculo['costo_hora'],
                'costo_jornada' => $calculo['costo_jornada'],
                'costo_total' => $calculo['costo_total'],
                'animales_atendidos' => $calculo['animales_atendidos'],
                'costo_por_animal' => $calculo['costo_por_animal'],
                'distribuir_entre_animales' => (bool) ($datos['distribuir_entre_animales'] ?? false),
                'metodo_distribucion' => $calculo['metodo_distribucion'],
                'descripcion' => $datos['descripcion'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            $this->sincronizarCostos($actividad, $calculo['animales']);

            return $actividad->fresh();
        });
    }

    /**
     * Deja la tabla `costos` reflejando exactamente esta actividad.
     *
     * Se borran y rehacen los costos que la actividad generó (identificables
     * por origen_tipo/origen_id) para que una corrección de horas no deje
     * importes viejos sumando. Los costos capturados a mano en el módulo de
     * costos no se tocan: no llevan este origen.
     *
     * @param array<int> $animales
     */
    private function sincronizarCostos(ActividadTrabajador $actividad, array $animales): void
    {
        Costo::where('origen_tipo', ActividadTrabajador::class)
            ->where('origen_id', $actividad->id)
            ->delete();

        if ((float) $actividad->costo_total <= 0) {
            return;   // sin importe no hay nada que costear
        }

        $base = [
            'concepto' => $this->concepto($actividad),
            'descripcion' => $this->descripcion($actividad),
            'categoria' => self::CATEGORIA_COSTO,
            'tipo_costo' => 'directo',
            'fecha' => $actividad->fecha,
            'trabajador_id' => $actividad->trabajador_id,
            'faena_id' => $actividad->faena_id,
            'origen_tipo' => ActividadTrabajador::class,
            'origen_id' => $actividad->id,
            'user_id' => Auth::id(),
            'cantidad' => $actividad->modalidad_pago === ActividadTrabajador::PAGO_JORNADA
                ? $actividad->jornadas
                : $actividad->horas_trabajadas,
            'unidad_medida' => $actividad->modalidad_pago === ActividadTrabajador::PAGO_JORNADA
                ? 'jornadas'
                : 'horas',
        ];

        // Reparto entre varios ejemplares.
        if (count($animales) > 1) {
            foreach ($this->repartir((float) $actividad->costo_total, count($animales)) as $indice => $monto) {
                Costo::create($base + [
                    'animal_id' => $animales[$indice],
                    'lote_id' => $actividad->lote_id,
                    'monto' => $monto,
                    'observaciones' => $actividad->metodo_distribucion,
                ]);
            }

            return;
        }

        // Un solo ejemplar, un lote sin repartir, o una labor general.
        Costo::create($base + [
            'animal_id' => $animales[0] ?? null,
            'lote_id' => $actividad->lote_id,
            'monto' => (float) $actividad->costo_total,
            'observaciones' => $actividad->metodo_distribucion,
        ]);
    }

    /**
     * Reparte un importe entre N partes sin perder ni inventar centavos.
     * El sobrante del redondeo se agrega a la última parte, de modo que la
     * suma de las partes siempre es igual al total.
     *
     * @return array<int, float>
     */
    public function repartir(float $total, int $partes): array
    {
        if ($partes < 1) {
            return [];
        }

        $porParte = floor(($total / $partes) * 100) / 100;
        $montos = array_fill(0, $partes, $porParte);

        $asignado = round($porParte * $partes, 2);
        $sobrante = round($total - $asignado, 2);

        if (abs($sobrante) >= 0.01) {
            $montos[$partes - 1] = round($montos[$partes - 1] + $sobrante, 2);
        }

        return $montos;
    }

    private function concepto(ActividadTrabajador $actividad): string
    {
        $trabajador = $actividad->trabajador;

        return sprintf(
            'Mano de obra: %s — %s',
            $actividad->tipo_legible,
            $trabajador?->nombre_completo ?? 'trabajador'
        );
    }

    /**
     * Deja por escrito de dónde salió el importe, para que el desglose de la
     * valuación pueda explicarlo sin volver a calcular nada.
     */
    private function descripcion(ActividadTrabajador $actividad): string
    {
        if ($actividad->modalidad_pago === ActividadTrabajador::PAGO_JORNADA) {
            return sprintf(
                '%s jornada(s) × $%s por jornada.',
                rtrim(rtrim(number_format((float) $actividad->jornadas, 2, '.', ''), '0'), '.'),
                number_format((float) $actividad->costo_jornada, 2)
            );
        }

        return sprintf(
            '%s hora(s) × $%s por hora.',
            rtrim(rtrim(number_format((float) $actividad->horas_trabajadas, 2, '.', ''), '0'), '.'),
            number_format((float) $actividad->costo_hora, 2)
        );
    }

    // ─── Apoyos ───────────────────────────────────────────────────────────

    /**
     * Detecta el mismo registro enviado dos veces por doble clic: mismo
     * trabajador, actividad, fecha y destino, capturado hace segundos.
     */
    public function existeDuplicadoReciente(Trabajador $trabajador, array $datos): bool
    {
        return ActividadTrabajador::where('trabajador_id', $trabajador->id)
            ->where('tipo_actividad', $datos['tipo_actividad'])
            ->whereDate('fecha', $datos['fecha'])
            ->where('animal_id', $datos['animal_id'] ?? null)
            ->where('lote_id', $datos['lote_id'] ?? null)
            ->where('created_at', '>=', now()->subMinutes(self::MINUTOS_ANTIDUPLICADO))
            ->exists();
    }

    /**
     * Cifras acumuladas de una persona, para su ficha.
     */
    public function resumen(Trabajador $trabajador): array
    {
        $actividades = $trabajador->actividades();

        $totalActividades = (clone $actividades)->count();

        return [
            'actividades' => $totalActividades,
            'horas' => round((float) (clone $actividades)->sum('horas_trabajadas'), 2),
            'jornadas' => round((float) (clone $actividades)->sum('jornadas'), 2),
            'costo_total' => round((float) (clone $actividades)->sum('costo_total'), 2),
            'animales_atendidos' => (clone $actividades)->whereNotNull('animal_id')->distinct()->count('animal_id'),
            'lotes_atendidos' => (clone $actividades)->whereNotNull('lote_id')->distinct()->count('lote_id'),
            'faenas' => (clone $actividades)->whereNotNull('faena_id')->distinct()->count('faena_id'),
            // Sin actividades no hay promedio: se devuelve null en vez de 0,
            // que se leería como "trabajó gratis".
            'costo_promedio' => $totalActividades > 0
                ? round((float) (clone $actividades)->sum('costo_total') / $totalActividades, 2)
                : null,
        ];
    }

    private function normalizarHora(string $hora): string
    {
        // Acepta "08:30" y "08:30:00", que es como las devuelve MySQL.
        return substr($hora, 0, 5);
    }

    /** Fecha de referencia para las validaciones de rango. */
    public function hoy(): CarbonInterface
    {
        return Carbon::today();
    }
}
