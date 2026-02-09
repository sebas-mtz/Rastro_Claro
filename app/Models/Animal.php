<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\MorphMany;

class Animal extends Model
{
    use HasFactory;

    protected $fillable = [
        'especie','alias','raza','arete','sexo','fecha_nac','peso','BCS','estado_productivo','lote_id'
    ];

    public function lote() {
        return $this->belongsTo(Lote::class);
    }

    public function salud() {
        return $this->hasMany(Salud::class);
    }

    public function producciones() {
        return $this->hasMany(Produccion::class);
    }

    public function alimentaciones() {
        return $this->hasMany(Alimentacion::class);
    }

    public function reproducciones() {
        return $this->hasMany(Reproduccion::class);
    }
    public function reproduccionesComoHembra()
{
    return $this->hasMany(\App\Models\Reproduccion::class, 'hembra_id');
}

public function reproduccionesComoMacho()
{
    return $this->hasMany(\App\Models\Reproduccion::class, 'macho_id');
}

    
    public function ventas(): MorphMany
    {
        return $this->morphMany(Venta::class, 'vendible');
    }

    /**
     * Verificar si el animal está vendido
     */
    public function getEstaVendidoAttribute(): bool
    {
        return $this->ventas()
            ->where('tipo_venta', 'animal')
            ->where('estado_venta', 'completada')
            ->exists();
    }
}