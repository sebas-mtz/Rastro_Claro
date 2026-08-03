<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalGenetica extends Model
{
    use HasFactory;

    protected $table = 'animal_geneticas';

    protected $fillable = [
        'owner_id',
        'animal_id',
        'porcentaje_pureza',
        'numero_registro',
        'asociacion',
        'certificado_pureza',
        'linea_genetica',
        'calidad_fenotipica',
        'aplomos',
        'caracteristicas_destacadas',
        'premios',
        'porcentaje_margen_genetico',
        'observaciones',
    ];

    protected $casts = [
        'porcentaje_pureza' => 'decimal:2',
        'porcentaje_margen_genetico' => 'decimal:2',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
