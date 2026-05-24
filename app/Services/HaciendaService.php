<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Produccion;
use App\Models\Venta;
use App\Models\Faena;
use App\Models\Sacrificio;
use App\Models\Animal;
use App\Models\Lote;
use App\Models\Comprador;
use Carbon\Carbon;

class HaciendaService
{
    // Cache TTL en minutos
    private $cacheTtl = 60;

    // ✅ TODOS los tipos de producción definidos en un solo lugar
    private $tiposProduccion = ['leche', 'lana', 'huevo', 'carne', 'grasa', 'cuero', 'plumas', 'canal'];

    // Tipos que se registran diariamente (sin subproductos de faena)
    private $tiposDiarios = ['leche', 'huevo', 'lana'];

    /**
     * Obtiene animales disponibles (no faenados/sacrificados/vendidos)
     */
    public function getAvailableAnimals(bool $forMap = true)
    {
        $query = Animal::whereNotIn('estado_productivo', ['sacrificado', 'faenado', 'vendido']);

        if ($forMap) {
            return $query->get()->map(function ($animal) {
                return [
                    'id'          => $animal->id,
                    'alias'       => $animal->alias ?? $animal->arete ?? 'Sin alias',
                    'arete'       => $animal->arete,
                    'especie'     => $animal->especie,
                    'peso'        => $animal->peso,
                    'raza'        => $animal->raza ?? 'Sin raza',
                    'lote_nombre' => $animal->lote->nombre ?? 'Sin lote',
                    'edad'        => $animal->fecha_nac ? now()->diffInYears($animal->fecha_nac) : 'N/D',
                ];
            });
        }

        return $query->get();
    }

    /**
     * Obtiene lotes con conteo de animales disponibles
     */
    public function getLotes(bool $forMap = true)
    {
        $estadosNoDisponibles = ['sacrificado', 'faenado', 'vendido'];

        $query = Lote::with([
                'animales' => function ($q) use ($estadosNoDisponibles) {
                    $q->whereNotIn('estado_productivo', $estadosNoDisponibles);
                }
            ])
            ->withCount([
                'animales as animales_disponibles_count' => function ($q) use ($estadosNoDisponibles) {
                    $q->whereNotIn('estado_productivo', $estadosNoDisponibles);
                }
            ]);

        if ($forMap) {
            return $query->get()->map(function ($lote) {
                return [
                    'id'             => $lote->id,
                    'nombre'         => $lote->nombre,
                    'especie'        => $lote->especie ?? ($lote->animales->first()->especie ?? 'Mixto'),
                    'animales_count' => $lote->animales_disponibles_count,
                    'corral_potrero' => $lote->corral_potrero ?? 'N/D',
                ];
            })
            ->filter(fn ($lote) => $lote['animales_count'] > 0)
            ->values();
        }

        return $query->get();
    }

    public function getCompradores()
    {
        return Comprador::select('id', 'nombre')->get();
    }

    // -------------------------------------------------------------------------
    // Produccion
    // -------------------------------------------------------------------------

    /**
     * Obtiene producciones paginadas con filtros (solo tipos diarios)
     */
    public function getProduccionesPaginated(Request $request)
    {
        $query = Produccion::with(['animal.lote'])
            ->whereIn('tipo', $this->tiposDiarios)
            ->latest();

        if ($request->tipo)        $query->where('tipo',  $request->tipo);
        if ($request->fecha_desde) $query->where('fecha', '>=', $request->fecha_desde);
        if ($request->fecha_hasta) $query->where('fecha', '<=', $request->fecha_hasta);

        return $query->paginate(10)->through(function ($produccion) {
            return [
                'id'             => $produccion->id,
                'animal_id'      => $produccion->animal_id,
                'animal_nombre'  => $produccion->animal->nombre ?? 'Sin animal',
                'animal_arete'   => $produccion->animal->arete ?? $produccion->animal->alias ?? 'Sin arete',
                'animal_especie' => $produccion->animal->especie ?? 'Sin especie',
                'lote_nombre'    => $produccion->animal->lote->nombre ?? 'Sin lote',
                'fecha'          => $produccion->fecha,
                'tipo'           => $produccion->tipo,
                'valor'          => $produccion->valor,
                'unidad'         => $produccion->unidad,
                'created_at'     => $produccion->created_at,
            ];
        });
    }

    /**
     * Calcula estadísticas de producción (con cache)
     * Usa TODOS los tipos de producción
     */
    public function getProduccionStatistics()
    {
        return Cache::remember('produccion_statistics', $this->cacheTtl, function () {

            $todosLosTipos = $this->tiposProduccion;

            // -- Datos por tipo (cards de resumen) --
            $datos = [];
            foreach ($todosLosTipos as $tipo) {
                $total = Produccion::where('tipo', $tipo)->sum('valor');
                $datos[$tipo] = [
                    'total'      => $total,
                    'meta'       => 100,
                    'variacion'  => rand(-5, 20), // TODO: calcular variación real
                    'porcentaje' => min(100, ($total / 100) * 100),
                ];
            }

            // -- Mejores animales (ranking) --
            $mejores = Animal::whereHas('producciones', function ($q) use ($todosLosTipos) {
                    $q->whereIn('tipo', $todosLosTipos);
                })
                ->withSum(['producciones' => function ($q) use ($todosLosTipos) {
                    $q->whereIn('tipo', $todosLosTipos);
                }], 'valor')
                ->orderByDesc('producciones_sum_valor')
                ->take(5)
                ->get()
                ->map(function ($animal) {
                    return [
                        'id'          => $animal->id,
                        'nombre'      => $animal->nombre,
                        'especie'     => $animal->especie,
                        'arete'       => $animal->arete ?? $animal->alias,
                        'lote_nombre' => $animal->lote->nombre ?? 'Sin lote',
                        'valor'       => $animal->producciones_sum_valor ?? 0,
                        'diferencia'  => '+' . rand(1, 10) . '%', // TODO: calcular real
                    ];
                });

            // -- Tendencias: agrupadas por día Y tipo, pivoteadas a columnas --
            $tendencias = Produccion::whereIn('tipo', $todosLosTipos)
                ->selectRaw('DATE(fecha) as dia, tipo, SUM(valor) as total')
                ->groupBy('dia', 'tipo')
                ->orderBy('dia', 'asc')
                ->get()
                ->groupBy('dia')
                ->map(function ($grupo, $dia) use ($todosLosTipos) {
                    $fila = ['fecha' => $dia];
                    foreach ($todosLosTipos as $tipo) {
                        $fila[$tipo] = (float) $grupo->where('tipo', $tipo)->sum('total');
                    }
                    return $fila;
                })
                ->values()
                ->toArray();

            // -- Totales mensuales --
            $mesActual   = now()->format('Y-m');
            $mesAnterior = now()->subMonth()->format('Y-m');

            $totalMes = Produccion::whereIn('tipo', $todosLosTipos)
                ->whereRaw("DATE_FORMAT(fecha, '%Y-%m') = ?", [$mesActual])
                ->sum('valor');

            $totalMesAnterior = Produccion::whereIn('tipo', $todosLosTipos)
                ->whereRaw("DATE_FORMAT(fecha, '%Y-%m') = ?", [$mesAnterior])
                ->sum('valor');

            $variacionMes = $totalMesAnterior > 0
                ? round((($totalMes - $totalMesAnterior) / $totalMesAnterior) * 100)
                : 0;

            // -- Eficiencia --
            $eficiencia = $totalMes > 0 ? min(100, round(($totalMes / 100) * 100)) : 0;

            // -- Resumen por lotes --
            $lotes = Lote::with(['animales.producciones' => function ($q) use ($todosLosTipos) {
                    $q->whereIn('tipo', $todosLosTipos);
                }])
                ->withCount('animales')
                ->get()
                ->map(function ($lote) {
                    $valorTotal = $lote->animales->flatMap->producciones->sum('valor');
                    return [
                        'nombre'     => $lote->nombre,
                        'especie'    => $lote->especie ?? 'General',
                        'animales'   => $lote->animales_count,
                        'valor'      => $valorTotal,
                        'eficiencia' => $valorTotal > 0 ? min(100, round(($valorTotal / 100) * 100)) : 0,
                    ];
                });

            return [
                'datos'     => $datos,
                'mejores'   => $mejores,
                'tendencias' => $tendencias,
                'resumen'   => [
                    'totalMes'     => $totalMes,
                    'variacionMes' => $variacionMes,
                    'eficiencia'   => $eficiencia,
                    'meta'         => 100,
                    'lotes'        => $lotes,
                ],
            ];
        });
    }

    /**
     * Calcula ingresos estimados de producción
     */
    public function getProduccionIngresos()
    {
        $stats    = $this->getProduccionStatistics();
        $totalMes = $stats['resumen']['totalMes'];

        $precioUnitarioPromedio = Venta::where('tipo_venta', 'produccion')
            ->whereRaw("DATE_FORMAT(fecha_venta, '%Y-%m') = ?", [now()->format('Y-m')])
            ->avg('precio_unitario') ?? 10;

        $ingresoMes = $totalMes * $precioUnitarioPromedio;

        $promedioMensual = Produccion::whereIn('tipo', $this->tiposProduccion)
            ->where('fecha', '>=', now()->subMonths(3))
            ->groupByRaw("DATE_FORMAT(fecha, '%Y-%m')")
            ->selectRaw('AVG(valor) as avg_valor')
            ->get()
            ->avg('avg_valor') ?? $totalMes;

        $ingresoAnual = $promedioMensual * $precioUnitarioPromedio * 12;

        return [
            'ingresoMes'   => $ingresoMes,
            'ingresoAnual' => $ingresoAnual,
        ];
    }

    // -------------------------------------------------------------------------
    // Venta
    // -------------------------------------------------------------------------

    /**
     * Obtiene ventas paginadas con filtros
     */
    public function getVentasPaginated(Request $request)
    {
        $query = Venta::with(['vendible', 'comprador', 'vendedor'])->latest();

        if ($request->tipo_venta)   $query->where('tipo_venta',   $request->tipo_venta);
        if ($request->fecha_desde)  $query->where('fecha_venta',  '>=', $request->fecha_desde);
        if ($request->estado_venta) $query->where('estado_venta', $request->estado_venta);

        return $query->paginate(10)->through(function ($venta) {
            return [
                'id'              => $venta->id,
                'fecha_venta'     => $venta->fecha_venta,
                'tipo_venta'      => $venta->tipo_venta,
                'producto'        => $venta->producto,
                'cantidad'        => $venta->cantidad,
                'unidad'          => $venta->unidad,
                'precio_unitario' => $venta->precio_unitario,
                'precio_total'    => $venta->precio_total,
                'comprador_nombre'=> $venta->comprador?->nombre ?? 'Desconocido',
                'vendedor_nombre' => $venta->vendedor->name ?? 'Desconocido',
                'estado_venta'    => $venta->estado_venta,
                'estado_pago'     => $venta->estado_pago,
                'metodo_pago'     => $venta->metodo_pago,
                'numero_factura'  => $venta->numero_factura,
                'vendible_info'   => $this->getProductoInfo($venta),
                'observaciones'   => $venta->observaciones,
            ];
        });
    }

    /**
     * Calcula estadísticas de ventas (con cache)
     */
    public function getVentaStatistics()
    {
        return Cache::remember('venta_statistics', $this->cacheTtl, function () {
            $estadisticas = [
                'total_ventas'      => Venta::count(),
                'ingreso_total'     => Venta::where('estado_venta', 'completada')->sum('precio_total'),
                'ingreso_pendiente' => Venta::whereIn('estado_pago', ['pendiente', 'parcial'])->sum('precio_total'),
                'ventas_pendientes' => Venta::where('estado_venta', 'pendiente')->count(),
                'ingreso_mensual'   => Venta::where('estado_venta', 'completada')
                    ->whereMonth('fecha_venta', now()->month)
                    ->whereYear('fecha_venta',  now()->year)
                    ->sum('precio_total'),
                'por_tipo_venta' => [
                    'animal'            => Venta::where('tipo_venta', 'animal')->where('estado_venta', 'completada')->count(),
                    'lote'              => Venta::where('tipo_venta', 'lote')->where('estado_venta', 'completada')->count(),
                    'produccion'        => Venta::where('tipo_venta', 'produccion')->where('estado_venta', 'completada')->count(),
                    'subproducto_faena' => Venta::where('tipo_venta', 'subproducto_faena')->where('estado_venta', 'completada')->count(),
                ],
            ];

            $mesAnterior         = now()->subMonth()->format('Y-m');
            $ingresoMesAnterior  = Venta::where('estado_venta', 'completada')
                ->whereRaw("DATE_FORMAT(fecha_venta, '%Y-%m') = ?", [$mesAnterior])
                ->sum('precio_total');

            $estadisticas['variacion_mensual'] = $ingresoMesAnterior > 0
                ? round((($estadisticas['ingreso_mensual'] - $ingresoMesAnterior) / $ingresoMesAnterior) * 100)
                : 0;

            $promedioMensual = Venta::where('estado_venta', 'completada')
                ->whereYear('fecha_venta', now()->year)
                ->groupByRaw("DATE_FORMAT(fecha_venta, '%Y-%m')")
                ->selectRaw('AVG(precio_total) as avg_mensual')
                ->get()
                ->avg('avg_mensual') ?? $estadisticas['ingreso_mensual'];

            $estadisticas['ingreso_anual_proyectado'] = $promedioMensual * 12;

            $estadisticas['top_compradores'] = Venta::where('estado_venta', 'completada')
                ->groupBy('comprador_id')
                ->selectRaw('comprador_id, SUM(precio_total) as total')
                ->orderByDesc('total')
                ->take(5)
                ->with('comprador')
                ->get();

            $estadisticas['tendencias'] = Venta::where('estado_venta', 'completada')
                ->selectRaw('DATE_FORMAT(fecha_venta, "%Y-%m") as mes, SUM(precio_total) as total')
                ->groupBy('mes')
                ->orderBy('mes')
                ->get();

            $estadisticas['margen_promedio'] = Venta::where('estado_venta', 'completada')
                ->selectRaw('AVG((precio_total - costo_total) / precio_total * 100) as margen')
                ->value('margen') ?? 0;

            return $estadisticas;
        });
    }

    /**
     * Calcula inventario de producciones diarias (restando ventas)
     */
    public function getInventarioProducciones()
    {
        $inventario = [];
        foreach ($this->tiposDiarios as $tipo) {
            $inventario[$tipo] = (float) Produccion::where('tipo', $tipo)->sum('valor');
        }

        $ventasProducciones = Venta::where('tipo_venta', 'produccion')
            ->where('estado_venta', 'completada')
            ->selectRaw('LOWER(producto) as tipo, SUM(cantidad) as vendido')
            ->groupBy('tipo')
            ->pluck('vendido', 'tipo');

        foreach ($inventario as $tipo => $total) {
            $vendido          = (float) ($ventasProducciones[$tipo] ?? 0);
            $inventario[$tipo] = max(0, $total - $vendido);
        }

        return $inventario;
    }

    /**
     * Calcula inventario de subproductos de faenas (restando ventas)
     */
    public function getInventarioSubproductos()
    {
        $inventario = [
            'carne'    => (float) Faena::sum('peso_carne'),
            'cuero'    => (float) Faena::sum('peso_cuero'),
            'grasa'    => (float) Faena::sum('peso_grasa'),
            'plumas'   => (float) Faena::sum('peso_plumas'),
            'hueso'    => (float) Faena::sum('peso_hueso'),
            'visceras' => (float) Faena::sum('peso_visceras'),
        ];

        $ventasSubproductos = Venta::where('tipo_venta', 'subproducto_faena')
            ->where('estado_venta', 'completada')
            ->selectRaw('LOWER(producto) as producto, SUM(cantidad) as vendido')
            ->groupBy('producto')
            ->pluck('vendido', 'producto');

        foreach ($ventasSubproductos as $producto => $vendido) {
            if (isset($inventario[$producto])) {
                $inventario[$producto] = max(0, $inventario[$producto] - (float) $vendido);
            }
        }

        return $inventario;
    }

    // -------------------------------------------------------------------------
    // Faena
    // -------------------------------------------------------------------------

    /**
     * Obtiene faenas paginadas con filtros
     */
    public function getFaenasPaginated(Request $request)
    {
        $query = Faena::with(['animal', 'lote'])->latest();

        if ($request->especie)     $query->whereHas('animal', fn($q) => $q->where('especie', $request->especie));
        if ($request->fecha_desde) $query->where('fecha', '>=', $request->fecha_desde);

        return $query->paginate(10)->through(function ($faena) {
            return [
                'id'             => $faena->id,
                'fecha'          => $faena->fecha,
                'animal_alias'   => $faena->animal->alias ?? 'Sin alias',
                'animal_arete'   => $faena->animal->arete ?? 'Sin arete',
                'animal_especie' => $faena->animal->especie,
                'lote_nombre'    => $faena->lote->nombre ?? 'Sin lote',
                'tipo_corte'     => $faena->tipo_corte,
                'peso_canal'     => $faena->peso_canal,
                'peso_carne'     => $faena->peso_carne,
                'peso_cuero'     => $faena->peso_cuero,
                'peso_grasa'     => $faena->peso_grasa,
                'peso_plumas'    => $faena->peso_plumas,
                'peso_hueso'     => $faena->peso_hueso,
                'peso_visceras'  => $faena->peso_visceras,
                'rendimiento'    => $faena->rendimiento,
                'observaciones'  => $faena->observaciones,
            ];
        });
    }

    /**
     * Calcula estadísticas de faenas (con cache)
     */
    public function getFaenaStatistics()
    {
        return Cache::remember('faena_statistics', $this->cacheTtl, function () {
            $estadisticas = [
                'total_faenas'         => (int)   Faena::count(),
                'total_carne'          => (float) Faena::sum('peso_carne'),
                'total_cuero'          => (float) Faena::sum('peso_cuero'),
                'total_grasa'          => (float) Faena::sum('peso_grasa'),
                'total_plumas'         => (float) Faena::sum('peso_plumas'),
                'total_hueso'          => (float) Faena::sum('peso_hueso'),
                'total_visceras'       => (float) Faena::sum('peso_visceras'),
                'rendimiento_promedio' => (float) Faena::avg('rendimiento'),
                'faenas_este_mes'      => (int)   Faena::whereMonth('fecha', now()->month)->count(),
            ];

            $faenas = Faena::with('animal')->get();

            $estadisticas['por_especie'] = $faenas->groupBy('animal.especie')->map(function ($grupo) {
                return [
                    'carne'    => $grupo->sum('peso_carne'),
                    'cuero'    => $grupo->sum('peso_cuero'),
                    'grasa'    => $grupo->sum('peso_grasa'),
                    'plumas'   => $grupo->sum('peso_plumas'),
                    'hueso'    => $grupo->sum('peso_hueso'),
                    'visceras' => $grupo->sum('peso_visceras'),
                    'cantidad' => $grupo->count(),
                ];
            });

            $estadisticas['tendencias'] = Faena::selectRaw('YEAR(fecha) as año, MONTH(fecha) as mes, COUNT(*) as total, AVG(rendimiento) as rendimiento_promedio')
                ->groupBy('año', 'mes')
                ->orderByDesc('año')
                ->orderByDesc('mes')
                ->take(12)
                ->get()
                ->map(function ($item) {
                    return [
                        'periodo'              => "{$item->año}-" . str_pad($item->mes, 2, '0', STR_PAD_LEFT),
                        'total'                => $item->total,
                        'rendimiento_promedio' => round($item->rendimiento_promedio, 2),
                    ];
                });

            $estadisticas['costo_total']   = Faena::sum('costo_total') ?? 0;
            $estadisticas['costo_promedio'] = Faena::avg('costo_total') ?? 0;

            return $estadisticas;
        });
    }

    // -------------------------------------------------------------------------
    // Sacrificio
    // -------------------------------------------------------------------------

    /**
     * Obtiene sacrificios paginados con filtros
     */
    public function getSacrificiosPaginated(Request $request)
    {
        $query = Sacrificio::with(['animal', 'lote'])->latest();

        if ($request->motivo)      $query->where('motivo', $request->motivo);
        if ($request->fecha_desde) $query->where('fecha',  '>=', $request->fecha_desde);

        return $query->paginate(10)->through(function ($sacrificio) {
            return [
                'id'             => $sacrificio->id,
                'fecha'          => $sacrificio->fecha,
                'animal_alias'   => $sacrificio->animal->alias ?? 'Sin alias',
                'animal_arete'   => $sacrificio->animal->arete ?? 'Sin arete',
                'animal_especie' => $sacrificio->animal->especie ?? 'Sin especie',
                'animal_raza'    => $sacrificio->animal->raza ?? 'Sin raza',
                'lote_nombre'    => $sacrificio->lote->nombre ?? 'Sin lote',
                'motivo'         => $sacrificio->motivo,
                'motivo_texto'   => $sacrificio->motivo_texto,
                'peso_vivo'      => $sacrificio->peso_vivo,
                'peso_canal'     => $sacrificio->peso_canal,
                'rendimiento'    => $sacrificio->rendimiento,
                'cuero'          => $sacrificio->cuero,
                'grasa'          => $sacrificio->grasa,
                'visceras'       => $sacrificio->visceras,
                'plumas'         => $sacrificio->plumas,
                'subproductos'   => $sacrificio->subproductos,
                'observaciones'  => $sacrificio->observaciones,
            ];
        });
    }

    /**
     * Calcula estadísticas de sacrificios (con cache)
     */
    public function getSacrificioStatistics()
    {
        return Cache::remember('sacrificio_statistics', $this->cacheTtl, function () {
            $sacrificios = Sacrificio::with('animal')->get();

            $estadisticas = [
                'total_sacrificios'    => $sacrificios->count(),
                'rendimiento_promedio' => $sacrificios->avg('rendimiento'),
                'peso_vivo_promedio'   => $sacrificios->avg('peso_vivo'),
                'peso_canal_promedio'  => $sacrificios->avg('peso_canal'),
                'por_motivo'           => $sacrificios->groupBy('motivo')->map->count(),
                'por_especie'          => $sacrificios->groupBy('animal.especie')->map(function ($grupo) {
                    return [
                        'cantidad'             => $grupo->count(),
                        'rendimiento_promedio' => $grupo->avg('rendimiento'),
                        'peso_vivo_promedio'   => $grupo->avg('peso_vivo'),
                    ];
                }),
                'subproductos' => [
                    'total_con_cuero'    => $sacrificios->where('cuero',    true)->count(),
                    'total_con_grasa'    => $sacrificios->where('grasa',    true)->count(),
                    'total_con_visceras' => $sacrificios->where('visceras', true)->count(),
                    'total_con_plumas'   => $sacrificios->where('plumas',   true)->count(),
                ],
            ];

            $estadisticas['tendencias'] = Sacrificio::selectRaw('YEAR(fecha) as año, MONTH(fecha) as mes, COUNT(*) as total, AVG(rendimiento) as rendimiento_promedio')
                ->groupBy('año', 'mes')
                ->orderByDesc('año')
                ->orderByDesc('mes')
                ->take(12)
                ->get()
                ->map(function ($item) {
                    return [
                        'periodo'              => "{$item->año}-" . str_pad($item->mes, 2, '0', STR_PAD_LEFT),
                        'total'                => $item->total,
                        'rendimiento_promedio' => round($item->rendimiento_promedio, 2),
                    ];
                });

            return $estadisticas;
        });
    }

    // -------------------------------------------------------------------------
    // Métodos comunes
    // -------------------------------------------------------------------------

    /**
     * Valida stock antes de venta/faena/sacrificio
     */
    public function validarStock(string $tipo, string $producto, float $cantidad): bool
    {
        if ($tipo === 'produccion') {
            $inventario = $this->getInventarioProducciones();
            return ($inventario[$producto] ?? 0) >= $cantidad;
        } elseif ($tipo === 'subproducto_faena') {
            $inventario = $this->getInventarioSubproductos();
            return ($inventario[$producto] ?? 0) >= $cantidad;
        }
        return false;
    }

    /**
     * Calcula ganancia neta global
     */
    public function calcularGananciaNeta()
    {
        $ingresos          = Venta::where('estado_venta', 'completada')->sum('precio_total');
        $costosFaenas      = Faena::sum('costo_total') ?? 0;
        $costosSacrificios = Sacrificio::sum('costo_total') ?? 0;
        $costosProduccion  = Produccion::sum('costo_total') ?? 0;

        return $ingresos - ($costosFaenas + $costosSacrificios + $costosProduccion);
    }

    // -------------------------------------------------------------------------
    // Privados
    // -------------------------------------------------------------------------

    private function getProductoInfo($venta)
    {
        if (!$venta->vendible) return $venta->producto;

        switch ($venta->tipo_venta) {
            case 'animal':
                return $venta->vendible->alias ?? $venta->vendible->arete . ' - ' . $venta->vendible->especie;
            case 'lote':
                return $venta->vendible->nombre . ' (' . $venta->vendible->animales_count . ' animales)';
            case 'produccion':
                return $venta->vendible->tipo . ' - ' . ($venta->vendible->animal->alias ?? $venta->vendible->animal->arete);
            case 'subproducto_faena':
                return $venta->producto . ' - ' . ($venta->vendible->animal->alias ?? $venta->vendible->animal->arete);
            default:
                return $venta->producto;
        }
    }
}