<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reproduccion extends Model
{
    protected $table = 'reproduccions';

    protected $fillable = [
        'hembra_id',
        'macho_id',
        'lote_id',
        'tipo_evento',
        'fecha',
        'estado',
        'metodo',
        'semen_codigo',
        'diagnostico',
        'costo',
        'numero_crias',
        'peso_total_crias',
        'complicaciones',
        'detalle_complicaciones',
        'observaciones',
        'user_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'costo' => 'decimal:2',
        'peso_total_crias' => 'decimal:2',
        'complicaciones' => 'boolean',
        'numero_crias' => 'integer',
    ];

    public function hembra(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'hembra_id');
    }

    public function macho(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'macho_id');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
