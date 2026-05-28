<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sacrificio extends Model
{
    use HasFactory;

    protected $fillable = [
        'animal_id',
        'lote_id', 
        'fecha',
        'motivo',
        'peso_vivo',
        'peso_canal',
        'rendimiento',
        'cuero',
        'grasa',
        'visceras',
        'plumas', // ✅ PLUMAS
        'observaciones'
    ];

    protected $casts = [
        'fecha' => 'date',
        'peso_vivo' => 'decimal:2',
        'peso_canal' => 'decimal:2',
        'rendimiento' => 'decimal:2',
        'cuero' => 'boolean',
        'grasa' => 'boolean', 
        'visceras' => 'boolean',
        'plumas' => 'boolean',
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

        static::saving(function ($sacrificio) {
            if ($sacrificio->peso_vivo > 0) {
                $sacrificio->rendimiento = ($sacrificio->peso_canal / $sacrificio->peso_vivo) * 100;
            }
        });
    }

    // ✅ MÉTODOS DE AYUDA
    public function getMotivoTextoAttribute()
    {
        return [
            'descarte' => 'Descarte (animal viejo)',
            'enfermedad' => 'Enfermedad',
            'accidente' => 'Accidente', 
            'autoconsumo' => 'Auto-consumo'
        ][$this->motivo] ?? $this->motivo;
    }

    public function getSubproductosAttribute()
    {
        $subproductos = [];
        if ($this->cuero) $subproductos[] = 'Cuero';
        if ($this->grasa) $subproductos[] = 'Grasa';
        if ($this->visceras) $subproductos[] = 'Vísceras';
        if ($this->plumas) $subproductos[] = 'Plumas';
        
        return $subproductos;
    }
}