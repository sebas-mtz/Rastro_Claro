<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EventoReproductivo extends Model
{
    protected $table = 'evento_reproductivos';

    protected $fillable = [
        'hembra_id',
        'lote_id',
        'user_id',
        'tipo_evento',
        'fecha',
        'costo',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'costo' => 'decimal:2',
    ];

    // ─── Relaciones hacia arriba ───────────────────────────────────────────

    public function hembra(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'hembra_id');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ─── Relaciones hacia abajo (tablas de detalle) ────────────────────────

    public function servicio(): HasOne
    {
        return $this->hasOne(ServicioReproductivo::class, 'evento_id');
    }

    public function diagnostico(): HasOne
    {
        return $this->hasOne(DiagnosticoGestacion::class, 'evento_id');
    }

    public function parto(): HasOne
    {
        return $this->hasOne(Parto::class, 'evento_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────

    public function scopeDeHembra($query, int $hembraId)
    {
        return $query->where('hembra_id', $hembraId);
    }

    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo_evento', $tipo);
    }

    public function scopeRecientes($query, int $dias = 90)
    {
        return $query->where('fecha', '>=', now()->subDays($dias));
    }
}