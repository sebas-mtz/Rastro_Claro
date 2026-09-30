<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoLote extends Model
{
    use HasFactory;

    protected $table = 'movimientos_lote';

    protected $fillable = [
        'owner_id',
        'animal_id',
        'lote_anterior_id',
        'lote_nuevo_id',
        'fecha',
        'motivo',
        'observaciones',
        'responsable_id',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function loteAnterior(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'lote_anterior_id');
    }

    public function loteNuevo(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'lote_nuevo_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function getDescripcionAttribute(): string
    {
        $desde = $this->loteAnterior?->nombre ?? 'sin lote';
        $hacia = $this->loteNuevo?->nombre ?? 'sin lote';

        return "De {$desde} a {$hacia}";
    }
}
