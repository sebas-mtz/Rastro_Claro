<?php

namespace App\Services;

use App\Models\Alimentacion;
use App\Models\Animal;
use App\Models\AnimalValuation;
use App\Models\AnimalValuationDetalle;
use App\Models\AnimalValuationHistorial;
use App\Models\ConfiguracionValuacion;
use App\Models\Costo;
use App\Models\EventoReproductivo;
use App\Models\EventoSalud;
use App\Models\Tratamiento;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Motor de valuación de un animal.
 *
 * Recolecta los costos reales que ya existen en el sistema (no los inventa ni
 * los duplica), los suma por bucket, aplica el margen genético y el plus
 * reproductivo, y deja constancia de cada cambio en el historial.
 *
 *   Costo_Total = Gestacion + Inicial + Sanitario + Alimentacion
 *               + Registro + ManoObra + Transporte + Otros
 *   Valor_Con_Margen = Costo_Total × (1 + Margen/100)
 *   Precio_Estimado  = Valor_Con_Margen + Plus_Reproductivo + Ajuste_Manual
 */
class AnimalValuationService
{
    /** Categorías de `costos` que alimentan cada bucket de la valuación. */
    private const CATEGORIAS_SANITARIAS = ['vacunas', 'medicamentos', 'consultas_veterinarias'];

    /**
     * Calcula el desglose completo SIN persistir nada.
     * Lo usan la vista previa, el simulador y guardar().
     *
     * @param  array  $overrides  porcentaje_margen_genetico, estado_reproductivo_valuacion,
     *                            plus_reproductivo, ajuste_manual, motivo_ajuste
     */
    public function calcular(Animal $animal, array $overrides = []): array
    {
        $detalles = [];
        $vistos = [];   // "origen_tipo:origen_id" ya contabilizados

        $gestacion = $this->detallesGestacion($animal, $vistos);
        $inicial = $this->detallesCostoInicial($animal, $vistos);
        $sanitario = $this->detallesSanitarios($animal, $vistos);
        $alimentacion = $this->detallesAlimentacion($animal, $vistos);
        $registro = $this->detallesPorCategoriaCosto($animal, ['registro_genetico'], AnimalValuationDetalle::CATEGORIA_REGISTRO, $vistos);
        $manoObra = $this->detallesPorCategoriaCosto($animal, ['mano_obra'], AnimalValuationDetalle::CATEGORIA_MANO_OBRA, $vistos);
        $transporte = $this->detallesPorCategoriaCosto($animal, ['transporte'], AnimalValuationDetalle::CATEGORIA_TRANSPORTE, $vistos);

        // Todo lo demás que esté ligado al animal y no haya entrado en otro bucket
        $otros = $this->detallesOtrosCostos($animal, $vistos);

        $detalles = array_merge(
            $gestacion['detalles'], $inicial['detalles'], $sanitario['detalles'],
            $alimentacion['detalles'], $registro['detalles'], $manoObra['detalles'],
            $transporte['detalles'], $otros['detalles'],
        );

        $costoTotal = round(
            $gestacion['total'] + $inicial['total'] + $sanitario['total'] + $alimentacion['total']
            + $registro['total'] + $manoObra['total'] + $transporte['total'] + $otros['total'],
            2
        );

        // ── Margen genético ───────────────────────────────────────────────
        // Se guarda y se muestra como porcentaje (50.00 = 50 %); la división
        // entre 100 ocurre solo aquí, en el cálculo.
        $margen = $this->resolverMargen($animal, $overrides);
        $valorMargen = round($costoTotal * ($margen / 100), 2);

        // ── Plus reproductivo ─────────────────────────────────────────────
        $estadoReproductivo = $overrides['estado_reproductivo_valuacion']
            ?? $this->sugerirEstadoReproductivo($animal);

        $plus = array_key_exists('plus_reproductivo', $overrides) && $overrides['plus_reproductivo'] !== null
            ? round((float) $overrides['plus_reproductivo'], 2)
            : ConfiguracionValuacion::plusPara($estadoReproductivo);

        $ajuste = round((float) ($overrides['ajuste_manual'] ?? 0), 2);

        $precioEstimado = round($costoTotal + $valorMargen + $plus + $ajuste, 2);

        return [
            'animal_id' => $animal->id,
            'buckets' => [
                'costo_gestacion' => $gestacion['total'],
                'costo_inicial' => $inicial['total'],
                'costo_sanitario' => $sanitario['total'],
                'costo_alimentacion' => $alimentacion['total'],
                'costo_registro' => $registro['total'],
                'costo_mano_obra' => $manoObra['total'],
                'costo_transporte' => $transporte['total'],
                'otros_costos' => $otros['total'],
            ],
            'costo_total_produccion' => $costoTotal,
            'porcentaje_margen_genetico' => $margen,
            'valor_margen_genetico' => $valorMargen,
            'estado_reproductivo_valuacion' => $estadoReproductivo,
            'plus_reproductivo' => $plus,
            'ajuste_manual' => $ajuste,
            'motivo_ajuste' => $overrides['motivo_ajuste'] ?? null,
            'precio_estimado' => $precioEstimado,
            'detalles' => $detalles,
            'avisos' => $this->avisos($animal, $gestacion, $alimentacion),
        ];
    }

    /**
     * Persiste la cotización con su desglose y deja el movimiento en el
     * historial. Todo dentro de una transacción: o queda completo o no queda.
     */
    public function guardar(
        Animal $animal,
        array $overrides = [],
        string $tipoMovimiento = AnimalValuationHistorial::TIPO_RECALCULO,
        ?string $motivo = null,
        ?array $referencia = null,
    ): AnimalValuation {
        $calculo = $this->calcular($animal, $overrides);

        return DB::transaction(function () use ($animal, $calculo, $overrides, $tipoMovimiento, $motivo, $referencia) {
            $valuacion = $animal->valuaciones()
                ->where('estado', AnimalValuation::ESTADO_ACTIVA)
                ->latest()
                ->first();

            $precioAnterior = $valuacion?->precio_estimado !== null
                ? (float) $valuacion->precio_estimado
                : null;

            $datosAnteriores = $valuacion
                ? $valuacion->only([
                    'costo_total_produccion', 'porcentaje_margen_genetico',
                    'plus_reproductivo', 'ajuste_manual', 'precio_estimado',
                ])
                : null;

            $atributos = array_merge($calculo['buckets'], [
                'animal_id' => $animal->id,
                'costo_total_produccion' => $calculo['costo_total_produccion'],
                'porcentaje_margen_genetico' => $calculo['porcentaje_margen_genetico'],
                'valor_margen_genetico' => $calculo['valor_margen_genetico'],
                'estado_reproductivo_valuacion' => $calculo['estado_reproductivo_valuacion'],
                'plus_reproductivo' => $calculo['plus_reproductivo'],
                'ajuste_manual' => $calculo['ajuste_manual'],
                'motivo_ajuste' => $calculo['motivo_ajuste'],
                'precio_estimado' => $calculo['precio_estimado'],
                'calculado_en' => now(),
                'actualizado_por' => Auth::id(),
            ]);

            if (array_key_exists('precio_publicado', $overrides)) {
                $atributos['precio_publicado'] = $overrides['precio_publicado'];
            }

            if (array_key_exists('estado', $overrides)) {
                $atributos['estado'] = $overrides['estado'];
            }

            if ($valuacion) {
                $valuacion->update($atributos);
            } else {
                $atributos['creado_por'] = Auth::id();
                $atributos['estado'] = $overrides['estado'] ?? AnimalValuation::ESTADO_ACTIVA;
                $valuacion = AnimalValuation::create($atributos);
                $tipoMovimiento = AnimalValuationHistorial::TIPO_CREACION;
            }

            // El desglose se reemplaza completo en cada recálculo; el historial
            // de precios es lo que conserva la memoria, no los detalles.
            $valuacion->detalles()->delete();

            foreach ($calculo['detalles'] as $detalle) {
                $valuacion->detalles()->create(array_merge($detalle, [
                    'animal_id' => $animal->id,
                    'creado_por' => Auth::id(),
                ]));
            }

            $this->registrarMovimiento(
                valuacion: $valuacion,
                precioAnterior: $precioAnterior,
                precioNuevo: (float) $calculo['precio_estimado'],
                tipoMovimiento: $tipoMovimiento,
                motivo: $motivo,
                referencia: $referencia,
                datosAnteriores: $datosAnteriores,
                datosNuevos: $calculo['buckets'] + [
                    'precio_estimado' => $calculo['precio_estimado'],
                    'porcentaje_margen_genetico' => $calculo['porcentaje_margen_genetico'],
                ],
            );

            return $valuacion->fresh(['detalles', 'historial']);
        });
    }

    /**
     * Agrega un movimiento al historial. Nunca sobrescribe el precio anterior:
     * cada cambio queda como una fila nueva.
     */
    public function registrarMovimiento(
        AnimalValuation $valuacion,
        ?float $precioAnterior,
        float $precioNuevo,
        string $tipoMovimiento,
        ?string $motivo = null,
        ?array $referencia = null,
        ?array $datosAnteriores = null,
        ?array $datosNuevos = null,
    ): AnimalValuationHistorial {
        return AnimalValuationHistorial::create([
            'valuation_id' => $valuacion->id,
            'animal_id' => $valuacion->animal_id,
            'precio_anterior' => $precioAnterior,
            'precio_nuevo' => $precioNuevo,
            'diferencia' => round($precioNuevo - (float) ($precioAnterior ?? 0), 2),
            'motivo' => $motivo,
            'tipo_movimiento' => $tipoMovimiento,
            'referencia_tipo' => $referencia['tipo'] ?? null,
            'referencia_id' => $referencia['id'] ?? null,
            'concepto' => $referencia['concepto'] ?? null,
            'valor_movimiento' => $referencia['valor'] ?? null,
            'usuario_id' => Auth::id(),
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => $datosNuevos,
        ]);
    }

    // ─── Recolectores por bucket ──────────────────────────────────────────

    /**
     * Costo de gestación asignado a esta cría.
     *
     * Ruta: animal → cria → parto → evento reproductivo → madre. Se suman los
     * eventos reproductivos de la madre desde el servicio que originó el parto
     * hasta el parto mismo, y el total se divide entre el número de crías del
     * parto (para no cargarle todo a una sola cría en partos múltiples).
     */
    private function detallesGestacion(Animal $animal, array &$vistos): array
    {
        $cria = $animal->cria()->with('parto.evento')->first();
        $parto = $cria?->parto;
        $eventoParto = $parto?->evento;

        if (! $eventoParto) {
            return ['detalles' => [], 'total' => 0.0, 'sin_datos' => true];
        }

        $madreId = $eventoParto->hembra_id;
        $fechaParto = $eventoParto->fecha;

        // Ventana de gestación: desde el servicio que originó el parto (si se
        // registró) hasta la fecha del parto.
        $eventoServicio = $parto->servicio_evento_id
            ? EventoReproductivo::find($parto->servicio_evento_id)
            : null;
        $fechaInicio = $eventoServicio?->fecha;

        $eventos = EventoReproductivo::where('hembra_id', $madreId)
            ->whereNotNull('costo')
            ->when($fechaInicio, fn ($q) => $q->whereDate('fecha', '>=', $fechaInicio))
            ->whereDate('fecha', '<=', $fechaParto)
            ->orderBy('fecha')
            ->get();

        $numeroCrias = max(1, (int) ($parto->numero_crias ?: 1));

        $detalles = [];
        $total = 0.0;

        foreach ($eventos as $evento) {
            $clave = EventoReproductivo::class . ':' . $evento->id;
            if (isset($vistos[$clave])) {
                continue;
            }
            $vistos[$clave] = true;

            $costoCompleto = (float) $evento->costo;
            $asignado = round($costoCompleto / $numeroCrias, 2);
            $total += $asignado;

            $detalles[] = [
                'categoria' => AnimalValuationDetalle::CATEGORIA_GESTACION,
                'concepto' => 'Gestación — ' . str_replace('_', ' ', $evento->tipo_evento),
                'descripcion' => $evento->observaciones,
                'fecha' => $evento->fecha,
                'cantidad' => 1,
                'costo_unitario' => $costoCompleto,
                'costo_total' => $asignado,
                'origen_tipo' => EventoReproductivo::class,
                'origen_id' => $evento->id,
                'es_automatico' => true,
                'metodo_distribucion' => $numeroCrias > 1
                    ? "Costo de la madre dividido entre {$numeroCrias} crías del parto"
                    : 'Costo completo de la gestación (parto de una cría)',
            ];
        }

        return ['detalles' => $detalles, 'total' => round($total, 2), 'sin_datos' => $eventos->isEmpty()];
    }

    /**
     * Costo de adquisición (si fue comprado) o de nacimiento (si nació aquí).
     * Nunca ambos: se decide por `animals.tipo_origen` y, si no está definido,
     * por si existe registro de cría.
     */
    private function detallesCostoInicial(Animal $animal, array &$vistos): array
    {
        $esComprado = $animal->tipo_origen === Animal::ORIGEN_COMPRADO;

        $categorias = $esComprado
            ? ['compra_animales']
            : ['compra_animales'];   // misma categoría contable; el concepto lo distingue

        return $this->detallesPorCategoriaCosto(
            $animal,
            $categorias,
            AnimalValuationDetalle::CATEGORIA_INICIAL,
            $vistos
        );
    }

    /**
     * Costo sanitario: vacunas, medicamentos, tratamientos, consultas.
     *
     * Se lee de tres fuentes y se deduplica: si un gasto ya está registrado en
     * la tabla `costos` apuntando a un evento de salud, ese evento no vuelve a
     * sumarse por su propia columna `costo`.
     */
    private function detallesSanitarios(Animal $animal, array &$vistos): array
    {
        $detalles = [];
        $total = 0.0;

        // 1) Filas de `costos` en categorías sanitarias (registro contable formal)
        $desdeCostos = $this->detallesPorCategoriaCosto(
            $animal,
            self::CATEGORIAS_SANITARIAS,
            AnimalValuationDetalle::CATEGORIA_SANITARIO,
            $vistos
        );
        $detalles = array_merge($detalles, $desdeCostos['detalles']);
        $total += $desdeCostos['total'];

        // 2) Eventos de salud con costo propio que no fueron cubiertos arriba
        $eventos = EventoSalud::where('animal_id', $animal->id)
            ->whereNotNull('costo')
            ->where('costo', '>', 0)
            ->orderBy('fecha_programada')
            ->with('vacuna')
            ->get();

        foreach ($eventos as $evento) {
            $clave = EventoSalud::class . ':' . $evento->id;
            if (isset($vistos[$clave])) {
                continue;   // ya vino desde la tabla `costos`
            }
            $vistos[$clave] = true;

            $costo = (float) $evento->costo;
            $total += $costo;

            $detalles[] = [
                'categoria' => AnimalValuationDetalle::CATEGORIA_SANITARIO,
                'concepto' => $evento->vacuna?->nombre ?? $evento->diagnostico ?? ucfirst((string) $evento->tipo),
                'descripcion' => trim(($evento->tratamiento ?? '') . ' ' . ($evento->observaciones ?? '')) ?: null,
                'fecha' => $evento->fecha_aplicacion ?? $evento->fecha_programada,
                'cantidad' => 1,
                'unidad' => $evento->dosis,
                'costo_unitario' => $costo,
                'costo_total' => $costo,
                'origen_tipo' => EventoSalud::class,
                'origen_id' => $evento->id,
                'es_automatico' => true,
                'observaciones' => $evento->responsable ? "Responsable: {$evento->responsable}" : null,
            ];
        }

        // 3) Tratamientos con costo propio
        $tratamientos = Tratamiento::where('animal_id', $animal->id)
            ->whereNotNull('costo')
            ->where('costo', '>', 0)
            ->orderBy('fecha_inicio')
            ->get();

        foreach ($tratamientos as $tratamiento) {
            $clave = Tratamiento::class . ':' . $tratamiento->id;
            if (isset($vistos[$clave])) {
                continue;
            }
            $vistos[$clave] = true;

            $costo = (float) $tratamiento->costo;
            $total += $costo;

            $detalles[] = [
                'categoria' => AnimalValuationDetalle::CATEGORIA_SANITARIO,
                'concepto' => $tratamiento->nombre,
                'descripcion' => $tratamiento->notas,
                'fecha' => $tratamiento->fecha_inicio,
                'cantidad' => 1,
                'costo_unitario' => $costo,
                'costo_total' => $costo,
                'origen_tipo' => Tratamiento::class,
                'origen_id' => $tratamiento->id,
                'es_automatico' => true,
                'observaciones' => $tratamiento->responsable ? "Responsable: {$tratamiento->responsable}" : null,
            ];
        }

        return ['detalles' => $detalles, 'total' => round($total, 2)];
    }

    /**
     * Costo de alimentación: consumos registrados directamente al animal más
     * la parte proporcional de los consumos registrados al lote.
     *
     * El sistema no guarda historial de entrada/salida de lote, así que el
     * prorrateo usa los animales actualmente asignados al lote. Cada línea lo
     * declara en `metodo_distribucion` para que el usuario sepa cómo se calculó.
     */
    private function detallesAlimentacion(Animal $animal, array &$vistos): array
    {
        $detalles = [];
        $total = 0.0;
        $sinCosto = 0;

        // 1) Gastos de alimentación ya registrados en el módulo de Costos.
        //    Van primero para que, cuando provengan de un consumo concreto, ese
        //    consumo quede marcado y no se sume dos veces más abajo.
        $desdeCostos = $this->detallesPorCategoriaCosto(
            $animal,
            ['alimentacion'],
            AnimalValuationDetalle::CATEGORIA_ALIMENTACION,
            $vistos
        );
        $detalles = array_merge($detalles, $desdeCostos['detalles']);
        $total += $desdeCostos['total'];

        // 2) Consumos individuales
        $individuales = Alimentacion::where('animal_id', $animal->id)
            ->with('racion')
            ->orderBy('fecha')
            ->get();

        foreach ($individuales as $consumo) {
            $clave = Alimentacion::class . ':' . $consumo->id;
            if (isset($vistos[$clave])) {
                continue;
            }
            $vistos[$clave] = true;

            $costo = $consumo->costoTotal();
            if ($costo === null) {
                $sinCosto++;
                continue;   // sin precio conocido no se inventa un monto
            }

            $total += $costo;

            $detalles[] = [
                'categoria' => AnimalValuationDetalle::CATEGORIA_ALIMENTACION,
                'concepto' => $consumo->racion?->nombre ?? ($consumo->tipo ?: 'Alimentación'),
                'descripcion' => $consumo->notas,
                'fecha' => $consumo->fecha,
                'cantidad' => (float) $consumo->cantidad,
                'unidad' => $consumo->unidad,
                'costo_unitario' => $consumo->costoPorKg(),
                'costo_total' => $costo,
                'origen_tipo' => Alimentacion::class,
                'origen_id' => $consumo->id,
                'es_automatico' => true,
                'metodo_distribucion' => 'Consumo registrado directamente a este animal',
            ];
        }

        // 3) Consumos de lote, prorrateados
        if ($animal->lote_id) {
            $animalesEnLote = max(1, Animal::where('lote_id', $animal->lote_id)->count());

            $deLote = Alimentacion::where('lote_id', $animal->lote_id)
                ->whereNull('animal_id')
                ->with('racion')
                ->orderBy('fecha')
                ->get();

            foreach ($deLote as $consumo) {
                $clave = Alimentacion::class . ':' . $consumo->id;
                if (isset($vistos[$clave])) {
                    continue;
                }
                $vistos[$clave] = true;

                $costoLote = $consumo->costoTotal();
                if ($costoLote === null) {
                    $sinCosto++;
                    continue;
                }

                $asignado = round($costoLote / $animalesEnLote, 2);
                $total += $asignado;

                $detalles[] = [
                    'categoria' => AnimalValuationDetalle::CATEGORIA_ALIMENTACION,
                    'concepto' => ($consumo->racion?->nombre ?? 'Alimentación') . ' (lote)',
                    'descripcion' => $consumo->notas,
                    'fecha' => $consumo->fecha,
                    'cantidad' => round((float) $consumo->cantidad / $animalesEnLote, 2),
                    'unidad' => $consumo->unidad,
                    'costo_unitario' => $consumo->costoPorKg(),
                    'costo_total' => $asignado,
                    'origen_tipo' => Alimentacion::class,
                    'origen_id' => $consumo->id,
                    'es_automatico' => true,
                    'metodo_distribucion' => "Costo del lote dividido entre {$animalesEnLote} animales actualmente asignados",
                ];
            }
        }

        return ['detalles' => $detalles, 'total' => round($total, 2), 'sin_costo' => $sinCosto];
    }

    /**
     * Lee filas de la tabla `costos` ligadas al animal en las categorías dadas
     * y las convierte en líneas de detalle, marcando su origen para el dedupe.
     */
    private function detallesPorCategoriaCosto(
        Animal $animal,
        array $categorias,
        string $categoriaDestino,
        array &$vistos,
    ): array {
        $costos = Costo::where('animal_id', $animal->id)
            ->whereIn('categoria', $categorias)
            ->orderBy('fecha')
            ->get();

        $detalles = [];
        $total = 0.0;

        foreach ($costos as $costo) {
            $clave = Costo::class . ':' . $costo->id;
            if (isset($vistos[$clave])) {
                continue;
            }

            // Dedupe en ambos sentidos, sin depender del orden en que se
            // recolecten los buckets: si el registro de origen ya aportó su
            // monto, este costo no vuelve a sumarlo, y viceversa.
            if ($costo->origen_tipo && $costo->origen_id) {
                $claveOrigen = $costo->origen_tipo . ':' . $costo->origen_id;

                if (isset($vistos[$claveOrigen])) {
                    $vistos[$clave] = true;
                    continue;
                }

                $vistos[$claveOrigen] = true;
            }

            $vistos[$clave] = true;

            $monto = (float) $costo->monto;
            $total += $monto;

            $detalles[] = [
                'categoria' => $categoriaDestino,
                'concepto' => $costo->concepto,
                'descripcion' => $costo->descripcion,
                'fecha' => $costo->fecha,
                'cantidad' => $costo->cantidad !== null ? (float) $costo->cantidad : null,
                'unidad' => $costo->unidad_medida,
                'costo_unitario' => $costo->cantidad > 0 ? round($monto / (float) $costo->cantidad, 2) : $monto,
                'costo_total' => $monto,
                'origen_tipo' => Costo::class,
                'origen_id' => $costo->id,
                'es_automatico' => true,
                'observaciones' => $this->observacionesDelCosto($costo),
            ];
        }

        return ['detalles' => $detalles, 'total' => round($total, 2)];
    }

    /**
     * Explica el origen del importe en la línea de desglose.
     *
     * En la mano de obra lo que hay que justificar no es el proveedor sino
     * quién trabajó y, si la actividad abarcó varios ejemplares, cómo se
     * repartió el costo entre ellos.
     */
    private function observacionesDelCosto(Costo $costo): ?string
    {
        if ($costo->trabajador_id) {
            $partes = array_filter([
                $costo->trabajador?->nombre_completo
                    ? "Trabajador: {$costo->trabajador->nombre_completo}"
                    : null,
                $costo->observaciones,
            ]);

            return $partes ? implode(' · ', $partes) : null;
        }

        return $costo->proveedor ? "Proveedor: {$costo->proveedor}" : null;
    }

    /**
     * Todo lo demás ligado al animal que no cayó en un bucket específico.
     */
    private function detallesOtrosCostos(Animal $animal, array &$vistos): array
    {
        $yaAsignadas = array_merge(
            self::CATEGORIAS_SANITARIAS,
            ['alimentacion', 'registro_genetico', 'mano_obra', 'transporte', 'compra_animales']
        );

        $categoriasRestantes = array_values(array_diff(Costo::CATEGORIAS, $yaAsignadas));

        return $this->detallesPorCategoriaCosto(
            $animal,
            $categoriasRestantes,
            AnimalValuationDetalle::CATEGORIA_OTROS,
            $vistos
        );
    }

    // ─── Apoyos ───────────────────────────────────────────────────────────

    /**
     * Margen a aplicar: el override explícito, si no el guardado en la ficha
     * genética del animal, si no cero.
     */
    private function resolverMargen(Animal $animal, array $overrides): float
    {
        if (array_key_exists('porcentaje_margen_genetico', $overrides) && $overrides['porcentaje_margen_genetico'] !== null) {
            return round((float) $overrides['porcentaje_margen_genetico'], 2);
        }

        $genetica = $animal->genetica;

        return $genetica ? round((float) $genetica->porcentaje_margen_genetico, 2) : 0.0;
    }

    /**
     * Propone un estado reproductivo de valuación leyendo el estado calculado
     * que ya expone Animal y el tipo de semental del último servicio.
     * Es solo una sugerencia: el usuario confirma o cambia.
     */
    public function sugerirEstadoReproductivo(Animal $animal): string
    {
        if ($animal->sexo !== 'F') {
            return 'otro';
        }

        $estado = $animal->estado_reproductivo;

        if (in_array($estado, ['gestante', 'proxima_a_parir', 'servida'], true)) {
            return $this->cargadaPorSementalDeRegistro($animal)
                ? 'cargada_semental_registro'
                : 'cargada_semental_comercial';
        }

        if ($estado === 'parida') {
            $ultimoParto = EventoReproductivo::where('hembra_id', $animal->id)
                ->where('tipo_evento', 'parto')
                ->latest('fecha')
                ->with('parto.crias')
                ->first();

            $parto = $ultimoParto?->parto;

            if ($parto && (int) $parto->numero_crias > 1) {
                return 'parto_multiple';
            }

            $criaViva = $parto?->crias->firstWhere('condicion', 'vivo');

            if ($criaViva) {
                return $criaViva->sexo === 'hembra'
                    ? 'con_cria_hembra_al_pie'
                    : 'con_cria_macho_al_pie';
            }

            return 'con_cria_al_pie';
        }

        // Sin fecha de nacimiento no se asume nada sobre su edad reproductiva.
        $nacimiento = self::fechaNacimiento($animal);

        if ($nacimiento && $nacimiento->diffInMonths(now()) < 8) {
            return 'joven_sin_edad_reproductiva';
        }

        return 'abierta';
    }

    /**
     * `animals.fecha_nac` no está casteado a fecha en el modelo, así que llega
     * como string. Se normaliza aquí en lugar de cambiar el cast, para no
     * alterar cómo el resto de los módulos ya serializan ese campo.
     */
    public static function fechaNacimiento(Animal $animal): ?\Carbon\Carbon
    {
        if (blank($animal->fecha_nac)) {
            return null;
        }

        if ($animal->fecha_nac instanceof \DateTimeInterface) {
            return \Carbon\Carbon::instance($animal->fecha_nac);
        }

        try {
            return \Carbon\Carbon::parse($animal->fecha_nac);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * ¿El servicio que la dejó cargada usó un semental de registro?
     * Se considera de registro cuando el macho tiene ficha genética con número
     * de registro, o cuando se usó pajilla de un donador con registro genealógico.
     */
    private function cargadaPorSementalDeRegistro(Animal $animal): bool
    {
        $servicio = EventoReproductivo::where('hembra_id', $animal->id)
            ->where('tipo_evento', 'servicio')
            ->latest('fecha')
            ->with(['servicio.macho.genetica', 'servicio.pajilla.donadorExterno'])
            ->first()?->servicio;

        if (! $servicio) {
            return false;
        }

        if ($servicio->macho?->genetica?->numero_registro) {
            return true;
        }

        return (bool) $servicio->pajilla?->donadorExterno?->registro_genealogico;
    }

    /**
     * Mensajes honestos sobre lo que no se pudo calcular, en vez de rellenar
     * huecos con ceros que parecerían datos reales.
     */
    private function avisos(Animal $animal, array $gestacion, array $alimentacion): array
    {
        $avisos = [];

        if (! empty($gestacion['sin_datos'])) {
            $avisos[] = 'No existen costos de gestación registrados para este animal.';
        }

        if (! empty($alimentacion['sin_costo'])) {
            $avisos[] = "{$alimentacion['sin_costo']} consumo(s) de alimento no tienen precio conocido y quedaron fuera del total.";
        }

        if (! $animal->genetica) {
            $avisos[] = 'Este animal no tiene ficha genética; el margen genético se aplica en 0 % hasta que la registres.';
        }

        return $avisos;
    }
}
