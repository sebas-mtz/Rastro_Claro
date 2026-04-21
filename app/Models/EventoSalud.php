<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class EventoSalud extends Model
{
    protected $table = 'eventos_salud';

    protected $fillable = [
        'animal_id',
        'tipo',
        'fecha_programada',
        'fecha_aplicacion',
        'diagnostico',
        'tratamiento',
        'vacuna_id',
        'dosis',
        'lote_vacuna',
        'observaciones',
        'estado',
        'responsable',
        'user_id',
    ];

    protected $casts = [
        'fecha_programada' => 'date',
        'fecha_aplicacion' => 'date',
    ];

    // Estados posibles como constantes para no usar strings mágicos en todo el código
    const ESTADO_PENDIENTE  = 'pendiente';
    const ESTADO_APLICADA   = 'aplicada';
    const ESTADO_VENCIDA    = 'vencida';

    const TIPO_CONSULTA     = 'consulta';
    const TIPO_VACUNACION   = 'vacunacion';
    const TIPO_REVISION     = 'revision';
    const TIPO_EMERGENCIA   = 'emergencia';

    // ─── Relaciones ───────────────────────────────────────────────

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function vacuna(): BelongsTo
    {
        return $this->belongsTo(Vacuna::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tratamientos(): HasMany
    {
        return $this->hasMany(Tratamiento::class, 'salud_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeVencidas(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_VENCIDA);
    }

    public function scopeDeAnimal(Builder $query, int $animalId): Builder
    {
        return $query->where('animal_id', $animalId);
    }

    public function scopeVacunaciones(Builder $query): Builder
    {
        return $query->where('tipo', self::TIPO_VACUNACION);
    }

    public function scopeProximas(Builder $query, int $dias = 7): Builder
    {
        return $query->where('estado', self::ESTADO_PENDIENTE)
                     ->whereBetween('fecha_programada', [
                         Carbon::today(),
                         Carbon::today()->addDays($dias),
                     ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    /**
     * Marca el evento como aplicado y guarda la fecha real de aplicación.
     */
    public function marcarAplicada(?Carbon $fechaAplicacion = null): void
    {
        $this->update([
            'estado'           => self::ESTADO_APLICADA,
            'fecha_aplicacion' => $fechaAplicacion ?? Carbon::today(),
        ]);
    }

    /**
     * Determina si la fecha programada ya pasó y el evento sigue pendiente.
     */
    public function estaVencida(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE
            && $this->fecha_programada->isPast();
    }
}