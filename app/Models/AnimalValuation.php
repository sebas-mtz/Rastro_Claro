<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnimalValuation extends Model
{
    use HasFactory;

    public const ESTADO_BORRADOR = 'borrador';
    public const ESTADO_ACTIVA = 'activa';
    public const ESTADO_CONFIRMADA = 'confirmada';
    public const ESTADO_CERRADA = 'cerrada';

    /**
     * Estados reproductivos usados para el plus. Son propios de la valuación:
     * el accessor Animal::estado_reproductivo sigue sirviendo al módulo de
     * Reproducción sin cambios y solo se usa aquí como sugerencia.
     */
    public const ESTADOS_REPRODUCTIVOS = [
        'joven_sin_edad_reproductiva',
        'abierta',
        'cargada_semental_comercial',
        'cargada_semental_registro',
        'con_cria_al_pie',
        'con_cria_hembra_al_pie',
        'con_cria_macho_al_pie',
        'parto_multiple',
        'otro',
    ];

    protected $table = 'animal_valuations';

    protected $fillable = [
        'owner_id',
        'animal_id',
        'costo_gestacion',
        'costo_inicial',
        'costo_sanitario',
        'costo_alimentacion',
        'costo_registro',
        'costo_mano_obra',
        'costo_transporte',
        'otros_costos',
        'costo_total_produccion',
        'porcentaje_margen_genetico',
        'valor_margen_genetico',
        'estado_reproductivo_valuacion',
        'plus_reproductivo',
        'ajuste_manual',
        'motivo_ajuste',
        'precio_estimado',
        'precio_publicado',
        'estado',
        'precio_real_venta',
        'venta_id',
        'calculado_en',
        'creado_por',
        'actualizado_por',
    ];

    protected $casts = [
        'costo_gestacion' => 'decimal:2',
        'costo_inicial' => 'decimal:2',
        'costo_sanitario' => 'decimal:2',
        'costo_alimentacion' => 'decimal:2',
        'costo_registro' => 'decimal:2',
        'costo_mano_obra' => 'decimal:2',
        'costo_transporte' => 'decimal:2',
        'otros_costos' => 'decimal:2',
        'costo_total_produccion' => 'decimal:2',
        'porcentaje_margen_genetico' => 'decimal:2',
        'valor_margen_genetico' => 'decimal:2',
        'plus_reproductivo' => 'decimal:2',
        'ajuste_manual' => 'decimal:2',
        'precio_estimado' => 'decimal:2',
        'precio_publicado' => 'decimal:2',
        'precio_real_venta' => 'decimal:2',
        'calculado_en' => 'datetime',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(AnimalValuationDetalle::class, 'valuation_id');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(AnimalValuationHistorial::class, 'valuation_id')
            ->orderByDesc('created_at');
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    public function scopeActivas($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVA);
    }

    /**
     * Utilidad frente al costo real de producción. Devuelve null mientras no
     * exista precio real de venta.
     */
    public function getUtilidadAttribute(): ?float
    {
        if ($this->precio_real_venta === null) {
            return null;
        }

        return round((float) $this->precio_real_venta - (float) $this->costo_total_produccion, 2);
    }

    /**
     * Porcentaje de utilidad. Devuelve null cuando el costo total es cero,
     * en vez de dividir entre cero.
     */
    public function getPorcentajeUtilidadAttribute(): ?float
    {
        $costo = (float) $this->costo_total_produccion;
        $utilidad = $this->utilidad;

        if ($utilidad === null || $costo <= 0.0) {
            return null;
        }

        return round(($utilidad / $costo) * 100, 2);
    }
}
