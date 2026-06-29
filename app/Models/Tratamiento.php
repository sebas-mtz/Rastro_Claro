<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Tratamiento extends Model
{
    protected $fillable = [
        'animal_id',
        'lote_id',
        'salud_id',
        'nombre',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'notas',
        'responsable',
        'user_id',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    const ESTADO_ACTIVO     = 'activo';
    const ESTADO_COMPLETADO = 'completado';

    // ─── Relaciones ───────────────────────────────────────────────

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }

    public function eventoSalud(): BelongsTo
    {
        return $this->belongsTo(EventoSalud::class, 'salud_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_ACTIVO);
    }

    public function scopeVencidos(Builder $query): Builder
    {
        // Tratamientos activos cuya fecha_fin ya pasó
        return $query->where('estado', self::ESTADO_ACTIVO)
                     ->whereNotNull('fecha_fin')
                     ->where('fecha_fin', '<', Carbon::today());
    }

    public function scopeDeAnimal(Builder $query, int $animalId): Builder
    {
        return $query->where('animal_id', $animalId);
    }
    
    public function scopeDeLote(Builder $query, int $loteId): Builder
{
    return $query->where('lote_id', $loteId);
}

    // ─── Helpers ──────────────────────────────────────────────────

    public function marcarCompletado(): void
    {
        $this->update(['estado' => self::ESTADO_COMPLETADO]);
    }

    public function diasRestantes(): ?int
    {
        if (!$this->fecha_fin) return null;

        return max(0, Carbon::today()->diffInDays($this->fecha_fin, false));
    }

    public function estaVencido(): bool
    {
        return $this->estado === self::ESTADO_ACTIVO
            && $this->fecha_fin
            && $this->fecha_fin->isPast();
    }
}