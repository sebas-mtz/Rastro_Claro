<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
class Faena extends Model
{
    use HasFactory;

    protected $fillable = [
        'animal_id',
        'lote_id',
        'fecha',
        'tipo_corte',
        'peso_canal',
        'peso_carne', 
        'peso_cuero',
        'peso_grasa',
        'peso_plumas', // ✅ PLUMAS
        'peso_hueso',
        'peso_visceras',
        'costo_total',
        'rendimiento',
        'observaciones'
    ];

    protected $casts = [
        'fecha' => 'date',
        'peso_canal' => 'decimal:2',
        'peso_carne' => 'decimal:2',
        'peso_cuero' => 'decimal:2',
        'peso_grasa' => 'decimal:2',
        'peso_plumas' => 'decimal:2',
        'peso_hueso' => 'decimal:2',
        'peso_visceras' => 'decimal:2',
        'rendimiento' => 'decimal:2',
    ];

    // ✅ RELACIONES
    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function lote()
    {
        return $this->belongsTo(Lote::class);
    }

    // ✅ CALCULAR RENDIMIENTO AUTOMÁTICAMENTE
    public static function boot()
    {
        parent::boot();

        static::saving(function ($faena) {
            if ($faena->peso_canal > 0) {
                $faena->rendimiento = ($faena->peso_carne / $faena->peso_canal) * 100;
            }
        });
    }

    // ✅ SCOPE para filtros comunes
    public function scopePorLote($query, $loteId)
    {
        return $query->where('lote_id', $loteId);
    }

    public function scopePorFecha($query, $fechaInicio, $fechaFin = null)
    {
        $query->where('fecha', '>=', $fechaInicio);

        if ($fechaFin) {
            $query->where('fecha', '<=', $fechaFin);
        }

        return $query;
    }


    public function ventasSubproductos(): MorphMany
    {
        return $this->morphMany(Venta::class, 'vendible');
    }

    /**
     * Obtener subproductos disponibles para venta
     */
    public function getSubproductosDisponiblesAttribute(): array
    {
        $subproductos = [];
        
        if ($this->peso_cuero > 0) {
            $subproductos[] = ['producto' => 'Cuero', 'cantidad' => $this->peso_cuero, 'unidad' => 'kg'];
        }
        if ($this->peso_grasa > 0) {
            $subproductos[] = ['producto' => 'Grasa', 'cantidad' => $this->peso_grasa, 'unidad' => 'kg'];
        }
        if ($this->peso_plumas > 0) {
            $subproductos[] = ['producto' => 'Plumas', 'cantidad' => $this->peso_plumas, 'unidad' => 'kg'];
        }
        if ($this->peso_hueso > 0) {
            $subproductos[] = ['producto' => 'Hueso', 'cantidad' => $this->peso_hueso, 'unidad' => 'kg'];
        }
        if ($this->peso_visceras > 0) {
            $subproductos[] = ['producto' => 'Vísceras', 'cantidad' => $this->peso_visceras, 'unidad' => 'kg'];
        }

        return $subproductos;
    }
}