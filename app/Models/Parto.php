<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parto extends Model
{
    protected $table = 'partos';

    protected $fillable = [
        'evento_id',
        'servicio_evento_id',
        'hora',
        'tipo_parto',
        'asistido',
        'asistencia_requerida',
        'complicaciones',
        'detalle_complicaciones',
        'numero_crias',
        'crias_vivas',
        'crias_muertas',
        'abortos',
        'costo_atencion',
        'veterinario_id',
        'responsable_id',
        'observaciones',
    ];

    protected $casts = [
        'asistido'             => 'boolean',
        'asistencia_requerida' => 'boolean',
        'complicaciones'       => 'boolean',
        'numero_crias'         => 'integer',
        'crias_vivas'          => 'integer',
        'crias_muertas'        => 'integer',
        'abortos'              => 'integer',
        'costo_atencion'       => 'decimal:2',
    ];

    // ─── Relaciones ───────────────────────────────────────────────────────

    public function evento(): BelongsTo
    {
        return $this->belongsTo(EventoReproductivo::class, 'evento_id');
    }

    public function eventoServicio(): BelongsTo
    {
        return $this->belongsTo(EventoReproductivo::class, 'servicio_evento_id');
    }

    // Todas las crías de este parto
    public function crias(): HasMany
    {
        return $this->hasMany(Cria::class, 'parto_id');
    }

    // Solo las crías que nacieron vivas
    public function criasVivas(): HasMany
    {
        return $this->hasMany(Cria::class, 'parto_id')
                    ->where('condicion', 'vivo');
    }

    // ─── Accessors ────────────────────────────────────────────────────────

    // Fecha del parto viene del evento padre
    public function getFechaAttribute()
    {
        return $this->evento?->fecha;
    }

    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinario_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /**
     * Prolificidad del parto: crías nacidas vivas. Es el insumo del promedio
     * de crías por parto que se reporta a nivel rebaño.
     */
    public function getProlificidadAttribute(): int
    {
        return (int) ($this->crias_vivas ?? $this->crias->where('condicion', 'vivo')->count());
    }

    public function getTipoPorHumanoAttribute(): string
    {
        return match($this->tipo_parto) {
            'normal'    => 'Normal',
            'distocico' => 'Distócico',
            'cesarea'   => 'Cesárea',
            default     => $this->tipo_parto,
        };
    }
}