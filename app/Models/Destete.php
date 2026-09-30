<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Destete extends Model
{
    protected $fillable = [
        'evento_id',
        'parto_id',
        'estado_madre',
        'estado_productivo_madre',
        'tipo_nacimiento',
    ];

    public function evento(): BelongsTo
    {
        return $this->belongsTo(EventoReproductivo::class);
    }

    public function parto(): BelongsTo
    {
        return $this->belongsTo(Parto::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DesteteCria::class);
    }
}
