<?php
namespace App\Http\Controllers;
use App\Models\Animal;
use App\Models\Lote;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnimalController extends Controller
{
    private $especies = [
        "Bovino","Porcino","Caprino","Ovino","Equino","Gallos","Aves de corral (gallinas y pollitos)"
    ];

    private $razasPorEspecie = [
        "Bovino" => ["Holstein", "Angus", "Hereford", "Simmental", "Otra"],
        "Porcino" => ["Yorkshire", "Landrace", "Duroc", "Pietrain", "Otra"],
        "Caprino" => ["Saanen", "Boer", "Alpina", "Toggenburg", "Otra"],
        "Ovino" => ["Dorper", "Merino", "Suffolk", "Katahdin", "Otra"],
        "Equino" => ["Cuarto de Milla", "Pura Sangre", "Árabe", "Criollo", "Otra"],
        "Aves de corral (gallinas y pollitos)" => ["Leghorn", "Rhode Island", "Plymouth Rock", "Sussex", "Otra"],

        // 🐓 Razas de gallos (USA + LATAM)
        "Gallos" => [
            "Gallos de pelea (Asil)",
            "Gallos Kelso",
            "Gallos Hatch",
            "Gallos Sweater",
            "Gallos Shamo",
            "Gallos Cuban Brown",
            "Gallos Navajeros (LATAM)",
            "Otra"
        ],
    ];

    private $estadosProductivos = [
        "Bovino" => ["Vaca seca", "Lactante", "Gestante", "En crecimiento", "Reproductor"],
        "Caprino" => ["Gestante", "En crecimiento", "Lactante", "Reproductor"],
        "Ovino" => ["Gestante", "En crecimiento", "Reproductor"],
        "Porcino" => ["Gestante", "En crecimiento", "Reproductor"],
        "Equino" => ["En entrenamiento", "Reproductor", "En descanso"],
        "Aves de corral" => ["Postura", "En descanso", "En crecimiento"],

        // 🐓 Estados para gallos
        "Gallos" => [
            "En crecimiento",
            "Reproductor",
            "De pelea / exhibición",
            "En entrenamiento",
            "En descanso"
        ],
    ];

    public function index()
    {
        return Inertia::render('Animals/Index', [
            'animales' => Animal::with('lote')->get(),
            'lotes' => Lote::all(),
            'especies' => $this->especies,
            'razasPorEspecie' => $this->razasPorEspecie,
            'estadosProductivos' => $this->estadosProductivos,
        ]);
    }


    public function store(Request $request)
    {
        // Validación general
        $validated = $request->validate([
            'especie' => 'required|string',
            'alias' => 'nullable|string|max:255',
            'raza' => 'nullable|string|max:255',
            'arete' => 'required|string',
            'sexo' => 'required|in:M,F',
            'fecha_nac' => 'nullable|date',
            'peso' => 'nullable|numeric',
            'BCS' => 'nullable|numeric',
            'estado_productivo' => 'nullable|string',
            'lote_id' => 'nullable|exists:lotes,id',
        ]);

        // ⚠️ Validación personalizada: arete no se repite en misma raza
        $repiteRaza = Animal::where('raza', $request->raza)
                            ->where('arete', $request->arete)
                            ->exists();

        if ($repiteRaza) {
            return back()->withErrors([
                'arete' => 'Ya existe un animal con este arete en esta misma raza.'
            ]);
        }

        // ⚠️ Validación: arete no se repite dentro del mismo lote
        if ($request->lote_id) {
            $repiteLote = Animal::where('lote_id', $request->lote_id)
                                ->where('arete', $request->arete)
                                ->exists();

            if ($repiteLote) {
                return back()->withErrors([
                    'arete' => 'Ya existe un animal con este arete en este mismo lote.'
                ]);
            }
        }

        Animal::create($validated);

        return back()->with('success', 'Animal agregado exitosamente.');
    }

    public function show(Animal $animal)
    {
        $animal->load(['lote', 'producciones']);

        return Inertia::render('Animals/ShowAnimal', [
            'animal' => $animal,
            'lotes' => Lote::all(),
            'especies' => $this->especies,
            'razasPorEspecie' => $this->razasPorEspecie,
            'estadosProductivos' => $this->estadosProductivos,
            'pesajes' => fn($q) => $q->orderBy('fecha', 'asc'),
 
    // Alimentaciones con su ración, las más recientes primero, limitadas a 10
    'alimentaciones' => fn($q) => $q->with('racion')->latest('fecha')->take(10),
        ]);
    }

    public function edit(Animal $animal)
    {


        return Inertia::render('Animals/Edit', [
            'animal' => $animal,
            'lotes' => Lote::all(),
            'especies' => $this->especies,
            'razasPorEspecie' => $this->razasPorEspecie,
            'estadosProductivos' => $this->estadosProductivos,
        ]);
    }

    public function update(Request $request, Animal $animal)
    {
        $validated = $request->validate([
            'especie' => 'required|string',
            'alias' => 'nullable|string|max:255',
            'raza' => 'nullable|string|max:255',
            'arete' => 'required|string',
            'sexo' => 'required|in:M,F',
            'fecha_nac' => 'nullable|date',
            'peso' => 'nullable|numeric',
            'BCS' => 'nullable|numeric',
            'estado_productivo' => 'nullable|string',
            'lote_id' => 'nullable|exists:lotes,id',
        ]);
        $repite = Animal::where('id', '!=', $animal->id)
                        ->where(function($q) use ($request) {
                            $q->where('raza', $request->raza)
                              ->orWhere('lote_id', $request->lote_id);
                        })
                        ->where('arete', $request->arete)
                        ->exists();
        if ($repite) {
            return back()->withErrors([
                'arete' => 'Este arete ya está usado en esta raza o lote.'
            ]);
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