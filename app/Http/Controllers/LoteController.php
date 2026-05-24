<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use App\Models\User;
use App\Models\Animal;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LoteController extends Controller
{
    // Mostrar todos los lotes
    public function index()
    {
        $lotes = Lote::with(['responsable', 'animales'])->get();
        $usuarios = User::all();

        $especies = ["Bovino","Porcino","Caprino","Ovino","Equino","Gallos","Aves de corral (gallinas y pollitos)"];

        $razasPorEspecie = [
            'Bovino' => ["Holstein", "Angus", "Hereford", "Simmental", "Otra"],
            'Porcino' => ["Yorkshire", "Landrace", "Duroc", "Pietrain", "Otra"],
            'Caprino' => ["Saanen", "Boer", "Alpina", "Toggenburg", "Otra"],
            'Ovino' => ["Dorper", "Merino", "Suffolk", "Katahdin", "Otra"],
            'Equino'=> ["Cuarto de Milla", "Pura Sangre", "Árabe", "Criollo", "Otra"],
            'Gallos' => [ "Gallos de pelea (Asil)", "Gallos Kelso","Gallos Hatch", "Gallos Sweater", "Gallos Shamo", 
            "Gallos Cuban Brown", "Gallos Navajeros (LATAM)","Otra"],
            'Aves de corral'=> ["Leghorn", "Rhode Island", "Plymouth Rock", "Otra"],
        ];

        $estadosProductivos = [
            'Bovino' => ["Vaca seca", "Lactante", "Gestante", "En crecimiento", "Reproductor"],
            'Caprino' =>["Gestante", "En crecimiento", "Lactante", "Reproductor"],
            'Ovino' =>["Gestante", "En crecimiento", "Reproductor"],
            'Porcino' =>["Gestante", "En crecimiento", "Reproductor"],
            'Equino' =>["En entrenamiento", "Reproductor", "En descanso"],
            'Aves de corral'=> ["Postura", "En descanso", "En crecimiento"],
            'Gallos' => ["Reproductor", "En crecimiento", "En descanso", "De pelea / exhibición", 
            "En entrenamiento"]
        ];

        return Inertia::render('Lotes/Index', compact(
            'lotes',
            'usuarios',
            'especies',
            'razasPorEspecie',
            'estadosProductivos'
        ));
    }

    // Guardar nuevo lote + animales
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'corral_potrero' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:255',
            'responsable_id' => 'required|exists:users,id',

            // Campos del ganado
            'animal.especie' => 'required|string',
            'animal.raza' => 'nullable|string',
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
            'responsable_id' => $validated['responsable_id'],
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
        $usuarios = User::all();

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
            'responsable_id' => 'nullable|exists:users,id',
        ]);

        $lote->update($request->all());

        return redirect()->route('lotes.index')->with('success', 'Lote actualizado correctamente');
    }

    // Eliminar lote
    public function destroy(Lote $lote)
    {
        $lote->delete();
        return redirect()->route('lotes.index')->with('success', 'Lote eliminado correctamente');
    }
}