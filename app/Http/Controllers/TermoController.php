<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\DonadorExterno;
use App\Models\Pajilla;
use App\Models\Termo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TermoController extends Controller
{
    public function index(Request $request)
    {
        $termos = Termo::with([
                'pajillas' => fn($q) => $q->orderBy('canastilla_numero')->orderBy('id'),
                'pajillas.animal',
                'pajillas.donadorExterno',
            ])
            ->withCount(['pajillas', 'pajillasDisponibles'])
            ->latest()
            ->paginate(10);

        $pajillasQuery = Pajilla::with(['termo', 'animal', 'donadorExterno']);

        // Búsqueda por código de pajilla o datos del donador (interno o externo)
        if ($request->filled('search')) {
            $texto = $request->string('search');

            $pajillasQuery->where(function ($q) use ($texto) {
                $q->where('codigo', 'like', "%{$texto}%")
                    ->orWhereHas('animal', function ($q2) use ($texto) {
                        $q2->where('arete', 'like', "%{$texto}%")
                            ->orWhere('alias', 'like', "%{$texto}%")
                            ->orWhere('identificador', 'like', "%{$texto}%")
                            ->orWhere('nombre', 'like', "%{$texto}%");
                    })
                    ->orWhereHas('donadorExterno', function ($q2) use ($texto) {
                        $q2->where('codigo', 'like', "%{$texto}%")
                            ->orWhere('nombre', 'like', "%{$texto}%");
                    });
            });
        }

        // Filtro por estado (utilizada, dañada, inactiva — multiselección)
        if ($request->filled('estados')) {
            $pajillasQuery->whereIn('estado', (array) $request->input('estados'));
        }

        // Orden
        switch ($request->get('sort')) {
            case 'codigo':
                // Códigos que terminan en número se ordenan de menor a mayor;
                // los que no terminan en número (ej. "PajDorper") van al final.
                // Requiere MySQL 8+ / MariaDB 10.0.5+ (soporte de REGEXP_SUBSTR).
                $pajillasQuery
                    ->orderByRaw("CASE WHEN codigo REGEXP '[0-9]+$' THEN 0 ELSE 1 END")
                    ->orderByRaw("CAST(REGEXP_SUBSTR(codigo, '[0-9]+$') AS UNSIGNED) ASC");
                break;
            case 'fecha_colecta':
                $pajillasQuery->orderByDesc('fecha_colecta');
                break;
            default:
                $pajillasQuery->latest();
                break;
        }

        $pajillas = $pajillasQuery
            ->paginate(10)
            ->withQueryString();

        $animales = Animal::where('sexo', 'M')
            ->orderBy('arete')
            ->get();

        $donadoresExternos = DonadorExterno::orderBy('nombre')
            ->get();

        $termosParaModal = Termo::where('estado', 'activo')->orderBy('codigo')->get();
        $termosParaModal = Termo::conOcupacion($termosParaModal);

        return Inertia::render('Genetica/Index', [
            'termos' => $termos,
            'termosActivos' => $termosParaModal,
            'pajillas' => $pajillas,
            'filtrosPajillas' => $request->only(['search', 'sort', 'estados']),
            'animales' => $animales,
            'donadoresExternos' => $donadoresExternos,
            'activeTab' => 'termos',

            'stats' => [
                'termos_activos' => Termo::where('estado', 'activo')->count(),
                'pajillas_disponibles' => Pajilla::where('estado', 'disponible')->count(),
                'pajillas_total' => Pajilla::count(),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Termos/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:50', 'unique:termos,codigo'],
            'nombre' => ['nullable', 'string', 'max:100'],
            'ubicacion' => ['nullable', 'string', 'max:150'],
            'capacidad' => ['required', 'numeric', 'min:0'],
            'numero_canastillas' => ['required', 'integer', 'min:1'],
            'capacidad_canastilla' => ['required', 'integer', 'min:1'],
            'estado' => ['required', 'in:activo,inactivo,mantenimiento'],
            'descripcion' => ['nullable', 'string', 'max:500'],
        ]);

        Termo::create($data);

        return redirect()
            ->route('genetica.index')
            ->with('success', 'Termo registrado correctamente.');
    }

    public function show(Termo $termo)
    {
        $termo->load([
            'pajillas.animal',
            'pajillas.donadorExterno',
        ]);

        return Inertia::render('Termos/Show', [
            'termo' => $termo,
        ]);
    }

    public function edit(Termo $termo)
    {
        return Inertia::render('Termos/Edit', [
            'termo' => $termo,
        ]);
    }

    public function update(Request $request, Termo $termo)
    {
        $data = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:50',
                'unique:termos,codigo,' . $termo->id,
            ],
            'nombre' => ['nullable', 'string', 'max:100'],
            'ubicacion' => ['nullable', 'string', 'max:150'],
            'capacidad' => ['required', 'numeric', 'min:0'],
            'numero_canastillas' => ['required', 'integer', 'min:1'],
            'capacidad_canastilla' => ['required', 'integer', 'min:1'],
            'estado' => ['required', 'in:activo,inactivo,mantenimiento'],
            'descripcion' => ['nullable', 'string', 'max:500'],
        ]);

        $seDesactiva = $data['estado'] !== 'activo' && $termo->estado === 'activo';

        if ($seDesactiva) {
            $pajillasActivas = Pajilla::where('termo_id', $termo->id)
                ->where('estado', 'disponible')
                ->count();

            // Primer intento: avisamos y pedimos confirmación explícita.
            if ($pajillasActivas > 0 && !$request->boolean('confirmar_inutilizar')) {
                return back()->withErrors([
                    'estado' => "Este termo tiene {$pajillasActivas} pajilla(s) disponible(s). "
                        . "Muévelas a otro termo o confirma para marcarlas como inactivas.",
                ])->withInput();
            }

            // El usuario confirmó: se inutilizan las pajillas que quedaron sin termo activo.
            if ($pajillasActivas > 0) {
                Pajilla::where('termo_id', $termo->id)
                    ->where('estado', 'disponible')
                    ->update(['estado' => 'inactiva']);
            }
        }

        $termo->update($data);

        return redirect()
            ->route('genetica.index')
            ->with('success', 'Termo actualizado correctamente.');
    }

    public function destroy(Termo $termo)
    {
        $termo->delete();

        return redirect()
            ->route('genetica.index')
            ->with('success', 'Termo eliminado correctamente.');
    }
}