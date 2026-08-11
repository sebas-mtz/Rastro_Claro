<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Parto extends Model
{
    protected $appends = [ 'tipo_nacimiento', ];
    protected $table = 'partos';

    protected $fillable = [
        'evento_id',
        'servicio_evento_id',
        'tipo_parto',
        'asistencia_requerida',
        'complicaciones',
        'detalle_complicaciones',
        'numero_crias',
        'salio_leche',
        'observaciones_leche',
        'facilidad_materna',
        'observaciones_maternas',
    ];

    protected $casts = [
        'asistencia_requerida' => 'boolean',
        'complicaciones'       => 'boolean',
        'numero_crias'         => 'integer',
        'salio_leche'          => 'boolean',
        'facilidad_materna'    => 'boolean',
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

    public function destete(): HasOne
    {
        return $this->hasOne(Destete::class);
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
    public function getTipoNacimientoAttribute(): string
{
    return match ((int) $this->numero_crias) {
        1 => 'Simple',
        2 => 'Gemelar',
        3 => 'Triple',
        4 => 'Cuádruple',
        5 => 'Quíntuple',
        default => "{$this->numero_crias} crías",
    };
}
}
