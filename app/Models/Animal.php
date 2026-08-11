<?php

namespace App\Models;
use App\Models\EventoReproductivo;
use App\Models\ServicioReproductivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class Animal extends Model
{
    use HasFactory;
protected $fillable = [
    'especie','alias', 'raza', 'arete', 'sexo','fecha_nac','peso','BCS','estado_productivo','lote_id','madre_id','padre_id','padre_externo_id','imagen', 'siniiga_id',
    'identificador',
    'numero_registro',
    'grado_pureza',
    'lectura_microchip',
    'color',
    ];

protected $casts = [
    'fecha_nac' => 'date',
    'peso' => 'float',
];

protected static function booted(): void
{
    static::updating(function (Animal $animal) {

if (strcasecmp((string) $animal->getOriginal('estado_productivo'), 'muerto') === 0) {
                throw ValidationException::withMessages([
                'animal' => 'Un animal dado de baja no puede modificarse.',
            ]);
        }

    });
}
    public function lote() {
        return $this->belongsTo(Lote::class);
    }

    public function muerte(): HasOne
    {
        return $this->hasOne(Muerte::class);
    }

    public function salud() {
        return $this->hasMany(EventoSalud::class);
    }

    public function producciones() {
        return $this->hasMany(Produccion::class);
    }

    public function alimentaciones() {
        return $this->hasMany(Alimentacion::class);
    }

    
    public function ventas(): MorphMany
    {
        return $this->morphMany(Venta::class, 'vendible');
    }

    /**
     * Verificar si el animal está vendido
     */
    public function getEstaVendidoAttribute(): bool
    {
        return $this->ventas()
            ->where('tipo_venta', 'animal')
            ->where('estado_venta', 'completada')
            ->exists();
    }
    
    public function eventosReproductivos(): HasMany
    {
        return $this->hasMany(EventoReproductivo::class, 'hembra_id')
                    ->orderBy('fecha', 'desc');
    }
 
    // Servicios en los que este animal participó como Semental
    public function serviciosComoMacho(): HasMany
    {
        return $this->hasMany(ServicioReproductivo::class, 'macho_id');
    }
 
    // El evento reproductivo más reciente de esta hembra
    public function ultimoEventoReproductivo(): HasOne
    {
        return $this->hasOne(EventoReproductivo::class, 'hembra_id')
                    ->latestOfMany('fecha');
    }
 
    // ── Accessor: estado reproductivo actual ──────────────────────────────
    //
    // Calcula el estado de la vaca en tiempo real desde sus eventos.
    // Requiere que el campo sexo sea 'hembra' para aplicar.
    //
    // Estados devueltos:
    //   no_aplica         → el animal es macho
    //   vacia             → sin eventos o último diagnóstico negativo
    //   servida           → servicio reciente sin diagnóstico todavía
    //   gestante          → diagnóstico positivo sin parto posterior
    //   proxima_a_parir   → gestante con parto probable en menos de 21 días
    //   parida            → parto hace menos de 45 días
    //   anestro_postparto → parto hace entre 45 y 90 días
    //   vacia_disponible  → parto hace más de 90 días sin nuevo servicio
    //
    public function getEstadoReproductivoAttribute(): string
    {
        if (!$this->esHembra()) {
            return 'no_aplica';
        }
 
        // ── Paso 1: revisar si hay parto reciente ─────────────────────────
        $ultimoParto = EventoReproductivo::where('hembra_id', $this->id)
            ->where('tipo_evento', 'parto')
            ->latest('fecha')
            ->first();
 
        if ($ultimoParto) {
            $diasDesdeParto = $ultimoParto->fecha->diffInDays(now());
 
            $nuevoServicioPostParto = EventoReproductivo::where('hembra_id', $this->id)
                ->where('tipo_evento', 'servicio')
                ->where('fecha', '>', $ultimoParto->fecha)
                ->exists();
 
            if (!$nuevoServicioPostParto) {
                if ($diasDesdeParto < 45)  return 'parida';
                if ($diasDesdeParto < 90)  return 'anestro_postparto';
                return 'vacia_disponible';
            }
        }
 
        // ── Paso 2: revisar último diagnóstico ────────────────────────────
        $ultimoDiagnostico = EventoReproductivo::where('hembra_id', $this->id)
            ->where('tipo_evento', 'diagnostico')
            ->latest('fecha')
            ->with('diagnostico')
            ->first();
 
        if ($ultimoDiagnostico?->diagnostico?->isPositivo()) {
 
            $partoPostDiagnostico = EventoReproductivo::where('hembra_id', $this->id)
                ->where('tipo_evento', 'parto')
                ->where('fecha', '>', $ultimoDiagnostico->fecha)
                ->exists();
 
            if (!$partoPostDiagnostico) {
                $fechaProbable = $ultimoDiagnostico->diagnostico->fecha_probable_parto;
 
                if ($fechaProbable && $fechaProbable->diffInDays(now(), false) >= -21) {
                    return 'proxima_a_parir';
                }
                return 'gestante';
            }
        }
 
        // ── Paso 3: revisar si hay servicio sin diagnóstico ───────────────
        $ultimoServicio = EventoReproductivo::where('hembra_id', $this->id)
            ->where('tipo_evento', 'servicio')
            ->latest('fecha')
            ->first();
 
        if ($ultimoServicio) {
            $diagnosticoPosterior = EventoReproductivo::where('hembra_id', $this->id)
                ->where('tipo_evento', 'diagnostico')
                ->where('fecha', '>', $ultimoServicio->fecha)
                ->exists();
 
            if (!$diagnosticoPosterior) {
                return 'servida';
            }
        }
 
        return 'vacia';
    }
 
    // ── Accessor: días abiertos ───────────────────────────────────────────
    //
    // Días desde el último parto hasta el próximo diagnóstico positivo.
    // Si aún no hay diagnóstico positivo posterior, cuenta hasta hoy.
    // Devuelve null si la vaca nunca ha parido.
    //
    public function getDiasAbiertosAttribute(): ?int
    {
        if (!$this->esHembra()) {
            return null;
        }
 
        $ultimoParto = EventoReproductivo::where('hembra_id', $this->id)
            ->where('tipo_evento', 'parto')
            ->latest('fecha')
            ->first();
 
        if (!$ultimoParto) {
            return null;
        }
 
        // Primer diagnóstico positivo después del parto
        $proximoConfirmado = EventoReproductivo::where('hembra_id', $this->id)
            ->where('tipo_evento', 'diagnostico')
            ->where('fecha', '>', $ultimoParto->fecha)
            ->with('diagnostico')
            ->get()
            ->first(fn($e) => $e->diagnostico?->isPositivo());
 
        if ($proximoConfirmado) {
            return $ultimoParto->fecha->diffInDays($proximoConfirmado->fecha);
        }
 
        // Sin diagnóstico positivo aún — días abiertos hasta hoy
        return $ultimoParto->fecha->diffInDays(now());
    }
 
    // ── Accessor: resumen reproductivo para la ficha del animal ──────────
 
    public function getResumenReproductivoAttribute(): array
    {
        if (!$this->esHembra()) {
            return [];
        }
 
        $totalPartos = EventoReproductivo::where('hembra_id', $this->id)
            ->where('tipo_evento', 'parto')
            ->count();
 
        $totalServicios = EventoReproductivo::where('hembra_id', $this->id)
            ->where('tipo_evento', 'servicio')
            ->count();
 
        $diagnosticosPositivos = EventoReproductivo::where('hembra_id', $this->id)
            ->where('tipo_evento', 'diagnostico')
            ->with('diagnostico')
            ->get()
            ->filter(fn($e) => $e->diagnostico?->isPositivo())
            ->count();
 
        $tasaConcepcion = $totalServicios > 0
            ? round(($diagnosticosPositivos / $totalServicios) * 100, 1)
            : null;
 
        return [
            'total_partos'        => $totalPartos,
            'total_servicios'     => $totalServicios,
            'tasa_concepcion'     => $tasaConcepcion,
            'dias_abiertos'       => $this->dias_abiertos,
            'estado_reproductivo' => $this->estado_reproductivo,
        ];
    }

    public function esHembra(): bool
{
    return strtoupper((string) $this->sexo) === 'H';
}
public function madre(): BelongsTo
{
    return $this->belongsTo(Animal::class, 'madre_id');
}

public function padre(): BelongsTo
{
    return $this->belongsTo(Animal::class, 'padre_id');
}

public function crias(): HasMany
{
    return $this->hasMany(Animal::class, 'madre_id');
}

public function criasComopadre(): HasMany
{
    return $this->hasMany(Animal::class, 'padre_id');
}
    public function pesajes()
{
    return $this->hasMany(Pesaje::class);
}
public function padreExterno(): BelongsTo
{
    return $this->belongsTo(DonadorExterno::class, 'padre_externo_id');
}

public function getPadreGenealogicoAttribute()
{
    return $this->padre ?? $this->padreExterno;
}
public function criaOrigen(): HasOne
{
    return $this->hasOne(Cria::class, 'animal_id');
}

public function getTipoPartoOrigenAttribute(): ?string
{
    return $this->criaOrigen?->parto?->tipo_parto;
}

public function esAptoParaReproduccion(?Carbon $fechaEvento = null, int $mesesMinimos = 7): bool
{
    // Si no tiene fecha de nacimiento registrada en la columna fecha_nac
    if (!$this->fecha_nac) {
        return true; 
    }

    $fecha = $fechaEvento ?? today();

    // Calcula la diferencia en meses usando $this->fecha_nac
    return $this->fecha_nac->diffInMonths($fecha, false) >= $mesesMinimos;
}

public function ultimoParto(?Carbon $fecha = null): ?EventoReproductivo
{
    $fecha = $fecha ?? Carbon::today();

    return EventoReproductivo::where('hembra_id', $this->id)
        ->where('tipo_evento', 'parto')
        ->whereDate('fecha', '<=', $fecha)
        ->latest('fecha')
        ->first();
}

/**
 * Valida si la hembra está apta para recibir un servicio reproductivo.
 * Retorna [bool $apto, ?string $mensajeError]
 */
public function puedeRecibirServicio(?Carbon $fecha = null): array
{
    $fecha = $fecha ?? Carbon::today();

    if (!$this->esHembra()) {
        return [false, "El animal '{$this->alias}' no es una hembra."];
    }

    if (!$this->esAptoParaReproduccion($fecha)) {
        return [false, "La hembra '{$this->alias}' no cumple con la edad mínima requerida."];
    }

    // Comparación insensible a mayúsculas/minúsculas: el catálogo de
    // estados productivos puede guardar "Gestante", "gestante", etc.
    if (strcasecmp((string) $this->estado_productivo, 'gestante') === 0) {
        return [false, "La hembra '{$this->alias}' ya se encuentra en gestación."];
    }

    $ultimoParto = $this->ultimoParto($fecha);
    if ($ultimoParto) {
        $diasDesdeParto = Carbon::parse($ultimoParto->fecha)->diffInDays($fecha);
        // Comparación insensible a mayúsculas/minúsculas: la especie se
        // guarda como "Bovino" (con mayúscula) en el catálogo del CRUD.
        $diasMinimos = 21;

        if ($diasDesdeParto < $diasMinimos) {
            return [false, "La hembra '{$this->alias}' tuvo un parto hace solo {$diasDesdeParto} días. Requiere al menos {$diasMinimos} días de descanso post-parto."];
        }
    }

    return [true, null];
}

/**
 * Valida si la hembra puede registrar un parto en la fecha dada.
 * Retorna [bool $apto, ?string $mensajeError]
 */
public function puedeRegistrarParto(?Carbon $fecha = null): array
{
    $fecha = $fecha ?? Carbon::today();

    if (!$this->esHembra()) {
        return [false, "El animal seleccionado no es una hembra."];
    }

    if (!$this->esAptoParaReproduccion($fecha)) {
        return [false, "La madre '{$this->alias}' no cumple con la edad mínima requerida."];
    }

    $ultimoParto = $this->ultimoParto($fecha);
    if ($ultimoParto) {
        $diasDesdeParto = Carbon::parse($ultimoParto->fecha)->diffInDays($fecha);
        // Comparación insensible a mayúsculas/minúsculas: ver nota en
        // puedeRecibirServicio().
        $intervaloMinimo = 177;

        if ($diasDesdeParto < $intervaloMinimo) {
            return [false, "La hembra '{$this->alias}' ya registró un parto hace {$diasDesdeParto} días. El intervalo mínimo biológico entre partos es de {$intervaloMinimo} días."];
        }
    }

    return [true, null];
}
}