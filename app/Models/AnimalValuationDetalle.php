<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AnimalValuationDetalle extends Model
{
    use HasFactory;

    /** Buckets que componen el costo total de producción. */
    public const CATEGORIA_GESTACION = 'gestacion';
    public const CATEGORIA_INICIAL = 'inicial';
    public const CATEGORIA_SANITARIO = 'sanitario';
    public const CATEGORIA_ALIMENTACION = 'alimentacion';
    public const CATEGORIA_REGISTRO = 'registro';
    public const CATEGORIA_MANO_OBRA = 'mano_obra';
    public const CATEGORIA_TRANSPORTE = 'transporte';
    public const CATEGORIA_OTROS = 'otros';

    protected $table = 'animal_valuation_detalles';

    protected $fillable = [
        'owner_id',
        'valuation_id',
        'animal_id',
        'categoria',
        'concepto',
        'descripcion',
        'fecha',
        'cantidad',
        'unidad',
        'costo_unitario',
        'costo_total',
        'origen_tipo',
        'origen_id',
        'es_automatico',
        'metodo_distribucion',
        'observaciones',
        'creado_por',
    ];

    protected $casts = [
        'fecha' => 'date',
        'cantidad' => 'decimal:2',
        'costo_unitario' => 'decimal:2',
        'costo_total' => 'decimal:2',
        'es_automatico' => 'boolean',
    ];

    public function valuacion(): BelongsTo
    {
        return $this->belongsTo(AnimalValuation::class, 'valuation_id');
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    /** Registro del que salió este movimiento (EventoSalud, Costo, Alimentacion...). */
    public function origen(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'origen_tipo', 'origen_id');
    }
}
