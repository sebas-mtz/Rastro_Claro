<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Costo;
use App\Models\EventoSalud;
use App\Models\Tratamiento;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CostoController extends Controller
{
    /**
     * Vista principal del módulo de Costos.
     * Acepta ?animal_id=X para filtrar por borrega.
     */
    public function index(Request $request): Response
    {
        $userId   = $request->user()->id;
        // ✅ CORRECCIÓN: definir $animalId desde el request antes de usarlo
        $animalId = $request->input('animal_id');

        $animales = Animal::select('id', 'arete', 'alias', 'especie', 'raza', 'sexo', 'estado_productivo')
            ->orderBy('arete')
            ->get()
            ->map(fn($a) => [
                'id'      => $a->id,
                'label'   => collect([
                                $a->arete ? "#$a->arete" : null,
                                $a->alias  ?: null,
                                $a->especie ? "($a->especie)" : null,
                             ])->filter()->join(' '),
                'arete'   => $a->arete,
                'alias'   => $a->alias,
                'especie' => $a->especie,
            ]);

        // Si no hay animal seleccionado y hay animales, usar el primero
        if (!$animalId && $animales->isNotEmpty()) {
            $animalId = $animales->first()['id'];
        }

        $costos       = collect();
        $totales      = [];
        $totalGeneral = 0;
        $porEtapa     = [];
        $porCategoria = [];
        $porFecha     = [];
        $animalInfo   = null;

        if ($animalId) {
            // Sincronizar automáticamente gastos de salud pendientes de importar
            $this->sincronizarGastosSalud((int) $animalId, $userId);

            // Cargar costos del animal
            $costos = Costo::with('animal')
                ->where('animal_id', $animalId)
                ->where('user_id', $userId)
                ->orderByDesc('fecha')
                ->get()
                ->map(fn($c) => [
                    'id'           => $c->id,
                    'fecha'        => $c->fecha->format('Y-m-d'),
                    'etapa'        => $c->etapa,
                    'etapa_label'  => Costo::ETAPAS[$c->etapa] ?? $c->etapa,
                    'categoria'    => $c->categoria,
                    'concepto'     => $c->concepto,
                    'costo'        => (float) $c->costo,
                    'observaciones'=> $c->observaciones,
                    'origen'       => $c->origen,
                ]);

            // Totales por categoría
            $totales = [
                'alimentacion' => (float) $costos->where('categoria', 'alimentacion')->sum('costo'),
                'salud'        => (float) $costos->where('categoria', 'salud')->sum('costo'),
                'manejo'       => (float) $costos->where('categoria', 'manejo')->sum('costo'),
                'otros'        => (float) $costos->where('categoria', 'otros')->sum('costo'),
            ];
            $totalGeneral = array_sum($totales);

            // Por etapa (para gráfica)
            $porEtapa = $costos
                ->groupBy('etapa')
                ->map(fn($g, $etapa) => [
                    'etapa' => Costo::ETAPAS[$etapa] ?? $etapa,
                    'total' => round($g->sum('costo'), 2),
                ])
                ->values()
                ->toArray();

            // Por categoría (para gráfica)
            $porCategoria = $costos
                ->groupBy('categoria')
                ->map(fn($g, $cat) => [
                    'categoria' => Costo::CATEGORIAS[$cat] ?? $cat,
                    'total'     => round($g->sum('costo'), 2),
                ])
                ->values()
                ->toArray();

            // Por fecha — agrupado por mes (para gráfica de línea)
            $porFecha = $costos
                ->groupBy(fn($c) => substr($c['fecha'], 0, 7))
                ->map(fn($g, $mes) => [
                    'mes'   => $mes,
                    'total' => round($g->sum('costo'), 2),
                ])
                ->sortKeys()
                ->values()
                ->toArray();

            // Info del animal seleccionado
            $animalInfo = $animales->firstWhere('id', $animalId);
        }

        return Inertia::render('Costos/Index', [
            'animales'     => $animales,
            'animalId'     => $animalId ? (int) $animalId : null,
            'animalInfo'   => $animalInfo,
            'costos'       => $costos,
            'totales'      => $totales,
            'totalGeneral' => round($totalGeneral, 2),
            'porEtapa'     => $porEtapa,
            'porCategoria' => $porCategoria,
            'porFecha'     => $porFecha,
            'etapas'       => Costo::ETAPAS,
            'categorias'   => Costo::CATEGORIAS,
        ]);
    }

    /**
     * Registrar un costo manual.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'animal_id'    => 'required|exists:animals,id',
            'fecha'        => 'required|date',
            'etapa'        => 'required|in:' . implode(',', array_keys(Costo::ETAPAS)),
            'categoria'    => 'required|in:alimentacion,salud,manejo,otros',
            'concepto'     => 'required|string|max:255',
            'costo'        => 'required|numeric|min:0',
            'observaciones'=> 'nullable|string|max:1000',
            'num_dias'     => 'nullable|integer|min:1',
        ]);

        $costo = $validated['costo'];

        // Si el usuario ingresa costo diario × días
        if (!empty($validated['num_dias']) && $validated['num_dias'] > 1) {
            $costo = $validated['costo'] * $validated['num_dias'];
            $validated['concepto'] .= " ({$validated['num_dias']} días × $" . number_format($validated['costo'], 2) . ")";
        }

        Costo::create([
            'animal_id'    => $validated['animal_id'],
            'user_id'      => $request->user()->id,
            'fecha'        => $validated['fecha'],
            'etapa'        => $validated['etapa'],
            'categoria'    => $validated['categoria'],
            'concepto'     => $validated['concepto'],
            'costo'        => $costo,
            'observaciones'=> $validated['observaciones'] ?? null,
            'origen'       => 'manual',
        ]);

        return redirect()
            ->route('costos.index', ['animal_id' => $validated['animal_id']])
            ->with('success', 'Gasto registrado correctamente.');
    }

    /**
     * Eliminar un costo.
     */
    public function destroy(Request $request, Costo $costo): RedirectResponse
    {
        if ($costo->user_id !== $request->user()->id) {
            abort(403);
        }

        $animalId = $costo->animal_id;
        $costo->delete();

        return redirect()
            ->route('costos.index', ['animal_id' => $animalId])
            ->with('success', 'Gasto eliminado.');
    }

    /**
     * Sincroniza automáticamente los gastos de salud (EventoSalud y Tratamiento)
     * que tengan costo > 0 y no hayan sido importados todavía.
     */
    private function sincronizarGastosSalud(int $animalId, int $userId): void
    {
        // Importar EventoSalud aplicados con costo que aún no estén en costos
        EventoSalud::where('animal_id', $animalId)
            ->where('user_id', $userId)
            ->where('costo', '>', 0)
            ->where('estado', 'aplicada')
            ->limit(50)
            ->get()
            ->each(fn($e) => Costo::importarDesdeSalud($e));

        // Importar Tratamientos con costo — si la migración aún no corrió,
        // la query fallará silenciosamente en lugar de tirar 500.
        try {
            Tratamiento::where('animal_id', $animalId)
                ->where('user_id', $userId)
                ->where('costo', '>', 0)
                ->limit(50)
                ->get()
                ->each(fn($t) => Costo::importarDesdeTratamiento($t));
        } catch (\Illuminate\Database\QueryException $e) {
            // Columna 'costo' o 'user_id' no existe en tratamientos todavía.
            // Ejecutar: php artisan migrate
            \Illuminate\Support\Facades\Log::warning(
                'CostoController: tratamientos sync omitida — migración pendiente. ' . $e->getMessage()
            );
        }
    }
}