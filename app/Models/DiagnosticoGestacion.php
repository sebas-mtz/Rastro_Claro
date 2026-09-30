<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiagnosticoGestacion extends Model
{
    protected $table = 'diagnostico_gestacions';

    protected $fillable = [
        'evento_id',
        'servicio_evento_id',
        'metodo',
        'resultado',
        'dias_gestacion_estimados',
        'fecha_probable_parto',
        'veterinario_id',
        'veterinario_externo',
    ];

    protected $casts = [
        'fecha_probable_parto'      => 'date',
        'dias_gestacion_estimados'  => 'integer',
    ];

    // ─── Relaciones ───────────────────────────────────────────────────────

    public function evento(): BelongsTo
    {
        return $this->belongsTo(EventoReproductivo::class, 'evento_id');
    }

    // El servicio al que responde este diagnóstico
    public function eventoServicio(): BelongsTo
    {
        return $this->belongsTo(EventoReproductivo::class, 'servicio_evento_id');
    }

    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'veterinario_id');
    }

    // ─── Accessors ────────────────────────────────────────────────────────

    public function getNombreVeterinarioAttribute(): ?string
    {
        if ($this->veterinario) {
            return $this->veterinario->name;
        }
        return $this->veterinario_externo;
    }

    public function isPositivo(): bool
    {
        return $this->resultado === 'positivo';
    }

    public function isNegativo(): bool
    {
        return $this->resultado === 'negativo';
    }
}