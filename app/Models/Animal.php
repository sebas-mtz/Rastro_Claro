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

class Animal extends Model
{
    use HasFactory;

    protected $fillable = [
        'especie','alias','raza','arete','sexo','fecha_nac','peso','BCS','estado_productivo','lote_id',
        'madre_id', 'padre_id', 'padre_externo_id'
    ];

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
}