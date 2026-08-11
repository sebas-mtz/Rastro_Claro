<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use App\Models\User;
use App\Models\Animal;
use App\Services\EstadoProductivoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoteController extends Controller
{
    // Las especies ya NO se hardcodean aquí. Se obtienen de
    // EstadoProductivoService, la misma fuente que usa AnimalController y
    // que validan StoreAnimalRequest/UpdateAnimalRequest — así los tres
    // lugares quedan atados a un único catálogo y no pueden volver a
    // desincronizarse como pasaba antes.
    private function especiesDisponibles(): array
    {
        return array_keys(EstadoProductivoService::estadosPorEspecie());
    }

    // IMPORTANTE: debe reflejar exactamente el mismo catálogo que
    // AnimalController::$razasPorEspecie. EstadoProductivoService no
    // conoce razas (solo estados productivos), así que este catálogo
    // sigue siendo responsabilidad de cada controlador por ahora. Si en
    // algún momento quieres centralizarlo también, se podría mover a un
    // servicio propio (ej. RazaService) o a config/ganado.php.
    private array $razasPorEspecie = [
        "Ovino" => [
            // Razas de Pelo
            "Katahdin", "Pelibuey", "Dorper", "White Dorper", "Blackbelly", "Saint Croix",

            // Razas Cárnicas de Lana
            "Suffolk", "Hampshire", "Dorset", "Texel", "Charollais",

            // Razas de Lana / Doble Propósito
            "Rambouillet", "Corriedale", "Columbia", "Merino",

            // Autóctonas y Criollas
            "Borrego de Chiapas",

            // Genérica / Otras
            "Otra",
        ],
    ];

    public function storeBasico(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'corral_potrero' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        $lote = Lote::create([
            ...$validated,
            'responsable_id' => Auth::id(),
        ]);

        return response()->json([
            'lote' => $lote->only('id', 'nombre', 'corral_potrero'),
        ], 201);
    }

    // Mostrar todos los lotes
    public function index()
    {
        $lotes = Lote::with(['responsable', 'animales'])->get();
        $usuarios = User::whereKey(Auth::id())->get();

        return Inertia::render('Lotes/Index', [
            'lotes' => $lotes,
            'usuarios' => $usuarios,
            'especies' => $this->especiesDisponibles(),
            'razasPorEspecie' => $this->razasPorEspecie,
            // Antes había un arreglo de estados hardcodeado aquí, distinto
            // al que usa AnimalController y desalineado con el catálogo
            // real. Ahora se usa la misma fuente de verdad en todo el
            // sistema.
            'estadosProductivos' => EstadoProductivoService::estadosManualesPorEspecie(),
        ]);
    }

    // Guardar nuevo lote + animales
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'corral_potrero' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:255',
            'responsable_id' => 'nullable',

            // Campos del ganado — la especie se valida contra el mismo
            // catálogo que usan StoreAnimalRequest/UpdateAnimalRequest.
            'animal.especie' => ['required', 'string', 'in:' . implode(',', $this->especiesDisponibles())],
            'animal.raza' => ['nullable', 'string', 'in:' . implode(',', $this->razasPorEspecie['Ovino'])],
            'animal.arete_inicio' => 'required|integer|min:1',
            'animal.arete_fin' => 'required|integer|gte:animal.arete_inicio',
            'animal.sexo' => 'required|string|in:M,H',
            'animal.fecha_nac' => 'nullable|date',
            'animal.peso' => 'nullable|numeric|min:0',
            'animal.estado_productivo' => 'nullable|string',
        ]);

        // Crear lote
        $lote = Lote::create([
            'nombre' => $validated['nombre'],
            'corral_potrero' => $validated['corral_potrero'],
            'descripcion' => $validated['descripcion'] ?? null,
            'responsable_id' => Auth::id(),
        ]);

        // Crear animales en rango
        $animalData = $validated['animal'];
        $aretesExistentes = Animal::where('lote_id', $lote->id)
                           ->pluck('arete')
                           ->toArray();


        $duplicados = [];

        for ($i = $animalData['arete_inicio']; $i <= $animalData['arete_fin']; $i++) {
            if (in_array((string)$i, $aretesExistentes)) {
                $duplicados[] = $i;
                continue;
            }

            $lote->animales()->create([
                'especie' => $animalData['especie'],
                'raza' => $animalData['raza'] ?? null,
                'arete' => (string)$i,
                'sexo' => $animalData['sexo'],
                'fecha_nac' => $animalData['fecha_nac'] ?? null,
                'peso' => $animalData['peso'] ?? null,
                'estado_productivo' => $animalData['estado_productivo'] ?? null,
            ]);
        }

        $mensaje = 'Lote y animales registrados correctamente.';
        if (!empty($duplicados)) {
            $mensaje .= ' Los siguientes aretes ya existían y no fueron registrados: ' . implode(', ', $duplicados);
        }

        return redirect()->route('lotes.index')->with('success', $mensaje);
    }

    // Editar lote
    public function edit(Lote $lote)
    {
        $usuarios = User::whereKey(Auth::id())->get();

        return Inertia::render('Lotes/Edit', [
            'lote' => $lote->load('responsable', 'animales'),
            'usuarios' => $usuarios,
        ]);
    }

    // Actualizar lote
    public function update(Request $request, Lote $lote)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'corral_potrero' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string|max:255',
            'responsable_id' => 'nullable',
        ]);

        $lote->update([
            ...$request->only('nombre', 'corral_potrero', 'descripcion'),
            'responsable_id' => Auth::id(),
        ]);

        return redirect()->route('lotes.index')->with('success', 'Lote actualizado correctamente');
    }

    // Eliminar lote
    public function destroy(Lote $lote)
    {
        $lote->delete();
        return redirect()->route('lotes.index')->with('success', 'Lote eliminado correctamente');
    }
}