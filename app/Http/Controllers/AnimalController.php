<?php
namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Lote;
use App\Services\EstadoProductivoService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnimalController extends Controller
{
    private array $especies = [
        "Bovino", "Porcino", "Caprino", "Ovino", "Equino",
        "Gallos", "Aves de corral (gallinas y pollitos)",
    ];

    private array $razasPorEspecie = [
        "Bovino"  => ["Holstein", "Angus", "Hereford", "Simmental", "Otra"],
        "Porcino" => ["Yorkshire", "Landrace", "Duroc", "Pietrain", "Otra"],
        "Caprino" => ["Saanen", "Boer", "Alpina", "Toggenburg", "Otra"],
        "Ovino"   => ["Dorper", "Merino", "Suffolk", "Katahdin", "Otra"],
        "Equino"  => ["Cuarto de Milla", "Pura Sangre", "Árabe", "Criollo", "Otra"],
        "Aves de corral (gallinas y pollitos)" => ["Leghorn", "Rhode Island", "Plymouth Rock", "Sussex", "Otra"],
        "Gallos"  => [
            "Gallos de pelea (Asil)", "Gallos Kelso", "Gallos Hatch", "Gallos Sweater",
            "Gallos Shamo", "Gallos Cuban Brown", "Gallos Navajeros (LATAM)", "Otra",
        ],
    ];

    // Sin constructor — estadosProductivos viene directo del servicio cuando se necesita

    public function index()
    {
        return Inertia::render('Animals/Index', [
            'animales'           => Animal::with('lote')->get(),
            'lotes'              => Lote::all(),
            'especies'           => $this->especies,
            'razasPorEspecie'    => $this->razasPorEspecie,
'estadosProductivos' => EstadoProductivoService::estadosManualesPorEspecie(), 
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'especie'           => 'required|string',
            'alias'             => 'nullable|string|max:255',
            'raza'              => 'nullable|string|max:255',
            'arete'             => 'required|string',
            'sexo'              => 'required|in:M,F',
            'fecha_nac'         => 'nullable|date',
            'peso'              => 'nullable|numeric',
            'BCS'               => 'nullable|numeric',
            'estado_productivo' => 'nullable|string',
            'lote_id'           => 'nullable|exists:lotes,id',
        ]);

        if (Animal::where('raza', $request->raza)->where('arete', $request->arete)->exists()) {
            return back()->withErrors(['arete' => 'Ya existe un animal con este arete en esta misma raza.']);
        }

        if ($request->lote_id && Animal::where('lote_id', $request->lote_id)->where('arete', $request->arete)->exists()) {
            return back()->withErrors(['arete' => 'Ya existe un animal con este arete en este mismo lote.']);
        }

        Animal::create($validated);

        return back()->with('success', 'Animal agregado exitosamente.');
    }

    public function show(Animal $animal)
    {
        $animal->load([
            'lote',
            'madre',
            'padre',
            'madre.madre',   // abuela materna
            'madre.padre',   // abuelo materno
            'padre.madre',   // abuela paterna
            'padre.padre',   // abuelo paterno
            'crias',         // descendencia directa
            'producciones'   => fn($q) => $q->latest('fecha')->take(10),
            'pesajes'        => fn($q) => $q->orderBy('fecha', 'asc'),
            'alimentaciones' => fn($q) => $q->with('racion')->latest('fecha')->take(10),
        ]);

        return Inertia::render('Animals/ShowAnimal', [
            'animal'             => $animal,
            'lotes'              => Lote::all(),
            'especies'           => $this->especies,
            'razasPorEspecie'    => $this->razasPorEspecie,
'estadosProductivos' => EstadoProductivoService::estadosManualesPorEspecie(), 
        ]);
    }

    public function edit(Animal $animal)
    {
        return Inertia::render('Animals/Edit', [
            'animal'             => $animal,
            'lotes'              => Lote::all(),
            'especies'           => $this->especies,
            'razasPorEspecie'    => $this->razasPorEspecie,
'estadosProductivos' => EstadoProductivoService::estadosManualesPorEspecie(), 
       ]);
    }

    public function update(Request $request, Animal $animal)
    {
        $validated = $request->validate([
            'especie'           => 'required|string',
            'alias'             => 'nullable|string|max:255',
            'raza'              => 'nullable|string|max:255',
            'arete'             => 'required|string',
            'sexo'              => 'required|in:M,F',
            'fecha_nac'         => 'nullable|date',
            'peso'              => 'nullable|numeric',
            'BCS'               => 'nullable|numeric',
            'estado_productivo' => 'nullable|string',
            'lote_id'           => 'nullable|exists:lotes,id',
        ]);

        $repite = Animal::where('id', '!=', $animal->id)
            ->where('arete', $request->arete)
            ->where(fn($q) => $q
                ->where('raza', $request->raza)
                ->orWhere('lote_id', $request->lote_id)
            )
            ->exists();

        if ($repite) {
            return back()->withErrors(['arete' => 'Este arete ya está usado en esta raza o lote.']);
        }

        $animal->update($validated);

        return back()->with('success', 'Animal actualizado.');
    }

    public function destroy(Animal $animal)
    {
        $animal->delete();
        return back()->with('success', 'Animal eliminado.');
    }
}