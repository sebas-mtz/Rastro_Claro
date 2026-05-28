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
        'tipo_parto',
        'asistencia_requerida',
        'complicaciones',
        'detalle_complicaciones',
        'numero_crias',
    ];

    protected $casts = [
        'asistencia_requerida' => 'boolean',
        'complicaciones'       => 'boolean',
        'numero_crias'         => 'integer',
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