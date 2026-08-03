<?php
namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Lote;
use App\Models\Raza;
use App\Services\AnimalValuationService;
use App\Services\EstadoProductivoService;
use App\Services\EtapaVidaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\DonadorExterno;
use Illuminate\Support\Facades\DB;
class AnimalController extends Controller
{
    /**
     * Sistema exclusivamente ovino. Las razas ya no viven aquí: se administran
     * desde el catálogo configurable (tabla `razas`).
     */
    private array $especies = [Animal::ESPECIE];

    // Sin constructor — estadosProductivos viene directo del servicio cuando se necesita

    public function index()
    {
        return Inertia::render('Animals/Index', [
            'animales'           => Animal::with('lote')->get(),
            'lotes'              => Lote::all(),
            'especies'           => $this->especies,
            'razas'              => Raza::activa()->orderBy('nombre')->get(['id', 'nombre', 'aptitud']),
'estadosProductivos' => EstadoProductivoService::estadosManualesPorEspecie(), 
'donadoresExternos' => DonadorExterno::orderBy('nombre')->get(),
        ]);
    }

    /**
     * Reglas comunes de alta y edición del ejemplar ovino.
     */
    private function reglas(): array
    {
        return [
            'alias'              => 'nullable|string|max:255',
            'arete'              => 'required|string',
            'sexo'               => 'required|in:M,F',
            'fecha_nac'          => 'nullable|date|before_or_equal:today',
            'peso'               => 'nullable|numeric|min:0',
            'BCS'                => 'nullable|numeric|min:1|max:5',
            'estado_productivo'  => 'nullable|string',
            'etapa_vida'         => 'nullable|string|in:' . implode(',', array_keys(EtapaVidaService::ETIQUETAS)),
            'lote_id'            => 'nullable|exists:lotes,id',
            'raza_id'            => 'nullable|exists:razas,id',
            'raza_secundaria_id' => 'nullable|exists:razas,id|different:raza_id',
            'madre_id'           => 'nullable|exists:animals,id',
            'padre_id'           => 'nullable|exists:animals,id',
            'padre_externo_id'   => 'nullable|exists:donadores_externos,id',
            // Motivo del cambio de lote: no se persiste en `animals`, lo lee el
            // observer para dejarlo escrito en el historial de movimientos.
            'motivo_movimiento_lote' => 'nullable|string|max:255',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->reglas());

        // Este sistema es exclusivamente ovino: la especie no se elige.
        $validated['especie'] = Animal::ESPECIE;
        $validated['es_cruza'] = ! empty($validated['raza_secundaria_id']);

        if ($errores = $this->validarGenealogia($validated)) {
            return back()->withErrors($errores)->withInput();
        }

        if ($request->lote_id && Animal::where('lote_id', $request->lote_id)->where('arete', $request->arete)->exists()) {
            return back()->withErrors(['arete' => 'Ya existe un ejemplar con este arete en este mismo lote.'])->withInput();
        }

        if ($validated['etapa_vida'] ?? null) {
            $validated['etapa_vida_confirmada_at'] = now();
        }

        unset($validated['motivo_movimiento_lote']);

        Animal::create($validated);

        return back()->with('success', 'Ejemplar registrado exitosamente.');
    }

    /**
     * Validaciones de parentesco. Devuelve un arreglo de errores o null.
     *
     * Comprueba que la madre sea hembra, el padre macho, que nadie sea su
     * propio ascendiente, que los padres hayan nacido antes que la cría y que
     * no se formen ciclos en el árbol genealógico.
     */
    private function validarGenealogia(array $datos, ?Animal $animal = null): ?array
    {
        $errores = [];

        $madreId = $datos['madre_id'] ?? null;
        $padreId = $datos['padre_id'] ?? null;

        if (! empty($padreId) && ! empty($datos['padre_externo_id'])) {
            $errores['padre_id'] = 'Selecciona un padre interno o un donador externo, no ambos.';
        }

        // Nadie puede ser su propio padre o madre
        if ($animal) {
            if ((int) $madreId === $animal->id) {
                $errores['madre_id'] = 'El ejemplar no puede ser su propia madre.';
            }

            if ((int) $padreId === $animal->id) {
                $errores['padre_id'] = 'El ejemplar no puede ser su propio padre.';
            }
        }

        $nacimientoCria = ! empty($datos['fecha_nac']) ? \Carbon\Carbon::parse($datos['fecha_nac']) : null;

        foreach ([['madre_id', $madreId, 'F', 'madre'], ['padre_id', $padreId, 'M', 'padre']] as [$campo, $id, $sexoEsperado, $etiqueta]) {
            if (empty($id) || isset($errores[$campo])) {
                continue;
            }

            $progenitor = Animal::find($id);

            if (! $progenitor) {
                continue;   // la regla `exists` ya lo reporta
            }

            if ($progenitor->sexo !== $sexoEsperado) {
                $errores[$campo] = $sexoEsperado === 'F'
                    ? 'La madre debe ser una hembra.'
                    : 'El padre debe ser un macho.';

                continue;
            }

            $nacimientoProgenitor = AnimalValuationService::fechaNacimiento($progenitor);

            if ($nacimientoCria && $nacimientoProgenitor && $nacimientoProgenitor->gte($nacimientoCria)) {
                $errores[$campo] = "La fecha de nacimiento de la {$etiqueta} debe ser anterior a la del ejemplar.";

                continue;
            }

            // Un descendiente no puede convertirse en ascendiente
            if ($animal && $this->esDescendiente($progenitor, $animal->id)) {
                $errores[$campo] = "No se puede asignar como {$etiqueta}: ese ejemplar desciende de este, se crearía un ciclo en la genealogía.";
            }
        }

        return $errores ?: null;
    }

    /**
     * ¿$posibleDescendiente desciende de $ancestroId? Recorre la línea materna
     * y paterna con un límite de profundidad para no ciclar indefinidamente.
     */
    private function esDescendiente(Animal $posibleDescendiente, int $ancestroId, int $profundidad = 0): bool
    {
        if ($profundidad > 10) {
            return false;
        }

        foreach ([$posibleDescendiente->madre_id, $posibleDescendiente->padre_id] as $progenitorId) {
            if (! $progenitorId) {
                continue;
            }

            if ((int) $progenitorId === $ancestroId) {
                return true;
            }

            $progenitor = Animal::find($progenitorId);

            if ($progenitor && $this->esDescendiente($progenitor, $ancestroId, $profundidad + 1)) {
                return true;
            }
        }

        return false;
    }

    public function show(Animal $animal)
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
        ]);

        return Inertia::render('Animals/ShowAnimal', [
            'animal'             => $animal,
            'movimientosLote'    => $animal->movimientosLote()
                ->with(['loteAnterior:id,nombre', 'loteNuevo:id,nombre', 'responsable:id,name'])
                ->get(),
            'condicionesCorporales' => $animal->condicionesCorporales()->limit(10)->get(),
            'etapaSugerida'      => app(EtapaVidaService::class)->sugerir($animal),
            'etapasVida'         => EtapaVidaService::ETIQUETAS,
            'desarrolloCorporal' => app(\App\Services\DesarrolloCorporalService::class)->resumen($animal),
            'documentos'         => $animal->documentos()->get()->map(fn ($d) => [
                'id' => $d->id,
                'nombre' => $d->nombre,
                'tipo' => $d->tipo,
                'fecha_documento' => $d->fecha_documento?->toDateString(),
                'observaciones' => $d->observaciones,
                'tamano_legible' => $d->tamano_legible,
                'es_imagen' => $d->es_imagen,
            ]),
            'tiposDocumento'     => \App\Models\Documento::TIPOS,
            'extensionesDocumento' => \App\Models\Documento::EXTENSIONES,
            'tamanoMaximoKb'     => \App\Models\Documento::TAMANO_MAXIMO_KB,
            'lotes'              => Lote::all(),
            'especies'           => $this->especies,
            'razas'              => Raza::activa()->orderBy('nombre')->get(['id', 'nombre', 'aptitud']),
'estadosProductivos' => EstadoProductivoService::estadosManualesPorEspecie(), 
        ]);
    }

    public function edit(Animal $animal)
    {
        return Inertia::render('Animals/Edit', [
            'animal'             => $animal,
            'lotes'              => Lote::all(),
            'especies'           => $this->especies,
            'razas'              => Raza::activa()->orderBy('nombre')->get(['id', 'nombre', 'aptitud']),
'estadosProductivos' => EstadoProductivoService::estadosManualesPorEspecie(), 
'posiblesPadres' => Animal::where('sexo', 'M')
    ->where('id', '!=', $animal->id)
    ->orderBy('arete')
    ->get(),

'donadoresExternos' => DonadorExterno::orderBy('nombre')->get(),
       ]);
    }

    public function update(Request $request, Animal $animal)
    {
        $validated = $request->validate($this->reglas());

        $validated['especie'] = Animal::ESPECIE;
        $validated['es_cruza'] = ! empty($validated['raza_secundaria_id']);

        if ($errores = $this->validarGenealogia($validated, $animal)) {
            return back()->withErrors($errores)->withInput();
        }

        $repite = Animal::where('id', '!=', $animal->id)
            ->where('arete', $request->arete)
            ->where('lote_id', $request->lote_id)
            ->exists();

        if ($repite) {
            return back()->withErrors(['arete' => 'Este arete ya está usado en este mismo lote.'])->withInput();
        }

        // La etapa de vida solo se marca como confirmada cuando cambia,
        // porque el sistema nunca la fija por su cuenta.
        if (($validated['etapa_vida'] ?? null) && $validated['etapa_vida'] !== $animal->etapa_vida) {
            $validated['etapa_vida_confirmada_at'] = now();
        }

        // El observer de movimientos lee esta propiedad al detectar el cambio
        // de lote; no forma parte de las columnas de `animals`.
        $animal->motivoMovimientoLote = $validated['motivo_movimiento_lote'] ?? null;
        unset($validated['motivo_movimiento_lote']);

        $animal->update($validated);

        return back()->with('success', 'Ejemplar actualizado.');
    }

    public function destroy(Animal $animal)
    {
        $animal->delete();
        return back()->with('success', 'Animal eliminado.');
    }

    public function imagen(Request $request, Animal $animal)
{
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
    if ($animal->imagen) {
        \Illuminate\Support\Facades\Storage::disk('public')->delete($animal->imagen);
        $animal->update(['imagen' => null]);
    }

    return back()->with('success', 'Imagen eliminada.');
}

/**
 * Búsqueda unificada por arete, alias, ID interno, microchip/RFID o token de QR.
 * Respeta el scope de tenencia automático del modelo Animal (owner_id).
 */
public function buscarPorIdentificador(Request $request)
{
    $validated = $request->validate([
        'codigo' => 'required|string|max:255',
    ]);

    $codigo = Animal::normalizarIdentificador($validated['codigo']);

    if ($codigo === '') {
        return response()->json([
            'encontrado' => false,
            'message' => 'El identificador no puede estar vacío.',
        ], 422);
    }

    $animal = Animal::query()
        ->where(function ($q) use ($codigo) {
            $q->whereRaw('UPPER(arete) = ?', [$codigo])
                ->orWhereRaw('UPPER(alias) = ?', [$codigo])
                ->orWhere('microchip_codigo', $codigo)
                ->orWhere('qr_token', $codigo);

            if (ctype_digit($codigo)) {
                $q->orWhere('id', (int) $codigo);
            }
        })
        ->first(['id', 'especie', 'alias', 'arete', 'microchip_codigo']);

    if (! $animal) {
        return response()->json(['encontrado' => false]);
    }

    return response()->json([
        'encontrado' => true,
        'animal' => $animal,
    ]);
}

/**
 * Registra o actualiza el identificador (microchip/RFID/QR/arete/manual) de un animal.
 * Rechaza códigos de microchip ya asignados a otro animal.
 */
public function registrarIdentificador(Request $request, Animal $animal)
{
    $validated = $request->validate([
        'tipo_identificador' => 'required|string|in:' . implode(',', Animal::TIPOS_IDENTIFICADOR),
        'microchip_codigo' => 'nullable|string|max:100',
        'fecha_colocacion_microchip' => 'nullable|date',
        'estado_microchip' => 'nullable|string|in:' . implode(',', Animal::ESTADOS_MICROCHIP),
        'observaciones_microchip' => 'nullable|string|max:1000',
    ]);

    if (! empty($validated['microchip_codigo'])) {
        $codigoNormalizado = Animal::normalizarIdentificador($validated['microchip_codigo']);

        $yaAsignado = Animal::where('microchip_codigo', $codigoNormalizado)
            ->where('id', '!=', $animal->id)
            ->exists();

        if ($yaAsignado) {
            return back()->withErrors([
                'microchip_codigo' => 'Este identificador ya está asignado a otro animal.',
            ])->withInput();
        }
    }

    DB::transaction(function () use ($animal, $validated) {
        $animal->update($validated);
    });

    return back()->with('success', 'Identificador registrado correctamente.');
}

/**
 * Devuelve (generando si hace falta) el token de QR del animal y la URL de escaneo.
 */
public function qr(Animal $animal)
{
    $token = $animal->asegurarQrToken();

    return response()->json([
        'token' => $token,
        'url' => route('animales.escanear', $token),
    ]);
}

/**
 * Punto de entrada al escanear el QR físico: resuelve el token y redirige
 * a la ficha del animal. El scope de tenencia hace que un QR de otro dueño
 * simplemente no se encuentre, sin filtrar datos.
 */
public function escanearQr(string $token)
{
    $animal = Animal::where('qr_token', $token)->first();

    if (! $animal) {
        abort(404, 'Identificador QR no encontrado.');
    }

    // No se usa route('animales.show', ...) a propósito: routes/api.php (capa móvil
    // sin comitear, fuera de alcance) registra Route::apiResource('animales', ...),
    // que reutiliza el mismo nombre "animales.show" y puede ganarle a esta ruta web
    // según el orden de registro. La ruta /animales/{id} en sí es estable.
    return redirect('/animales/' . $animal->id);
}
}