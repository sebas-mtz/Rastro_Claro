<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Lote;
use App\Models\Reproduccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ReproduccionController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['tipo_evento', 'estado', 'fecha_desde', 'fecha_hasta', 'hembra_id']);

        $query = Reproduccion::with([
                'hembra:id,alias,arete,especie,sexo,lote_id',
                'macho:id,alias,arete,especie,sexo,lote_id',
                'lote:id,nombre,especie',
            ])
            ->latest('fecha')
            ->latest('id');

        if ($request->filled('tipo_evento')) $query->where('tipo_evento', $request->tipo_evento);
        if ($request->filled('estado')) $query->where('estado', $request->estado);
        if ($request->filled('hembra_id')) $query->where('hembra_id', $request->hembra_id);
        if ($request->filled('fecha_desde')) $query->whereDate('fecha', '>=', $request->fecha_desde);
        if ($request->filled('fecha_hasta')) $query->whereDate('fecha', '<=', $request->fecha_hasta);

        $reproducciones = $query->paginate(10)->through(function ($r) {
            return [
                'id' => $r->id,
                'fecha' => optional($r->fecha)->format('Y-m-d'),
                'tipo_evento' => $r->tipo_evento,
                'estado' => $r->estado,
                'metodo' => $r->metodo,
                'diagnostico' => $r->diagnostico,
                'lote_nombre' => $r->lote?->nombre,
                'hembra' => $r->hembra ? [
                    'id' => $r->hembra->id,
                    'alias' => $r->hembra->alias ?? $r->hembra->arete ?? 'Sin alias',
                    'especie' => $r->hembra->especie,
                ] : null,
                'macho' => $r->macho ? [
                    'id' => $r->macho->id,
                    'alias' => $r->macho->alias ?? $r->macho->arete ?? 'Sin alias',
                    'especie' => $r->macho->especie,
                ] : null,
                'parto' => $r->tipo_evento === 'parto' ? [
                    'numero_crias' => $r->numero_crias,
                    'complicaciones' => (bool) $r->complicaciones,
                ] : null,
            ];
        });

        // Animales para selects (solo disponibles si quieres)
        $animales = Animal::with('lote:id,nombre')
            ->select('id', 'alias', 'arete', 'especie', 'sexo', 'lote_id', 'estado_productivo')
            ->whereNotIn('estado_productivo', ['sacrificado', 'faenado', 'vendido']) // recomendado
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'alias' => $a->alias ?? $a->arete ?? 'Sin alias',
                'arete' => $a->arete,
                'especie' => $a->especie,
                'sexo' => $a->sexo,
                'lote_nombre' => $a->lote?->nombre ?? 'Sin lote',
            ]);

        // Lotes (si quieres puedes reutilizar tu lógica de "disponibles")
        $lotes = Lote::select('id', 'nombre')->get();

        return Inertia::render('Reproduccion/Index', [
            'reproducciones' => $reproducciones,
            'animales' => $animales,
            'lotes' => $lotes,
            'filters' => $filters,
        ]);
    }

    public function store(Request $request)
    {
        $TIPOS = ['celo', 'monta', 'inseminacion', 'diagnostico_gestacion', 'aborto', 'parto', 'destete', 'otro'];
        $ESTADOS = ['pendiente', 'confirmado', 'fallido', 'cancelado'];

        $validated = $request->validate([
            'hembra_id' => [
                'required',
                Rule::exists('animals', 'id')->where(fn ($q) => $q->where('sexo', 'F')),
            ],
            'macho_id' => [
                'nullable',
                Rule::exists('animals', 'id')->where(fn ($q) => $q->where('sexo', 'M')),
            ],
            'lote_id' => ['nullable', 'exists:lotes,id'],
            'tipo_evento' => ['required', Rule::in($TIPOS)],
            'fecha' => ['required', 'date'],
            'estado' => ['required', Rule::in($ESTADOS)],

            'metodo' => ['nullable', 'string', 'max:255'],
            'semen_codigo' => ['nullable', 'string', 'max:255'],
            'diagnostico' => ['nullable', 'string', 'max:255'],

            'costo' => ['nullable', 'numeric', 'min:0'],
            'numero_crias' => ['nullable', 'integer', 'min:0'],
            'peso_total_crias' => ['nullable', 'numeric', 'min:0'],
            'complicaciones' => ['nullable', 'boolean'],
            'detalle_complicaciones' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],
        ]);

        // Reglas por tipo
        if ($validated['tipo_evento'] === 'inseminacion' && empty($validated['semen_codigo'])) {
            return back()->withErrors(['semen_codigo' => 'Para inseminación, el código de semen es requerido.']);
        }

        if ($validated['tipo_evento'] === 'diagnostico_gestacion' && empty($validated['diagnostico'])) {
            return back()->withErrors(['diagnostico' => 'Para diagnóstico, el campo diagnóstico es requerido.']);
        }

        if ($validated['tipo_evento'] === 'parto' && $validated['numero_crias'] === null) {
            return back()->withErrors(['numero_crias' => 'Para parto, el número de crías es requerido.']);
        }

        // Checkbox: si viene null, lo dejamos en false
        $validated['complicaciones'] = (bool) ($validated['complicaciones'] ?? false);

        DB::beginTransaction();
        try {
            $validated['user_id'] = Auth::id();

            Reproduccion::create($validated);

            DB::commit();

            return redirect()->route('reproducciones.index')->with([
                'message' => 'Evento reproductivo registrado correctamente.',
                'type' => 'success',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors([
                'message' => 'Error al guardar reproducción: ' . $e->getMessage(),
            ]);
        }
    }
}
