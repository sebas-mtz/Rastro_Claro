<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalValuationHistorial extends Model
{
    use HasFactory;

    public const TIPO_CREACION = 'creacion';
    public const TIPO_RECALCULO = 'recalculo';
    public const TIPO_NUEVO_GASTO = 'nuevo_gasto';
    public const TIPO_CAMBIO_MARGEN = 'cambio_margen';
    public const TIPO_CAMBIO_REPRODUCTIVO = 'cambio_reproductivo';
    public const TIPO_AJUSTE_MANUAL = 'ajuste_manual';
    public const TIPO_CONFIRMACION_VENTA = 'confirmacion_venta';

    public const TIPOS = [
        self::TIPO_CREACION,
        self::TIPO_RECALCULO,
        self::TIPO_NUEVO_GASTO,
        self::TIPO_CAMBIO_MARGEN,
        self::TIPO_CAMBIO_REPRODUCTIVO,
        self::TIPO_AJUSTE_MANUAL,
        self::TIPO_CONFIRMACION_VENTA,
    ];

    protected $table = 'animal_valuation_historial';

    protected $fillable = [
        'owner_id',
        'valuation_id',
        'animal_id',
        'precio_anterior',
        'precio_nuevo',
        'diferencia',
        'motivo',
        'tipo_movimiento',
        'referencia_tipo',
        'referencia_id',
        'concepto',
        'valor_movimiento',
        'usuario_id',
        'datos_anteriores',
        'datos_nuevos',
    ];

    protected $casts = [
        'precio_anterior' => 'decimal:2',
        'precio_nuevo' => 'decimal:2',
        'diferencia' => 'decimal:2',
        'valor_movimiento' => 'decimal:2',
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
    ];

    public function valuacion(): BelongsTo
    {
        return $this->belongsTo(AnimalValuation::class, 'valuation_id');
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
