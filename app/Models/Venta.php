<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Venta extends Model
{
    protected $fillable = [
        'tipo_venta',
    'vendible_type',
    'vendible_id',
    'producto',
    'comprador_id',
    'fecha_venta',
    'cantidad',
    'unidad',
    'precio_unitario',
    'precio_total',
    'metodo_pago',
    'estado_venta',
    'estado_pago',
    'condiciones_entrega',
    'fecha_entrega',
    'observaciones',
    'numero_factura',
    'vendedor_id',
    ];

    protected $casts = [
        'fecha_venta' => 'date',
        'fecha_entrega' => 'date',
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'precio_total' => 'decimal:2',
    ];

    /**
     * Relación polimórfica: puede vender Animal, Lote, Produccion o subproductos de Faena
     */
    public function vendible(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relación con el comprador
     */
    public function comprador(): BelongsTo
    {
        return $this->belongsTo(Comprador::class);
    }

    /**
     * Relación con el vendedor (usuario)
     */
    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    /**
     * Scope para ventas completadas
     */
    public function scopeCompletadas($query)
    {
        return $query->where('estado_venta', 'completada');
    }

    /**
     * Scope para ventas pendientes de pago
     */
    public function scopePendientesPago($query)
    {
        return $query->where('estado_pago', 'pendiente');
    }

    /**
     * Calcular comisión del vendedor (5%)
     */
    public function getComisionAttribute(): float
    {
        return $this->precio_total * 0.05;
    }

    /**
     * Verificar si la venta está pagada completamente
     */
    public function getEstaPagadaAttribute(): bool
    {
        return $this->estado_pago === 'completado';
    }

    
    public function getEstadoVentaLabelAttribute(): string
    {
        return [
            'pendiente' => 'Pendiente',
            'completada' => 'Completada',
            'cancelada' => 'Cancelada',
        ][$this->estado_venta] ?? $this->estado_venta;
    }
}