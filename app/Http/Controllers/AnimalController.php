<?php
namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Lote;
use App\Services\EstadoProductivoService;
use App\Services\EstadoActualAnimalService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\DonadorExterno;
use App\Http\Requests\StoreAnimalRequest;
use App\Http\Requests\UpdateAnimalRequest;
use App\Services\HistorialNacimientoService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class AnimalController extends Controller
{
    // Las especies ya NO se hardcodean aquí. EstadoProductivoService es la
    // misma fuente que usan StoreAnimalRequest/UpdateAnimalRequest para
    // validar el campo "especie" — si el frontend mostrara una especie que
    // el backend no acepta (o viceversa), volveríamos a tener el problema
    // que arrastraba LoteController antes de corregirlo.
    private function especiesDisponibles(): array
    {
        return array_keys(EstadoProductivoService::estadosPorEspecie());
    }

    private array $razasPorEspecie = [
        "Ovino" => [
    // Razas de Pelo
    "Katahdin","Pelibuey","Dorper","White Dorper","Blackbelly","Saint Croix",
    
    // Razas Cárnicas de Lana
    "Suffolk","Hampshire","Dorset","Texel","Charollais",
    
    // Razas de Lana / Doble Propósito
    "Rambouillet","Corriedale","Columbia","Merino",
    
    // Autóctonas y Criollas
    "Borrego de Chiapas",
    
    // Genérica / Otras
    "Otra"
],
           ];

    // Sin constructor — estadosProductivos viene directo del servicio cuando se necesita

    public function index()
    {
        return Inertia::render('Animals/Index', [
            'animales' => Animal::select(
                        'id','arete','alias','especie','raza','sexo','fecha_nac','peso','BCS','lote_id','siniiga_id',
                        'identificador', 'numero_registro','grado_pureza','color', 'estado_productivo','madre_id','padre_id',
                        'padre_externo_id','created_at'
                    )
                    ->with([
                        'lote:id,nombre',
                        'criaOrigen.parto:id,tipo_parto',
                    ])
                    ->get()->map(fn($a) => [
                        'id' => $a->id,'arete' => $a->arete,'alias' => $a->alias,'especie' => $a->especie,'raza' => $a->raza,'sexo' => $a->sexo,
                        'fecha_nac' => $a->fecha_nac,'peso' => $a->peso,'BCS' => $a->BCS,'lote_id' => $a->lote_id,'lote' => $a->lote,
                        'siniiga_id' => $a->siniiga_id,
                        'identificador' => $a->identificador,
                        'numero_registro' => $a->numero_registro,
                        'grado_pureza' => $a->grado_pureza,
                        'color' => $a->color,
                        'estado_productivo' => $a->estado_productivo,
                        'madre_id' => $a->madre_id,'padre_id' => $a->padre_id,
                        'padre_externo_id' => $a->padre_externo_id,
                        'created_at' => $a->created_at,'tipo_parto_origen' => $a->tipo_parto_origen,
                    ]),
            'lotes'              => Lote::all(),
            'especies'           => $this->especiesDisponibles(),
            'razasPorEspecie'    => $this->razasPorEspecie,
            'estadosProductivos' => EstadoProductivoService::estadosManualesPorEspecie(),
            // Antes esta lista vivía hardcodeada en el frontend (Index.jsx)
            // y tenía un bug de casing ("Muerto" vs "muerto") que hacía que
            // esos animales nunca cayeran en la sección de historial. Ahora
            // el frontend la recibe de aquí, que es la misma fuente que usa
            // Animal::booted() y el resto del controlador para comparar.
            'estadosSistema'     => EstadoProductivoService::estadosSistema(),
            'donadoresExternos' => DonadorExterno::orderBy('nombre')->get(),
        ]);
    }

   public function store(
    StoreAnimalRequest $request,
    HistorialNacimientoService $historialNacimientoService
){
     $validated = $request->validated();
    if (
        !empty($validated['padre_id']) &&
        !empty($validated['padre_externo_id'])
    ) {
        return back()->withErrors([
            'padre_id' => 'Selecciona un padre interno o un donador externo, no ambos.',
        ])->withInput();}

    // ── Padre asignado sin madre ─────────────────────────────────────
    // Si se asigna un padre interno pero no hay madre, nunca se ejecuta
    // HistorialNacimientoService::crear() (que es donde normalmente se
    // valida la edad del padre), así que lo validamos aquí directamente
    // usando la fecha de nacimiento del propio animal y su especie para
    // estimar la fecha de concepción.
    if (
        empty($validated['madre_id']) &&
        !empty($validated['padre_id']) &&
        !empty($validated['fecha_nac'])
    ) {
        $fechaServicioEstimada = $historialNacimientoService->calcularFechaServicio(
            $validated['especie'],
            Carbon::parse($validated['fecha_nac'])
        );

        $historialNacimientoService->validarEdadPadre(
            Animal::findOrFail($validated['padre_id']),
            $fechaServicioEstimada
        );
    }

    DB::transaction(function () use ($validated, $historialNacimientoService, $request) {
    $animal = Animal::create($validated);

    if (!empty($validated['madre_id'])) {
        $historialNacimientoService->crear($animal, $validated, $request->user()->id);
    }
});

    return back()->with('success', 'Animal agregado exitosamente.');
}
    public function show(Animal $animal, EstadoActualAnimalService $estadoActualService)
    {
        $animal->load([
            'lote',
            'madre',
            'padre',
            'padreExterno',
            'madre.madre',   // abuela materna
            'madre.padre',   // abuelo materno
            'padre.madre',   // abuela paterna
            'padre.padre',   // abuelo paterno
            'crias',         // descendencia directa
            'producciones'   => fn($q) => $q->latest('fecha')->take(10),
            'pesajes'        => fn($q) => $q->orderBy('fecha', 'asc'),
            'alimentaciones' => fn($q) => $q->with('racion')->latest('fecha')->take(10),
            'muerte',
        ]);

        return Inertia::render('Animals/ShowAnimal', [
            'animal'             => $animal,
            'lotes'              => Lote::all(),
            'especies'           => $this->especiesDisponibles(),
            'razasPorEspecie'    => $this->razasPorEspecie,
'estadosProductivos' => EstadoProductivoService::estadosManualesPorEspecie(), 
            'estadoContextual' => $estadoActualService->obtener($animal),
        ]);
    }


    public function edit(Animal $animal)
    {
        return Inertia::render('Animals/Edit', [
            'animal'             => $animal,
            'lotes'              => Lote::all(),
            'especies'           => $this->especiesDisponibles(),
            'razasPorEspecie'    => $this->razasPorEspecie,
'estadosProductivos' => EstadoProductivoService::estadosManualesPorEspecie(), 
'posiblesPadres' => Animal::where('sexo', 'M')
    ->where('id', '!=', $animal->id)
    ->orderBy('arete')
    ->get(),

'donadoresExternos' => DonadorExterno::orderBy('nombre')->get(),
       ]);
    }


    public function update(
        UpdateAnimalRequest $request,
        Animal $animal,
        HistorialNacimientoService $historialNacimientoService
    )
{
    if ($animal->estado_productivo === 'muerto') {
        return back()->withErrors([
            'animal' => 'Un animal registrado como muerto no puede modificarse.',
        ]);
    }
    $validated = $request->validated();

    // ── Genealogía bloqueada tras el parto o durante gestación ───────
    // Una vez que el animal ya tiene un parto histórico asociado
    // (criaOrigen) o mientras está gestante, madre/padre ya no se pueden
    // modificar: reescribir la genealogía en ese punto invalidaría el
    // historial reproductivo ya generado (servicio + parto) o el
    // seguimiento de la gestación en curso.
    $tieneHistorialReproductivo = $animal->criaOrigen()->exists();
    $estaGestante = strcasecmp((string) $animal->estado_productivo, 'gestante') === 0;

    if ($tieneHistorialReproductivo || $estaGestante) {
        $cambiaMadre = array_key_exists('madre_id', $validated)
            && (int) ($validated['madre_id'] ?? 0) !== (int) ($animal->madre_id ?? 0);

        $cambiaPadre = array_key_exists('padre_id', $validated)
            && (int) ($validated['padre_id'] ?? 0) !== (int) ($animal->padre_id ?? 0);

        $cambiaPadreExterno = array_key_exists('padre_externo_id', $validated)
            && (int) ($validated['padre_externo_id'] ?? 0) !== (int) ($animal->padre_externo_id ?? 0);

        if ($cambiaMadre || $cambiaPadre || $cambiaPadreExterno) {
            $motivo = $tieneHistorialReproductivo
                ? 'ya tiene un parto histórico registrado'
                : 'se encuentra actualmente gestante';

            $errores = [];
            if ($cambiaMadre) {
                $errores['madre_id'] = "No se puede modificar la madre de este animal porque {$motivo}.";
            }
            if ($cambiaPadre || $cambiaPadreExterno) {
                $errores['padre_id'] = "No se puede modificar el padre de este animal porque {$motivo}.";
            }

            return back()->withErrors($errores)->withInput();
        }
    }

    // No permitir padre interno y donador externo al mismo tiempo
    if (
        !empty($validated['padre_id']) &&
        !empty($validated['padre_externo_id'])) 
        {  return back()
            ->withErrors([
                'padre_id' => 'Selecciona un padre interno o un donador externo, no ambos.', ])->withInput();}

// Evitar que sea su propio padre
    if (
        !empty($validated['padre_id']) &&
        (int) $validated['padre_id'] === $animal->id
    ) {return back()
            ->withErrors(['padre_id' => 'El animal no puede ser su propio padre.',])->withInput();}

    // ── Padre asignado sin madre ─────────────────────────────────────
    // Mismo caso que en store(): si tras esta actualización el animal
    // queda con padre interno pero sin madre, no existe historial
    // reproductivo formal donde validar la edad del padre, así que se
    // valida aquí directamente.
    $madreId = $validated['madre_id'] ?? $animal->madre_id;
    $fechaNacParaValidar = $validated['fecha_nac'] ?? $animal->fecha_nac;
    if (
        empty($madreId) &&
        !empty($validated['padre_id']) &&
        !empty($fechaNacParaValidar)
    ) {
        $fechaServicioEstimada = $historialNacimientoService->calcularFechaServicio(
            $validated['especie'] ?? $animal->especie,
            Carbon::parse($fechaNacParaValidar)
        );

        $historialNacimientoService->validarEdadPadre(
            Animal::findOrFail($validated['padre_id']),
            $fechaServicioEstimada
        );
    }

    // Validar arete duplicado
    $repite = Animal::where('id', '!=', $animal->id)
        ->where('arete', $validated['arete'])
        ->where(function ($q) use ($validated) {
            $q->where('raza', $validated['raza'])
              ->orWhere('lote_id', $validated['lote_id']);
        })
        ->exists();
    if ($repite) {
        return back()->withErrors([
            'arete' => 'Este arete ya está usado en esta raza o lote.',
        ]);
    }
    // No permitir modificar peso si ya existe historial
    if (
        array_key_exists('peso', $validated)
        && $animal->pesajes()->exists()
        && (
            $validated['peso'] === null ||
            round((float) $validated['peso'], 2) !== round((float) $animal->peso, 2)
        )
    ) {
        return back()->withErrors([
            'peso' => 'Cuando ya existe historial, el peso sólo puede cambiarse desde el módulo de Pesajes.',
        ])->withInput();
    }
    $animal->update($validated);
    return back()->with('success', 'Animal actualizado.');
}

   
public function destroy(Animal $animal)
    {
        if ($animal->estado_productivo === 'muerto') {
            return back()->withErrors([
                'animal' => 'Un animal registrado como muerto no puede eliminarse.',
            ]);
        }

        $animal->delete();
        return back()->with('success', 'Animal eliminado.');
    }

public function imagen(Request $request, Animal $animal)
{
    if ($animal->estado_productivo === 'muerto') {
        return back()->withErrors([
            'animal' => 'Un animal registrado como muerto no puede modificarse.',
        ]);
    }

    $request->validate([
        'imagen' => 'required|image|max:5120', // máx 5MB
    ]);

    // Borra la imagen anterior si existe
    if ($animal->imagen) {
        \Illuminate\Support\Facades\Storage::disk('public')->delete($animal->imagen);
    }

    $path = $request->file('imagen')->store('animales', 'public');

    $animal->update(['imagen' => $path]);

    return back()->with('success', 'Imagen actualizada.');
}

public function guardarImagen(Request $request, Animal $animal)
{
    if ($animal->estado_productivo === 'muerto') {
        return back()->withErrors([
            'animal' => 'Un animal registrado como muerto no puede modificarse.',
        ]);
    }

    $request->validate([
        'imagen' => 'required|image|max:5120',
    ]);

    $ruta = $request->file('imagen')->store('animales', 'public');

    $animal->update([
        'imagen' => $ruta,
    ]);

    return back();
}

public function eliminarImagen(Animal $animal)
{
    if ($animal->estado_productivo === 'muerto') {
        return back()->withErrors([
            'animal' => 'Un animal registrado como muerto no puede modificarse.',
        ]);
    }

    if ($animal->imagen) {
        \Illuminate\Support\Facades\Storage::disk('public')->delete($animal->imagen);
        $animal->update(['imagen' => null]);
    }

    return back()->with('success', 'Imagen eliminada.');
}
}