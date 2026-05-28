<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Alimentacion;
use App\Models\Cria;
use App\Models\DiagnosticoGestacion;
use App\Models\EventoReproductivo;
use App\Models\EventoSalud;
use App\Models\InventarioInsumo;
use App\Models\Lote;
use App\Models\Parto;
use App\Models\Pesaje;
use App\Models\ProgramacionAlimentacion;
use App\Models\Produccion;
use App\Models\Racion;
use App\Models\ServicioReproductivo;
use App\Models\Tratamiento;
use App\Models\Vacuna;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportesController extends Controller
{
    // ─────────────────────────────────────────────
    //  Página principal (Inertia)
    // ─────────────────────────────────────────────
    public function index(Request $request)
    {
        $catalogos = [
            'animales_lista' => Animal::select('id', 'arete', 'alias', 'especie')
                                    ->orderBy('arete')->get(),
            'lotes'          => Lote::select('id', 'nombre')->orderBy('nombre')->get(),
            'vacunas'        => Vacuna::select('id', 'nombre')->orderBy('nombre')->get(),
            'raciones'       => Racion::select('id', 'nombre')->where('activo', true)->orderBy('nombre')->get(),
            'especies'       => Animal::distinct()->pluck('especie'),
            'razas'          => Animal::whereNotNull('raza')->distinct()->pluck('raza'),
            'tipos_insumo'   => InventarioInsumo::distinct()->pluck('tipo'),
        ];

        $datos = null;
        if ($request->filled('fecha_inicio') || $request->filled('modulo')) {
            $datos = $this->compilarDatos($request);
        }

        // ── Ficha de animal con todos los filtros aplicados ──
        $ficha = null;
        if ($request->input('tab') === 'animal' && $request->filled('animal_id')) {
            $ficha = $this->compilarFicha($request);
        }

        return Inertia::render('Reportes/Reportes', [
            'catalogos' => $catalogos,
            'datos'     => $datos,
            'ficha'     => $ficha,
            'filtros'   => $request->only([
                'fecha_inicio', 'fecha_fin', 'modulo',
                'especie', 'raza', 'sexo', 'lote_id', 'estado_productivo',
                'tipo_evento', 'estado_salud', 'estado_trat', 'vacuna_id',
                'racion_id', 'tipo_alimentacion', 'tipo_insumo', 'activo_insumo',
                'tipo_evento_repro', 'tipo_servicio', 'resultado_diagnostico',
                'tipo_produccion',
                'tipo_venta', 'estado_venta', 'estado_pago', 'metodo_pago',
                'tab', 'animal_id',
            ]),
        ]);
    }

    // ─────────────────────────────────────────────
    //  Ficha individual con filtros aplicados
    // ─────────────────────────────────────────────
    private function compilarFicha(Request $request): ?array
    {
        $animal = Animal::with(['lote:id,nombre', 'madre:id,arete,alias', 'padre:id,arete,alias'])
            ->find($request->animal_id);

        if (! $animal) return null;

        $fi = $request->fecha_inicio ?: null;
        $ff = $request->fecha_fin    ?: null;

        // ── Pesajes ──
        $pesajes = Pesaje::where('animal_id', $animal->id)
            ->when($fi, fn($q) => $q->whereDate('fecha', '>=', $fi))
            ->when($ff, fn($q) => $q->whereDate('fecha', '<=', $ff))
            ->orderBy('fecha')
            ->get();

        // ── Eventos de salud ──
        $eventosSalud = EventoSalud::with(['vacuna:id,nombre,patogeno,refuerzo_dias'])
            ->where('animal_id', $animal->id)
            ->when($fi,                   fn($q) => $q->whereDate('fecha_programada', '>=', $fi))
            ->when($ff,                   fn($q) => $q->whereDate('fecha_programada', '<=', $ff))
            ->when($request->tipo_evento, fn($q) => $q->where('tipo', $request->tipo_evento))
            ->when($request->estado_salud,fn($q) => $q->where('estado', $request->estado_salud))
            ->when($request->vacuna_id,   fn($q) => $q->where('vacuna_id', $request->vacuna_id))
            ->orderByDesc('fecha_programada')
            ->get();

        // ── Tratamientos ──
        $tratamientos = Tratamiento::where('animal_id', $animal->id)
            ->when($fi,                  fn($q) => $q->whereDate('fecha_inicio', '>=', $fi))
            ->when($ff,                  fn($q) => $q->whereDate('fecha_inicio', '<=', $ff))
            ->when($request->estado_trat,fn($q) => $q->where('estado', $request->estado_trat))
            ->orderByDesc('fecha_inicio')
            ->get();

        // ── Alimentación ──
        $alimentaciones = Alimentacion::with(['racion:id,nombre,MS,PB,EM,FDN'])
            ->where('animal_id', $animal->id)
            ->when($fi,                        fn($q) => $q->whereDate('fecha', '>=', $fi))
            ->when($ff,                        fn($q) => $q->whereDate('fecha', '<=', $ff))
            ->when($request->racion_id,        fn($q) => $q->where('racion_id', $request->racion_id))
            ->when($request->tipo_alimentacion === 'automatico', fn($q) => $q->where('generado_automaticamente', true))
            ->when($request->tipo_alimentacion === 'manual',     fn($q) => $q->where('generado_automaticamente', false))
            ->orderByDesc('fecha')
            ->get();

        // ── Producción ──
        $producciones = Produccion::where('animal_id', $animal->id)
            ->when($fi,                     fn($q) => $q->whereDate('fecha', '>=', $fi))
            ->when($ff,                     fn($q) => $q->whereDate('fecha', '<=', $ff))
            ->when($request->tipo_produccion,fn($q) => $q->where('tipo', $request->tipo_produccion))
            ->orderByDesc('fecha')
            ->get();

        // ── Eventos reproductivos ──
        $eventosRepro = EventoReproductivo::with([
                'servicio.macho:id,arete,alias',
                'servicio.tecnico:id,name',
                'diagnostico.veterinario:id,name',
                'parto.crias.animal:id,arete',
            ])
            ->where('hembra_id', $animal->id)
            ->when($fi,                       fn($q) => $q->whereDate('fecha', '>=', $fi))
            ->when($ff,                       fn($q) => $q->whereDate('fecha', '<=', $ff))
            ->when($request->tipo_evento_repro,fn($q) => $q->where('tipo_evento', $request->tipo_evento_repro))
            ->when($request->tipo_servicio,    fn($q) =>
                $q->whereHas('servicio', fn($s) => $s->where('tipo_servicio', $request->tipo_servicio))
            )
            ->when($request->resultado_diagnostico, fn($q) =>
                $q->whereHas('diagnostico', fn($d) => $d->where('resultado', $request->resultado_diagnostico))
            )
            ->orderByDesc('fecha')
            ->get();

        // Adjuntamos los parámetros de filtro al array para que el frontend
        // pueda mostrar el banner de "filtros activos"
        return array_merge($animal->toArray(), [
            'pesajes'                => $pesajes,
            'eventos_salud'          => $eventosSalud,
            'tratamientos'           => $tratamientos,
            'alimentaciones'         => $alimentaciones,
            'producciones'           => $producciones,
            'eventos_reproductivos'  => $eventosRepro,
            // meta para el banner
            '_filtros_aplicados' => array_filter([
                'Fecha inicio'         => $fi,
                'Fecha fin'            => $ff,
                'Tipo evento salud'    => $request->tipo_evento,
                'Estado salud'         => $request->estado_salud,
                'Vacuna'               => $request->vacuna_id
                    ? (Vacuna::find($request->vacuna_id)?->nombre) : null,
                'Estado tratamiento'   => $request->estado_trat,
                'Ración'               => $request->racion_id
                    ? (Racion::find($request->racion_id)?->nombre) : null,
                'Tipo alimentación'    => $request->tipo_alimentacion,
                'Tipo producción'      => $request->tipo_produccion,
                'Tipo evento repro.'   => $request->tipo_evento_repro,
                'Tipo servicio'        => $request->tipo_servicio,
                'Resultado diagnóst.'  => $request->resultado_diagnostico,
            ]),
        ]);
    }

    // ─────────────────────────────────────────────
    //  Exportar PDF ficha
    // ─────────────────────────────────────────────
    public function exportarFichaPdf(Request $request)
{
    $ficha = $this->compilarFicha($request);

    if (! $ficha) abort(404);

    // Re-cargar el animal como objeto Eloquent con todas las relaciones
    $animal = Animal::with([
        'lote:id,nombre',
        'madre:id,arete,alias',
        'padre:id,arete,alias',
    ])->find($request->animal_id);

    if (! $animal) abort(404);

    // Inyectar las colecciones filtradas (vienen del compilarFicha)
    // como relaciones "falsas" usando el método setRelation()
    $animal->setRelation('pesajes',               collect($ficha['pesajes']));
    $animal->setRelation('eventos_salud',         collect($ficha['eventos_salud']));
    $animal->setRelation('tratamientos',          collect($ficha['tratamientos']));
    $animal->setRelation('alimentaciones',        collect($ficha['alimentaciones']));
    $animal->setRelation('producciones',          collect($ficha['producciones']));
    $animal->setRelation('eventos_reproductivos', collect($ficha['eventos_reproductivos']));

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
        'reportes.ficha_animal',
        ['animal' => $animal]
    )->setPaper('a4', 'portrait');

    $arete = $animal->arete;
    return $pdf->download("ficha_{$arete}_" . now()->format('Ymd') . '.pdf');
}

    // ─────────────────────────────────────────────
    //  Exportar PDF general
    // ─────────────────────────────────────────────
    public function exportarPdf(Request $request)
    {
        $datos   = $this->compilarDatos($request);
        $filtros = $request->all();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reportes.pdf', compact('datos', 'filtros'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('reporte_' . now()->format('Ymd_His') . '.pdf');
    }

    // ─────────────────────────────────────────────
    //  Exportar XML
    // ─────────────────────────────────────────────
    public function exportarXml(Request $request)
    {
        $datos = $this->compilarDatos($request);
        $xml   = $this->generarXml($datos, $request->all());

        return response($xml, 200, [
            'Content-Type'        => 'application/xml',
            'Content-Disposition' => 'attachment; filename="reporte_' . now()->format('Ymd_His') . '.xml"',
        ]);
    }

    // ─────────────────────────────────────────────
    //  Dispatcher de módulos
    // ─────────────────────────────────────────────
    private function compilarDatos(Request $request): array
    {
        return match ($request->input('modulo', 'general')) {
            'animales'     => ['animales'     => $this->queryAnimales($request)],
            'salud'        => ['salud'        => $this->querySalud($request)],
            'vacunacion'   => ['vacunacion'   => $this->queryVacunacion($request)],
            'tratamientos' => ['tratamientos' => $this->queryTratamientos($request)],
            'pesajes'      => ['pesajes'      => $this->queryPesajes($request)],
            'alimentacion' => ['alimentacion' => $this->queryAlimentacion($request)],
            'inventario'   => ['inventario'   => $this->queryInventario($request)],
            'reproduccion' => $this->queryReproduccion($request),
            default        => [
                'animales'     => $this->queryAnimales($request),
                'salud'        => $this->querySalud($request),
                'vacunacion'   => $this->queryVacunacion($request),
                'tratamientos' => $this->queryTratamientos($request),
                'pesajes'      => $this->queryPesajes($request),
                'alimentacion' => $this->queryAlimentacion($request),
                'inventario'   => $this->queryInventario($request),
                ...$this->queryReproduccion($request),
                'resumen'      => $this->resumen($request),
            ],
        };
    }

    // ─────────────────────────────────────────────
    //  Animales
    // ─────────────────────────────────────────────
    private function queryAnimales(Request $request): array
    {
        $rows = Animal::with(['lote:id,nombre', 'madre:id,arete,alias', 'padre:id,arete,alias'])
            ->when($request->especie,           fn($q) => $q->where('especie',           $request->especie))
            ->when($request->raza,              fn($q) => $q->where('raza',              $request->raza))
            ->when($request->sexo,              fn($q) => $q->where('sexo',              $request->sexo))
            ->when($request->lote_id,           fn($q) => $q->where('lote_id',           $request->lote_id))
            ->when($request->estado_productivo, fn($q) => $q->where('estado_productivo', $request->estado_productivo))
            ->when($request->fecha_inicio,      fn($q) => $q->whereDate('created_at',    '>=', $request->fecha_inicio))
            ->when($request->fecha_fin,         fn($q) => $q->whereDate('created_at',    '<=', $request->fecha_fin))
            ->orderBy('especie')->orderBy('arete')
            ->get();

        return [
            'registros'   => $rows,
            'total'       => $rows->count(),
            'por_especie' => $rows->groupBy('especie')->map(fn($g) => $g->count()),
            'por_sexo'    => $rows->groupBy('sexo')->map(fn($g) => $g->count()),
        ];
    }

    // ─────────────────────────────────────────────
    //  Eventos de Salud
    // ─────────────────────────────────────────────
    private function querySalud(Request $request): array
    {
        $rows = EventoSalud::with([
                'animal:id,arete,alias,especie,raza,sexo,lote_id',
                'animal.lote:id,nombre',
                'vacuna:id,nombre',
            ])
            ->when($request->fecha_inicio, fn($q) => $q->whereDate('fecha_programada', '>=', $request->fecha_inicio))
            ->when($request->fecha_fin,    fn($q) => $q->whereDate('fecha_programada', '<=', $request->fecha_fin))
            ->when($request->tipo_evento,  fn($q) => $q->where('tipo',   $request->tipo_evento))
            ->when($request->estado_salud, fn($q) => $q->where('estado', $request->estado_salud))
            ->when($request->especie,      fn($q) => $q->whereHas('animal', fn($a) => $a->where('especie', $request->especie)))
            ->when($request->lote_id,      fn($q) => $q->whereHas('animal', fn($a) => $a->where('lote_id', $request->lote_id)))
            ->orderByDesc('fecha_programada')
            ->get();

        return [
            'registros'  => $rows,
            'total'      => $rows->count(),
            'por_tipo'   => $rows->groupBy('tipo')->map(fn($g) => $g->count()),
            'por_estado' => $rows->groupBy('estado')->map(fn($g) => $g->count()),
            'pendientes' => $rows->where('estado', 'pendiente')->count(),
            'vencidos'   => $rows->where('estado', 'vencida')->count(),
        ];
    }

    // ─────────────────────────────────────────────
    //  Vacunaciones
    // ─────────────────────────────────────────────
    private function queryVacunacion(Request $request): array
    {
        $rows = EventoSalud::with([
                'animal:id,arete,alias,especie,raza,sexo,lote_id',
                'animal.lote:id,nombre',
                'vacuna:id,nombre,patogeno,refuerzo_dias',
            ])
            ->where('tipo', 'vacunacion')
            ->when($request->fecha_inicio, fn($q) => $q->whereDate('fecha_programada', '>=', $request->fecha_inicio))
            ->when($request->fecha_fin,    fn($q) => $q->whereDate('fecha_programada', '<=', $request->fecha_fin))
            ->when($request->vacuna_id,    fn($q) => $q->where('vacuna_id', $request->vacuna_id))
            ->when($request->estado_salud, fn($q) => $q->where('estado',   $request->estado_salud))
            ->when($request->especie,      fn($q) => $q->whereHas('animal', fn($a) => $a->where('especie', $request->especie)))
            ->when($request->lote_id,      fn($q) => $q->whereHas('animal', fn($a) => $a->where('lote_id', $request->lote_id)))
            ->orderByDesc('fecha_programada')
            ->get();

        $refuerzoVencido = $rows->filter(function ($ev) {
            if (! $ev->vacuna?->refuerzo_dias || ! $ev->fecha_aplicacion) return false;
            return Carbon::parse($ev->fecha_aplicacion)->addDays($ev->vacuna->refuerzo_dias)->isPast();
        });

        return [
            'registros'        => $rows,
            'total'            => $rows->count(),
            'por_vacuna'       => $rows->groupBy('vacuna.nombre')->map(fn($g) => $g->count()),
            'por_estado'       => $rows->groupBy('estado')->map(fn($g) => $g->count()),
            'refuerzo_vencido' => $refuerzoVencido->count(),
        ];
    }

    // ─────────────────────────────────────────────
    //  Tratamientos
    // ─────────────────────────────────────────────
    private function queryTratamientos(Request $request): array
    {
        $rows = Tratamiento::with([
                'animal:id,arete,alias,especie,raza,sexo,lote_id',
                'animal.lote:id,nombre',
                'salud:id,diagnostico,tipo',
            ])
            ->when($request->fecha_inicio, fn($q) => $q->whereDate('fecha_inicio', '>=', $request->fecha_inicio))
            ->when($request->fecha_fin,    fn($q) => $q->whereDate('fecha_inicio', '<=', $request->fecha_fin))
            ->when($request->estado_trat,  fn($q) => $q->where('estado', $request->estado_trat))
            ->when($request->especie,      fn($q) => $q->whereHas('animal', fn($a) => $a->where('especie', $request->especie)))
            ->when($request->lote_id,      fn($q) => $q->whereHas('animal', fn($a) => $a->where('lote_id', $request->lote_id)))
            ->orderByDesc('fecha_inicio')
            ->get();

        return [
            'registros'  => $rows,
            'total'      => $rows->count(),
            'por_estado' => $rows->groupBy('estado')->map(fn($g) => $g->count()),
            'activos'    => $rows->where('estado', 'activo')->count(),
        ];
    }

    // ─────────────────────────────────────────────
    //  Pesajes
    // ─────────────────────────────────────────────
    private function queryPesajes(Request $request): array
    {
        $rows = Pesaje::with([
                'animal:id,arete,alias,especie,raza,sexo,lote_id',
                'animal.lote:id,nombre',
            ])
            ->when($request->fecha_inicio, fn($q) => $q->whereDate('fecha', '>=', $request->fecha_inicio))
            ->when($request->fecha_fin,    fn($q) => $q->whereDate('fecha', '<=', $request->fecha_fin))
            ->when($request->especie,      fn($q) => $q->whereHas('animal', fn($a) => $a->where('especie', $request->especie)))
            ->when($request->sexo,         fn($q) => $q->whereHas('animal', fn($a) => $a->where('sexo',    $request->sexo)))
            ->when($request->lote_id,      fn($q) => $q->whereHas('animal', fn($a) => $a->where('lote_id', $request->lote_id)))
            ->orderByDesc('fecha')
            ->get();

        return [
            'registros'     => $rows,
            'total'         => $rows->count(),
            'peso_promedio' => $rows->avg('peso') ? round($rows->avg('peso'), 2) : null,
            'peso_max'      => $rows->max('peso'),
            'peso_min'      => $rows->min('peso'),
        ];
    }

    // ─────────────────────────────────────────────
    //  Alimentación
    // ─────────────────────────────────────────────
    private function queryAlimentacion(Request $request): array
    {
        $rows = Alimentacion::with([
                'animal:id,arete,alias,especie,lote_id',
                'animal.lote:id,nombre',
                'lote:id,nombre',
                'racion:id,nombre,MS,PB,EM,FDN',
            ])
            ->when($request->fecha_inicio,      fn($q) => $q->whereDate('fecha', '>=', $request->fecha_inicio))
            ->when($request->fecha_fin,         fn($q) => $q->whereDate('fecha', '<=', $request->fecha_fin))
            ->when($request->racion_id,         fn($q) => $q->where('racion_id', $request->racion_id))
            ->when($request->lote_id,           fn($q) => $q->where(fn($q2) =>
                $q2->where('lote_id', $request->lote_id)
                   ->orWhereHas('animal', fn($a) => $a->where('lote_id', $request->lote_id))
            ))
            ->when($request->especie,           fn($q) => $q->whereHas('animal', fn($a) => $a->where('especie', $request->especie)))
            ->when($request->tipo_alimentacion === 'automatico', fn($q) => $q->where('generado_automaticamente', true))
            ->when($request->tipo_alimentacion === 'manual',     fn($q) => $q->where('generado_automaticamente', false))
            ->orderByDesc('fecha')
            ->get();

        return [
            'registros'   => $rows,
            'total'       => $rows->count(),
            'total_kg'    => round($rows->sum('cantidad'), 2),
            'por_racion'  => $rows->groupBy('racion.nombre')->map(fn($g) => $g->count()),
            'automaticos' => $rows->where('generado_automaticamente', true)->count(),
        ];
    }

    // ─────────────────────────────────────────────
    //  Inventario de insumos
    // ─────────────────────────────────────────────
    private function queryInventario(Request $request): array
    {
        $rows = InventarioInsumo::query()
            ->when($request->tipo_insumo,  fn($q) => $q->where('tipo', $request->tipo_insumo))
            ->when($request->activo_insumo !== null && $request->activo_insumo !== '',
                fn($q) => $q->where('activo', (bool)$request->activo_insumo))
            ->when($request->fecha_inicio, fn($q) => $q->whereDate('created_at', '>=', $request->fecha_inicio))
            ->when($request->fecha_fin,    fn($q) => $q->whereDate('created_at', '<=', $request->fecha_fin))
            ->orderBy('tipo')->orderBy('nombre')
            ->get();

        return [
            'registros'  => $rows,
            'total'      => $rows->count(),
            'activos'    => $rows->where('activo', true)->count(),
            'por_tipo'   => $rows->groupBy('tipo')->map(fn($g) => $g->count()),
            'stock_bajo' => $rows->filter(fn($i) => $i->existencias <= 0)->count(),
        ];
    }

    // ─────────────────────────────────────────────
    //  Reproducción
    // ─────────────────────────────────────────────
    private function queryReproduccion(Request $request): array
    {
        $base = EventoReproductivo::with([
            'hembra:id,arete,alias,especie,raza,sexo,lote_id',
            'lote:id,nombre',
        ])
        ->when($request->fecha_inicio,      fn($q) => $q->whereDate('fecha', '>=', $request->fecha_inicio))
        ->when($request->fecha_fin,         fn($q) => $q->whereDate('fecha', '<=', $request->fecha_fin))
        ->when($request->especie,           fn($q) => $q->whereHas('hembra', fn($a) => $a->where('especie', $request->especie)))
        ->when($request->lote_id,           fn($q) => $q->where('lote_id', $request->lote_id))
        ->when($request->tipo_evento_repro, fn($q) => $q->where('tipo_evento', $request->tipo_evento_repro));

        $todos = (clone $base)->orderByDesc('fecha')->get();

        $servicios = (clone $base)
            ->where('tipo_evento', 'servicio')
            ->with(['servicio.macho:id,arete,alias', 'servicio.tecnico:id,name'])
            ->when($request->tipo_servicio, fn($q) =>
                $q->whereHas('servicio', fn($s) => $s->where('tipo_servicio', $request->tipo_servicio))
            )
            ->orderByDesc('fecha')->get();

        $diagnosticos = (clone $base)
            ->where('tipo_evento', 'diagnostico')
            ->with(['diagnostico.veterinario:id,name'])
            ->when($request->resultado_diagnostico, fn($q) =>
                $q->whereHas('diagnostico', fn($d) => $d->where('resultado', $request->resultado_diagnostico))
            )
            ->orderByDesc('fecha')->get();

        $partos = (clone $base)
            ->where('tipo_evento', 'parto')
            ->with(['parto.crias.animal:id,arete'])
            ->orderByDesc('fecha')->get();

        $totalServicios    = $servicios->count();
        $concepcionPositiva = $diagnosticos->filter(fn($ev) => $ev->diagnostico?->resultado === 'positivo')->count();
        $spc = ($concepcionPositiva > 0) ? round($totalServicios / $concepcionPositiva, 2) : null;

        return [
            'reproduccion' => ['registros' => $todos,        'total' => $todos->count(),        'por_tipo' => $todos->groupBy('tipo_evento')->map(fn($g) => $g->count())],
            'servicios'    => ['registros' => $servicios,    'total' => $servicios->count(),    'por_tipo_servicio' => $servicios->groupBy('servicio.tipo_servicio')->map(fn($g) => $g->count()), 'servicios_por_concepcion' => $spc],
            'diagnosticos' => ['registros' => $diagnosticos, 'total' => $diagnosticos->count(), 'por_resultado' => $diagnosticos->groupBy('diagnostico.resultado')->map(fn($g) => $g->count()), 'tasa_concepcion' => $totalServicios > 0 ? round(($concepcionPositiva / $totalServicios) * 100, 1) : null],
            'partos'       => ['registros' => $partos,       'total' => $partos->count(),       'total_crias' => $partos->sum(fn($ev) => $ev->parto?->numero_crias ?? 0), 'con_complicaciones' => $partos->filter(fn($ev) => $ev->parto?->complicaciones)->count()],
        ];
    }

    // ─────────────────────────────────────────────
    //  Resumen ejecutivo
    // ─────────────────────────────────────────────
    private function resumen(Request $request): array
    {
        $fi = $request->fecha_inicio;
        $ff = $request->fecha_fin;

        $animQ  = Animal::query()
            ->when($request->especie, fn($q) => $q->where('especie', $request->especie))
            ->when($request->lote_id, fn($q) => $q->where('lote_id', $request->lote_id));

        $saludQ = EventoSalud::query()
            ->when($fi, fn($q) => $q->whereDate('fecha_programada', '>=', $fi))
            ->when($ff, fn($q) => $q->whereDate('fecha_programada', '<=', $ff));

        $tratQ  = Tratamiento::query()
            ->when($fi, fn($q) => $q->whereDate('fecha_inicio', '>=', $fi))
            ->when($ff, fn($q) => $q->whereDate('fecha_inicio', '<=', $ff));

        $pesQ   = Pesaje::query()
            ->when($fi, fn($q) => $q->whereDate('fecha', '>=', $fi))
            ->when($ff, fn($q) => $q->whereDate('fecha', '<=', $ff));

        $alimQ  = Alimentacion::query()
            ->when($fi, fn($q) => $q->whereDate('fecha', '>=', $fi))
            ->when($ff, fn($q) => $q->whereDate('fecha', '<=', $ff));

        return [
            'total_animales'           => (clone $animQ)->count(),
            'animales_machos'          => (clone $animQ)->where('sexo', 'M')->count(),
            'animales_hembras'         => (clone $animQ)->where('sexo', 'F')->count(),
            'lotes_activos'            => Lote::count(),
            'total_eventos_salud'      => (clone $saludQ)->count(),
            'eventos_pendientes'       => (clone $saludQ)->where('estado', 'pendiente')->count(),
            'eventos_vencidos'         => (clone $saludQ)->where('estado', 'vencida')->count(),
            'total_vacunaciones'       => (clone $saludQ)->where('tipo', 'vacunacion')->count(),
            'vacunaciones_aplicadas'   => (clone $saludQ)->where('tipo', 'vacunacion')->where('estado', 'aplicada')->count(),
            'tratamientos_activos'     => (clone $tratQ)->where('estado', 'activo')->count(),
            'tratamientos_total'       => (clone $tratQ)->count(),
            'total_pesajes'            => (clone $pesQ)->count(),
            'total_alimentaciones'     => (clone $alimQ)->count(),
            'insumos_activos'          => InventarioInsumo::where('activo', true)->count(),
            'insumos_total'            => InventarioInsumo::count(),
            'total_partos'             => EventoReproductivo::where('tipo_evento', 'parto')
                                            ->when($fi, fn($q) => $q->whereDate('fecha', '>=', $fi))
                                            ->when($ff, fn($q) => $q->whereDate('fecha', '<=', $ff))
                                            ->count(),
            'servicios_por_concepcion' => $this->calcularSpc($fi, $ff),
            'total_produccion'         => \App\Models\Produccion::query()
                                            ->when($fi, fn($q) => $q->whereDate('fecha', '>=', $fi))
                                            ->when($ff, fn($q) => $q->whereDate('fecha', '<=', $ff))
                                            ->count(),
            'total_ventas'             => 0, // ajusta con tu modelo de Venta
            'ingresos_ventas'          => null,
        ];
    }

    private function calcularSpc(?string $fi, ?string $ff): ?float
    {
        $servicios = EventoReproductivo::where('tipo_evento', 'servicio')
            ->when($fi, fn($q) => $q->whereDate('fecha', '>=', $fi))
            ->when($ff, fn($q) => $q->whereDate('fecha', '<=', $ff))
            ->count();

        $positivos = EventoReproductivo::where('tipo_evento', 'diagnostico')
            ->when($fi, fn($q) => $q->whereDate('fecha', '>=', $fi))
            ->when($ff, fn($q) => $q->whereDate('fecha', '<=', $ff))
            ->whereHas('diagnostico', fn($d) => $d->where('resultado', 'positivo'))
            ->count();

        return ($positivos > 0) ? round($servicios / $positivos, 2) : null;
    }

    // ─────────────────────────────────────────────
    //  Generador XML
    // ─────────────────────────────────────────────
    private function generarXml(array $datos, array $filtros): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElement('reporte');
        $root->setAttribute('generado', now()->toIso8601String());
        $dom->appendChild($root);

        $meta = $dom->createElement('filtros');
        foreach ($filtros as $k => $v) {
            if ($v !== null && $v !== '') {
                $n = $dom->createElement('filtro');
                $n->setAttribute('campo', htmlspecialchars($k));
                $n->setAttribute('valor', htmlspecialchars((string) $v));
                $meta->appendChild($n);
            }
        }
        $root->appendChild($meta);

        $secciones = [
            'animales'     => fn($a)   => ['id'=>$a->id,'arete'=>$a->arete,'alias'=>$a->alias,'especie'=>$a->especie,'raza'=>$a->raza,'sexo'=>$a->sexo,'fecha_nac'=>$a->fecha_nac,'peso'=>$a->peso,'BCS'=>$a->BCS,'estado_productivo'=>$a->estado_productivo,'lote'=>$a->lote?->nombre],
            'salud'        => fn($ev)  => ['id'=>$ev->id,'animal'=>$ev->animal?->arete,'especie'=>$ev->animal?->especie,'lote'=>$ev->animal?->lote?->nombre,'tipo'=>$ev->tipo,'fecha_programada'=>$ev->fecha_programada,'fecha_aplicacion'=>$ev->fecha_aplicacion,'estado'=>$ev->estado,'diagnostico'=>$ev->diagnostico,'responsable'=>$ev->responsable],
            'vacunacion'   => fn($ev)  => ['id'=>$ev->id,'animal'=>$ev->animal?->arete,'lote'=>$ev->animal?->lote?->nombre,'vacuna'=>$ev->vacuna?->nombre,'dosis'=>$ev->dosis,'lote_vacuna'=>$ev->lote_vacuna,'fecha_programada'=>$ev->fecha_programada,'fecha_aplicacion'=>$ev->fecha_aplicacion,'estado'=>$ev->estado],
            'tratamientos' => fn($tr)  => ['id'=>$tr->id,'animal'=>$tr->animal?->arete,'nombre'=>$tr->nombre,'fecha_inicio'=>$tr->fecha_inicio,'fecha_fin'=>$tr->fecha_fin,'estado'=>$tr->estado,'responsable'=>$tr->responsable],
            'pesajes'      => fn($p)   => ['id'=>$p->id,'animal'=>$p->animal?->arete,'especie'=>$p->animal?->especie,'lote'=>$p->animal?->lote?->nombre,'fecha'=>$p->fecha,'peso'=>$p->peso,'notas'=>$p->notas],
            'alimentacion' => fn($al)  => ['id'=>$al->id,'fecha'=>$al->fecha,'hora'=>$al->hora,'racion'=>$al->racion?->nombre,'animal'=>$al->animal?->arete,'lote'=>$al->lote?->nombre,'cantidad'=>$al->cantidad,'unidad'=>$al->unidad,'automatico'=>$al->generado_automaticamente],
            'inventario'   => fn($inv) => ['id'=>$inv->id,'nombre'=>$inv->nombre,'tipo'=>$inv->tipo,'existencias'=>$inv->existencias,'unidad'=>$inv->unidad,'costo_promedio'=>$inv->costo_promedio,'activo'=>$inv->activo],
        ];

        foreach ($secciones as $nombre => $mapper) {
            if (! isset($datos[$nombre])) continue;
            $sec = $dom->createElement($nombre);
            $sec->setAttribute('total', $datos[$nombre]['total']);
            foreach ($datos[$nombre]['registros'] as $row) {
                $node = $dom->createElement('item');
                foreach ($mapper($row) as $k => $v) {
                    $node->setAttribute($k, htmlspecialchars((string) ($v ?? '')));
                }
                $sec->appendChild($node);
            }
            $root->appendChild($sec);
        }

        if (isset($datos['resumen'])) {
            $sec = $dom->createElement('resumen');
            foreach ($datos['resumen'] as $k => $v) {
                $sec->setAttribute($k, (string) $v);
            }
            $root->appendChild($sec);
        }

        return $dom->saveXML();
    }
}