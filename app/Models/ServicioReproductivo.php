<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicioReproductivo extends Model
{
    protected $table = 'servicio_reproductivos';

    protected $fillable = [
        'evento_id',
        'macho_id',
        'tipo_servicio',
        'pajilla_codigo',
        'pajilla_raza',
        'pajilla_origen',
        'tecnico_id',
        'tecnico_externo',
        'numero_servicio',
    ];

    protected $casts = [
        'numero_servicio' => 'integer',
    ];

    // ─── Relaciones ───────────────────────────────────────────────────────

    public function evento(): BelongsTo
    {
        return $this->belongsTo(EventoReproductivo::class, 'evento_id');
    }

    public function macho(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'macho_id');
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    // ─── Accessors ────────────────────────────────────────────────────────

    // Devuelve el nombre del técnico sin importar si es usuario del sistema
    // o técnico externo
    public function getNombreTecnicoAttribute(): ?string
    {
        if ($this->tecnico) {
            return $this->tecnico->name;
        }
        return $this->tecnico_externo;
    }

    // Descripción legible del servicio para mostrar en la timeline
    public function getDescripcionAttribute(): string
    {
        $tipo = match($this->tipo_servicio) {
            'monta_natural'           => 'Monta natural',
            'inseminacion_artificial' => 'Inseminación artificial',
            'iatf'                    => 'IATF',
            default                   => $this->tipo_servicio,
        };

        if ($this->tipo_servicio === 'monta_natural' && $this->macho) {
            return "{$tipo} — Toro: {$this->macho->nombre}";
        }

        if ($this->pajilla_codigo) {
            return "{$tipo} — Pajilla: {$this->pajilla_codigo}";
        }

        return $tipo;
    }
}