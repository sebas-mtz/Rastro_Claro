<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comprador extends Model
{
    protected $table = 'compradores';
    protected $fillable = [
        'nombre',
        'tipo',
        'rut_ci',
        'telefono',
        'email',
        'direccion',
        'notas',
    ];

    /**
     * Relación con las ventas
     */
    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    /**
     * Obtener el total comprado por este comprador
     */
    public function getTotalCompradoAttribute(): float
    {
        return $this->ventas()->completadas()->sum('precio_total');
    }

    /**
     * Obtener la cantidad de ventas realizadas
     */
    public function getCantidadVentasAttribute(): int
    {
        return $this->ventas()->completadas()->count();
    }
}