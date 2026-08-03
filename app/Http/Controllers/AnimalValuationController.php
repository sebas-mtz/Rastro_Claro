<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\AnimalValuation;
use App\Models\AnimalValuationHistorial;
use App\Models\Auditoria;
use App\Models\ConfiguracionValuacion;
use App\Services\AnimalValuationService;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AnimalValuationController extends Controller
{
    public function __construct(
        protected AnimalValuationService $valuationService,
        protected AuditoriaService $auditoria,
    ) {
    }

    /**
     * Ficha de valuación del animal. Si nunca se ha cotizado, muestra el
     * cálculo en vivo sin persistir nada todavía.
     */
    public function show(Request $request, Animal $animal)
    {
        $animal->load(['lote', 'madre', 'padre', 'padreExterno', 'genetica']);

        $valuacion = $animal->valuaciones()
            ->where('estado', AnimalValuation::ESTADO_ACTIVA)
            ->with(['detalles', 'creadoPor', 'actualizadoPor'])
            ->latest()
            ->first();

        $calculo = $this->valuationService->calcular($animal);

        return Inertia::render('Valuaciones/Show', [
            'animal' => $animal,
            'valuacion' => $valuacion,
            'calculo' => $calculo,
            'historial' => $this->consultarHistorial($request, $animal),
            'lineaTiempo' => $this->construirLineaTiempo($animal, $calculo),
            'estadosReproductivos' => AnimalValuation::ESTADOS_REPRODUCTIVOS,
            'tiposMovimiento' => AnimalValuationHistorial::TIPOS,
            'configuracionPlus' => ConfiguracionValuacion::orderBy('clave')->get(),
            'filtrosHistorial' => $request->only(['desde', 'hasta', 'tipo_movimiento', 'usuario_id']),
            'puedeMargenExtendido' => (bool) Auth::user()?->isSuperAdmin(),
            'puedeConfigurarPlus' => (bool) Auth::user()?->isSuperAdmin(),
            'esVendido' => $animal->esta_vendido,
        ]);
    }

    /**
     * Recalcula con los valores actuales del sistema y persiste el resultado.
     */
    public function recalcular(Request $request, Animal $animal)
    {
        $this->bloquearSiVendido($animal);

        $valuacion = $animal->valuaciones()
            ->where('estado', AnimalValuation::ESTADO_ACTIVA)
            ->latest()
            ->first();

        // Conserva las decisiones manuales del usuario al recalcular los costos
        $overrides = $valuacion ? [
            'porcentaje_margen_genetico' => (float) $valuacion->porcentaje_margen_genetico,
            'estado_reproductivo_valuacion' => $valuacion->estado_reproductivo_valuacion,
            'plus_reproductivo' => (float) $valuacion->plus_reproductivo,
            'ajuste_manual' => (float) $valuacion->ajuste_manual,
            'motivo_ajuste' => $valuacion->motivo_ajuste,
        ] : [];

        $this->valuationService->guardar(
            $animal,
            $overrides,
            AnimalValuationHistorial::TIPO_RECALCULO,
            'Recálculo manual solicitado desde la ficha de valuación.'
        );

        return back()->with('success', 'Cotización recalculada.');
    }

    /**
     * Simulación: devuelve el resultado sin tocar la información real.
     */
    public function simular(Request $request, Animal $animal)
    {
        $validated = $request->validate($this->reglasCotizacion());

        $this->validarMargenExtendido($validated);

        return response()->json([
            'calculo' => $this->valuationService->calcular($animal, $validated),
        ]);
    }

    /**
     * Guarda la cotización con los valores que el usuario confirmó.
     */
    public function guardar(Request $request, Animal $animal)
    {
        $this->bloquearSiVendido($animal);

        $validated = $request->validate(array_merge($this->reglasCotizacion(), [
            'estado' => ['nullable', Rule::in([AnimalValuation::ESTADO_BORRADOR, AnimalValuation::ESTADO_ACTIVA])],
            'precio_publicado' => 'nullable|numeric|min:0',
        ]));

        $this->validarMargenExtendido($validated);

        $valuacionPrevia = $animal->valuaciones()
            ->where('estado', AnimalValuation::ESTADO_ACTIVA)
            ->latest()
            ->first();

        $tipoMovimiento = $this->clasificarMovimiento($valuacionPrevia, $validated);

        $this->valuationService->guardar(
            $animal,
            $validated,
            $tipoMovimiento,
            $validated['motivo_ajuste'] ?? 'Cotización guardada desde la interfaz.'
        );

        return back()->with('success', 'Cotización guardada.');
    }

    /**
     * Registra el precio real de venta y cierra la cotización.
     */
    public function confirmarPrecioVenta(Request $request, Animal $animal)
    {
        $nacimiento = AnimalValuationService::fechaNacimiento($animal);

        $reglaFecha = ['required', 'date'];

        // La venta no puede ser anterior al nacimiento del animal.
        if ($nacimiento) {
            $reglaFecha[] = 'after_or_equal:' . $nacimiento->toDateString();
        }

        $validated = $request->validate([
            'precio_real_venta' => 'required|numeric|min:0',
            'fecha_venta' => $reglaFecha,
            'venta_id' => 'nullable|exists:ventas,id',
            'observaciones' => 'nullable|string|max:2000',
        ]);

        $valuacion = $animal->valuaciones()
            ->where('estado', AnimalValuation::ESTADO_ACTIVA)
            ->latest()
            ->first();

        if (! $valuacion) {
            return back()->withErrors([
                'precio_real_venta' => 'Primero debes guardar una cotización antes de confirmar el precio de venta.',
            ]);
        }

        DB::transaction(function () use ($valuacion, $validated, $animal) {
            $precioAnterior = (float) $valuacion->precio_estimado;

            $valuacion->update([
                'precio_real_venta' => $validated['precio_real_venta'],
                'venta_id' => $validated['venta_id'] ?? null,
                'estado' => AnimalValuation::ESTADO_CONFIRMADA,
                'actualizado_por' => Auth::id(),
            ]);

            $this->valuationService->registrarMovimiento(
                valuacion: $valuacion,
                precioAnterior: $precioAnterior,
                precioNuevo: (float) $validated['precio_real_venta'],
                tipoMovimiento: AnimalValuationHistorial::TIPO_CONFIRMACION_VENTA,
                motivo: $validated['observaciones'] ?? 'Confirmación del precio real de venta.',
                referencia: [
                    'tipo' => 'venta',
                    'id' => $validated['venta_id'] ?? null,
                    'concepto' => 'Precio real de venta',
                    'valor' => $validated['precio_real_venta'],
                ],
                datosAnteriores: ['precio_estimado' => $precioAnterior],
                datosNuevos: [
                    'precio_real_venta' => $validated['precio_real_venta'],
                    'utilidad' => round((float) $validated['precio_real_venta'] - (float) $valuacion->costo_total_produccion, 2),
                ],
            );
        });

        return back()->with('success', 'Precio de venta confirmado.');
    }

    /**
     * Actualiza los valores configurables del plus reproductivo.
     */
    public function actualizarConfiguracion(Request $request)
    {
        $validated = $request->validate([
            'valores' => 'required|array',
            'valores.*.clave' => 'required|string|max:100',
            'valores.*.valor' => 'required|numeric|min:0',
        ]);

        // Valores previos, para que la bitácora pueda decir qué cambió.
        $anteriores = ConfiguracionValuacion::pluck('valor', 'clave')->toArray();

        DB::transaction(function () use ($validated) {
            foreach ($validated['valores'] as $fila) {
                ConfiguracionValuacion::updateOrCreate(
                    // La configuración es del rancho, no de la persona que la
                    // edita: si mañana entra otro usuario a la misma cuenta
                    // debe encontrar los mismos valores, no crear una copia.
                    ['owner_id' => Auth::user()->cuentaId(), 'clave' => $fila['clave']],
                    [
                        'valor' => $fila['valor'],
                        'descripcion' => ConfiguracionValuacion::PLUS_POR_DEFECTO[$fila['clave']][1] ?? null,
                    ]
                );
            }
        });

        $this->auditoria->registrarSobreEntidad(
            Auditoria::VALOR_VALUACION_MODIFICADO,
            ConfiguracionValuacion::class,
            null,
            $anteriores,
            ConfiguracionValuacion::pluck('valor', 'clave')->toArray(),
            'Valores del plus reproductivo actualizados.'
        );

        return back()->with('success', 'Configuración de plus reproductivo actualizada.');
    }

    public function exportarPdf(Animal $animal)
    {
        $animal->load(['lote', 'madre', 'padre', 'genetica']);

        $valuacion = $animal->valuaciones()
            ->where('estado', AnimalValuation::ESTADO_ACTIVA)
            ->with('detalles')
            ->latest()
            ->first();

        $calculo = $this->valuationService->calcular($animal);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('valuaciones.pdf', [
            'animal' => $animal,
            'valuacion' => $valuacion,
            'calculo' => $calculo,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('cotizacion_' . ($animal->arete ?: $animal->id) . '_' . now()->format('Ymd') . '.pdf');
    }

    // ─── Apoyos ───────────────────────────────────────────────────────────

    private function reglasCotizacion(): array
    {
        return [
            'porcentaje_margen_genetico' => 'nullable|numeric|min:0|max:500',
            'estado_reproductivo_valuacion' => ['nullable', Rule::in(AnimalValuation::ESTADOS_REPRODUCTIVOS)],
            'plus_reproductivo' => 'nullable|numeric|min:0',
            'ajuste_manual' => 'nullable|numeric',
            // El ajuste manual solo se acepta acompañado de una justificación.
            'motivo_ajuste' => 'nullable|string|max:2000|required_with:ajuste_manual',
        ];
    }

    /**
     * Superar el 100 % de margen exige rol admin, además de justificación.
     */
    private function validarMargenExtendido(array $datos): void
    {
        $margen = (float) ($datos['porcentaje_margen_genetico'] ?? 0);

        if ($margen <= 100) {
            return;
        }

        // Un margen por encima del 100 % altera de forma sustancial el precio
        // estimado, así que es una modificación crítica: la autoriza el
        // superadministrador, no cualquier administrador.
        abort_unless(
            Auth::user()?->isSuperAdmin(),
            403,
            'Solo el superadministrador puede aplicar un margen genético superior al 100 %.'
        );

        if (blank($datos['motivo_ajuste'] ?? null)) {
            abort(422, 'Un margen genético superior al 100 % requiere una justificación escrita.');
        }
    }

    /**
     * Un animal vendido no genera cotizaciones nuevas.
     */
    private function bloquearSiVendido(Animal $animal): void
    {
        abort_if(
            $animal->esta_vendido,
            403,
            'Este animal ya fue vendido; su cotización quedó cerrada.'
        );
    }

    /**
     * Clasifica el movimiento para que el historial explique qué cambió.
     */
    private function clasificarMovimiento(?AnimalValuation $previa, array $datos): string
    {
        if (! $previa) {
            return AnimalValuationHistorial::TIPO_CREACION;
        }

        if (isset($datos['ajuste_manual']) && (float) $datos['ajuste_manual'] !== (float) $previa->ajuste_manual) {
            return AnimalValuationHistorial::TIPO_AJUSTE_MANUAL;
        }

        if (isset($datos['porcentaje_margen_genetico'])
            && (float) $datos['porcentaje_margen_genetico'] !== (float) $previa->porcentaje_margen_genetico) {
            return AnimalValuationHistorial::TIPO_CAMBIO_MARGEN;
        }

        if (isset($datos['estado_reproductivo_valuacion'])
            && $datos['estado_reproductivo_valuacion'] !== $previa->estado_reproductivo_valuacion) {
            return AnimalValuationHistorial::TIPO_CAMBIO_REPRODUCTIVO;
        }

        return AnimalValuationHistorial::TIPO_RECALCULO;
    }

    private function consultarHistorial(Request $request, Animal $animal)
    {
        return AnimalValuationHistorial::where('animal_id', $animal->id)
            ->when($request->desde, fn ($q) => $q->whereDate('created_at', '>=', $request->desde))
            ->when($request->hasta, fn ($q) => $q->whereDate('created_at', '<=', $request->hasta))
            ->when($request->tipo_movimiento, fn ($q) => $q->where('tipo_movimiento', $request->tipo_movimiento))
            ->when($request->usuario_id, fn ($q) => $q->where('usuario_id', $request->usuario_id))
            ->with('usuario:id,name')
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();
    }

    /**
     * Línea de tiempo desde la gestación hasta la cotización actual, armada
     * con los movimientos reales del desglose.
     */
    private function construirLineaTiempo(Animal $animal, array $calculo): array
    {
        $hitos = [];

        foreach ($calculo['detalles'] as $detalle) {
            if (empty($detalle['fecha'])) {
                continue;
            }

            $fecha = $detalle['fecha'] instanceof \DateTimeInterface
                ? $detalle['fecha']->format('Y-m-d')
                : (string) $detalle['fecha'];

            $hitos[] = [
                'fecha' => $fecha,
                'categoria' => $detalle['categoria'],
                'concepto' => $detalle['concepto'],
                'costo' => $detalle['costo_total'],
                'metodo' => $detalle['metodo_distribucion'] ?? null,
            ];
        }

        $nacimiento = AnimalValuationService::fechaNacimiento($animal);

        if ($nacimiento) {
            $hitos[] = [
                'fecha' => $nacimiento->format('Y-m-d'),
                'categoria' => 'nacimiento',
                'concepto' => 'Nacimiento',
                'costo' => null,
                'metodo' => null,
            ];
        }

        usort($hitos, fn ($a, $b) => strcmp($a['fecha'], $b['fecha']));

        return $hitos;
    }
}
