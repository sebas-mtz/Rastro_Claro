<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Costo;
use App\Models\Faena;
use App\Models\Lote;
use App\Models\Sacrificio;
use App\Services\CostoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CostoController extends Controller
{
    public function __construct(protected CostoService $costoService)
    {
    }

    public function index(Request $request)
    {
        $costos = $this->costoService->aplicarFiltros($request)
            ->with(['animal:id,arete,alias', 'lote:id,nombre', 'faena:id,fecha', 'sacrificio:id,fecha', 'usuario:id,name'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Costos/Index', [
            'costos' => $costos,
            'totales' => $this->costoService->totales($request),
            'comparacion' => $this->costoService->compararIngresosCostos($request->fecha_desde, $request->fecha_hasta),
            'filtros' => $request->only([
                'categoria', 'fecha_desde', 'fecha_hasta', 'animal_id', 'lote_id',
                'faena_id', 'sacrificio_id', 'tipo_costo', 'concepto',
            ]),
            'categorias' => Costo::CATEGORIAS,
            'tiposCosto' => Costo::TIPOS_COSTO,
            'animales' => Animal::orderBy('arete')->get(['id', 'arete', 'alias']),
            'lotes' => Lote::orderBy('nombre')->get(['id', 'nombre']),
            'faenas' => Faena::orderByDesc('fecha')->get(['id', 'fecha', 'animal_id']),
            'sacrificios' => Sacrificio::orderByDesc('fecha')->get(['id', 'fecha', 'animal_id']),
        ]);
    }

    public function resumen(Request $request)
    {
        return response()->json([
            'totales' => $this->costoService->totales($request),
            'comparacion' => $this->costoService->compararIngresosCostos($request->fecha_desde, $request->fecha_hasta),
        ]);
    }

    private function reglasValidacion(): array
    {
        return [
            'concepto' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:2000',
            'categoria' => 'required|string|in:' . implode(',', Costo::CATEGORIAS),
            'tipo_costo' => 'required|string|in:' . implode(',', Costo::TIPOS_COSTO),
            'monto' => 'required|numeric|min:0.01',
            'cantidad' => 'nullable|numeric|min:0',
            'unidad_medida' => 'nullable|string|max:50',
            'fecha' => 'required|date',
            'animal_id' => 'nullable|exists:animals,id',
            'lote_id' => 'nullable|exists:lotes,id',
            'faena_id' => 'nullable|exists:faenas,id',
            'sacrificio_id' => 'nullable|exists:sacrificios,id',
            'proveedor' => 'nullable|string|max:255',
            'numero_comprobante' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string|max:2000',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->reglasValidacion());

        if ($this->costoService->existeDuplicadoReciente($validated, Auth::user()->cuentaId())) {
            return back()->withErrors([
                'concepto' => 'Ya registraste un costo idéntico hace unos minutos. Si no es un error, cambia algún dato o espera unos minutos.',
            ])->withInput();
        }

        $validated['user_id'] = Auth::id();

        Costo::create($validated);

        return back()->with('success', 'Costo registrado exitosamente.');
    }

    public function update(Request $request, Costo $costo)
    {
        $validated = $request->validate($this->reglasValidacion());

        $costo->update($validated);

        return back()->with('success', 'Costo actualizado.');
    }

    public function destroy(Costo $costo)
    {
        $costo->delete();

        return back()->with('success', 'Costo eliminado.');
    }

    public function exportarCsv(Request $request)
    {
        $costos = $this->costoService->aplicarFiltros($request)
            ->with(['animal:id,arete,alias', 'lote:id,nombre'])
            ->orderByDesc('fecha')
            ->get();

        $nombreArchivo = 'costos_' . now()->format('Ymd_His') . '.csv';

        $callback = function () use ($costos) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Fecha', 'Concepto', 'Categoria', 'Tipo', 'Monto', 'Cantidad', 'Unidad',
                'Animal', 'Lote', 'Proveedor', 'Numero Comprobante', 'Observaciones',
            ]);

            foreach ($costos as $costo) {
                fputcsv($handle, [
                    $costo->fecha->format('Y-m-d'),
                    $costo->concepto,
                    $costo->categoria,
                    $costo->tipo_costo,
                    $costo->monto,
                    $costo->cantidad,
                    $costo->unidad_medida,
                    $costo->animal?->arete ?? '',
                    $costo->lote?->nombre ?? '',
                    $costo->proveedor,
                    $costo->numero_comprobante,
                    $costo->observaciones,
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $nombreArchivo, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportarPdf(Request $request)
    {
        $costos = $this->costoService->aplicarFiltros($request)
            ->with(['animal:id,arete,alias', 'lote:id,nombre'])
            ->orderByDesc('fecha')
            ->get();

        $totales = $this->costoService->totales($request);
        $comparacion = $this->costoService->compararIngresosCostos($request->fecha_desde, $request->fecha_hasta);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('costos.pdf', [
            'costos' => $costos,
            'totales' => $totales,
            'comparacion' => $comparacion,
            'filtros' => $request->all(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('costos_' . now()->format('Ymd_His') . '.pdf');
    }
}
