<?php

namespace App\Models;
use App\Models\EventoReproductivo;
use App\Models\ServicioReproductivo;
use App\Support\CodigoIso11784;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Animal extends Model
{
    use HasFactory;

    public const TIPOS_IDENTIFICADOR = ['microchip', 'rfid', 'qr', 'arete', 'manual'];
    public const ESTADOS_MICROCHIP = ['activo', 'inactivo', 'perdido', 'dañado'];

    /**
     * Formas de transmisión que define la norma ISO 11785.
     *
     * Media duplicidad (HDX) y plena duplicidad (FDX-B) son dos maneras de que
     * el arete le conteste al lector. La diferencia la resuelve el LECTOR: con
     * cualquiera de las dos llega el mismo código ISO 11784.
     *
     * Se guarda porque en campo sí importa: un lector que solo lee FDX-B no
     * detecta un arete HDX, y saber qué trae cada ejemplar evita pensar que el
     * animal se perdió cuando lo que falla es el equipo.
     */
    public const TECNOLOGIA_HDX = 'HDX';
    public const TECNOLOGIA_FDX_B = 'FDX-B';

    public const TECNOLOGIAS_RFID = [
        self::TECNOLOGIA_HDX => 'HDX — media duplicidad',
        self::TECNOLOGIA_FDX_B => 'FDX-B — plena duplicidad',
    ];

    protected $fillable = [
        'especie','alias','raza','arete','sexo','fecha_nac','peso','BCS','estado_productivo','lote_id',
        'madre_id', 'padre_id', 'padre_externo_id', 'imagen',
        'microchip_codigo', 'tipo_identificador', 'fecha_colocacion_microchip',
        'estado_microchip', 'observaciones_microchip', 'qr_token',
        'siniiga_numero', 'tecnologia_rfid', 'pais_codigo',
        'tipo_origen', 'fecha_adquisicion', 'proveedor_origen',
        'raza_id', 'raza_secundaria_id', 'es_cruza', 'raza_original',
        'etapa_vida', 'etapa_vida_confirmada_at',
        'activo', 'fecha_baja',
    ];

    /** Este sistema es exclusivamente ovino. */
    public const ESPECIE = 'Ovino';

    /**
     * Motivo del cambio de lote, que MovimientoLoteObserver escribe en el
     * historial de movimientos.
     *
     * Es una propiedad real de PHP, no un atributo de Eloquent: si se asignara
     * con $animal->motivo = '...' entraría al arreglo de atributos y el save()
     * intentaría guardarla como columna inexistente.
     */
    public ?string $motivoMovimientoLote = null;

    protected $casts = [
        'fecha_colocacion_microchip' => 'date',
        'fecha_adquisicion'          => 'date',
        'es_cruza'                   => 'boolean',
        'etapa_vida_confirmada_at'   => 'datetime',
        'activo'                     => 'boolean',
        'fecha_baja'                 => 'date',
    ];

    public const ORIGEN_NACIDO = 'nacido';
    public const ORIGEN_COMPRADO = 'comprado';
    public const ORIGEN_DESCONOCIDO = 'desconocido';

    public const TIPOS_ORIGEN = [
        self::ORIGEN_NACIDO,
        self::ORIGEN_COMPRADO,
        self::ORIGEN_DESCONOCIDO,
    ];

    /**
     * Normaliza el código de microchip/RFID: quita espacios, saltos de línea
     * y lo uniformiza en mayúsculas para evitar duplicados por formato.
     */
    public function setMicrochipCodigoAttribute(?string $value): void
    {
        $this->attributes['microchip_codigo'] = $value !== null && trim($value) !== ''
            ? strtoupper(preg_replace('/\s+/', '', trim($value)))
            : null;
    }

    /**
     * El número del arete visual oficial: solo dígitos, sin separadores.
     */
    public function setSiniigaNumeroAttribute(?string $value): void
    {
        $digitos = preg_replace('/\D+/', '', (string) $value);

        $this->attributes['siniiga_numero'] = $digitos !== '' ? $digitos : null;
    }

    /**
     * Normaliza cualquier identificador libre (arete/alias/microchip) recibido
     * desde un lector o input de búsqueda antes de compararlo en BD.
     */
    public static function normalizarIdentificador(string $valor): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($valor)));
    }

    /**
     * Lectura del código electrónico, o null si el ejemplar no tiene uno o el
     * guardado no cumple la estructura de la norma.
     */
    public function codigoIso(): ?CodigoIso11784
    {
        return CodigoIso11784::desde($this->microchip_codigo);
    }

    /** El código como se imprime en el arete: «484 000123456789». */
    public function getCodigoIsoFormateadoAttribute(): ?string
    {
        return $this->codigoIso()?->formateado();
    }

    public function getTecnologiaLegibleAttribute(): ?string
    {
        return $this->tecnologia_rfid
            ? (self::TECNOLOGIAS_RFID[$this->tecnologia_rfid] ?? $this->tecnologia_rfid)
            : null;
    }

    public function asegurarQrToken(): string
    {
        if (! $this->qr_token) {
            $this->qr_token = (string) Str::random(32);
            $this->save();
        }

        return $this->qr_token;
    }

    public function lote() {
        return $this->belongsTo(Lote::class);
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
        if ($this->sexo !== 'hembra') {
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
        if ($this->sexo !== 'hembra') {
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
        if ($this->sexo !== 'hembra') {
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
    // app/Models/Animal.php

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

// ── Valuación y cotización ────────────────────────────────────────────

/**
 * Registro de cría del que proviene este animal, si nació dentro de la
 * unidad productiva. Es la puerta de entrada a los costos de gestación:
 * cria → parto → evento reproductivo → madre.
 */
public function cria(): HasOne
{
    return $this->hasOne(Cria::class, 'animal_id');
}

public function genetica(): HasOne
{
    return $this->hasOne(AnimalGenetica::class);
}

public function valuaciones(): HasMany
{
    return $this->hasMany(AnimalValuation::class)->orderByDesc('created_at');
}

public function valuacionActiva(): HasOne
{
    return $this->hasOne(AnimalValuation::class)
                ->where('estado', AnimalValuation::ESTADO_ACTIVA)
                ->latestOfMany();
}

public function tratamientos(): HasMany
{
    return $this->hasMany(Tratamiento::class);
}

// ── Raza (catálogo ovino) ─────────────────────────────────────────────

public function razaPrincipal(): BelongsTo
{
    return $this->belongsTo(Raza::class, 'raza_id');
}

public function razaSecundaria(): BelongsTo
{
    return $this->belongsTo(Raza::class, 'raza_secundaria_id');
}

// ── Permanencia en el rebaño ──────────────────────────────────────────

public function bajas(): HasMany
{
    return $this->hasMany(Baja::class)->orderByDesc('fecha');
}

/** La baja vigente del ejemplar, si ya salió del rebaño. */
public function baja(): HasOne
{
    return $this->hasOne(Baja::class)->latestOfMany('fecha');
}

public function movimientosLote(): HasMany
{
    return $this->hasMany(MovimientoLote::class)->orderByDesc('fecha');
}

public function condicionesCorporales(): HasMany
{
    return $this->hasMany(CondicionCorporal::class)->orderByDesc('fecha');
}

public function documentos(): \Illuminate\Database\Eloquent\Relations\MorphMany
{
    return $this->morphMany(Documento::class, 'documentable')->orderByDesc('created_at');
}

/** Solo ejemplares que siguen en el rebaño. */
public function scopeActivo($query)
{
    return $query->where('activo', true);
}

public function scopeDadoDeBaja($query)
{
    return $query->where('activo', false);
}

/**
 * Raza legible: "Pelibuey x Dorper" para cruzas, el nombre simple si es pura.
 * Cae al texto original capturado cuando el catálogo aún no está asignado.
 */
public function getRazaDescriptivaAttribute(): string
{
    $principal = $this->razaPrincipal?->nombre;

    if (! $principal) {
        return $this->raza_original ?? $this->raza ?? 'Sin raza';
    }

    $secundaria = $this->razaSecundaria?->nombre;

    return $secundaria ? "{$principal} x {$secundaria}" : $principal;
}
}