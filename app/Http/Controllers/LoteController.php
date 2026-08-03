<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use App\Models\User;
use App\Models\Animal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoteController extends Controller
{
    // Mostrar todos los lotes
    public function index()
    {
        $lotes = Lote::with(['responsable', 'animales'])->get();
        $usuarios = $this->responsablesDisponibles();

        // Sistema exclusivamente ovino: la especie no se elige y las razas
        // se administran desde el catálogo configurable (tabla `razas`).
        $especies = [\App\Models\Animal::ESPECIE];

        $razas = \App\Models\Raza::activa()->orderBy('nombre')->get(['id', 'nombre']);

        $estadosProductivos = \App\Services\EstadoProductivoService::estadosManualesPorEspecie();

        $tiposLote = \App\Models\Lote::TIPOS;

        return Inertia::render('Lotes/Index', compact(
            'lotes',
            'usuarios',
            'especies',
            'razas',
            'estadosProductivos',
            'tiposLote'
        ));
    }

    // Guardar nuevo lote + animales
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'corral_potrero' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:255',
            'responsable_id' => 'nullable',
            'tipo' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(Lote::TIPOS))],
            'capacidad' => 'nullable|integer|min:0',

            // Campos del ganado ovino. La especie no se envía: es siempre Ovino.
            'animal.raza_id' => 'nullable|exists:razas,id',
            'animal.arete_inicio' => 'required|integer|min:1',
            'animal.arete_fin' => 'required|integer|gte:animal.arete_inicio',
            'animal.sexo' => 'required|string|in:M,F',
            'animal.fecha_nac' => 'nullable|date',
            'animal.peso' => 'nullable|numeric|min:0',
            'animal.estado_productivo' => 'nullable|string',
        ]);

        // Crear lote
        $lote = Lote::create([
            'nombre' => $validated['nombre'],
            'corral_potrero' => $validated['corral_potrero'],
            'descripcion' => $validated['descripcion'] ?? null,
            'tipo' => $validated['tipo'] ?? null,
            'capacidad' => $validated['capacidad'] ?? null,
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
                'especie' => Animal::ESPECIE,
                'raza_id' => $animalData['raza_id'] ?? null,
                'arete' => (string)$i,
                'sexo' => $animalData['sexo'],
                'fecha_nac' => $animalData['fecha_nac'] ?? null,
                'peso' => $animalData['peso'] ?? null,
                'estado_productivo' => $animalData['estado_productivo'] ?? null,
            ]);
        }

        $mensaje = 'Lote y ejemplares registrados correctamente.';
        if (!empty($duplicados)) {
            $mensaje .= ' Los siguientes aretes ya existían y no fueron registrados: ' . implode(', ', $duplicados);
        }

        return redirect()->route('lotes.index')->with('success', $mensaje);
    }

    // Editar lote
    public function edit(Lote $lote)
    {
        $usuarios = $this->responsablesDisponibles();

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

    /**
     * Personas que pueden quedar como responsables de un lote.
     *
     * Antes devolvía únicamente al propio usuario, porque cada cuenta era un
     * rancho de una sola persona. Ahora lista a quienes trabajan en el mismo
     * rancho, que es lo que el desplegable siempre quiso decir.
     */
    private function responsablesDisponibles()
    {
        $cuentaId = Auth::user()?->cuentaId();

        return User::where('cuenta_id', $cuentaId)
            ->where('activo', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
