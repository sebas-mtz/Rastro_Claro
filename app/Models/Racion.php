<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Racion extends Model
{
    protected $fillable = [
        'nombre',
        'MS',
        'PB',
        'EM',
        'FDN',
        'minerales',
        'precio_kg',
        'costo_total',
        'activo',
        'archivado_at',
    ];

    protected $casts = [
        'MS'           => 'float',
        'PB'           => 'float',
        'EM'           => 'float',
        'FDN'          => 'float',
        'precio_kg'    => 'float',
        'costo_total'  => 'float',
        'activo'       => 'boolean',
        'archivado_at' => 'datetime',
    ];

    // Solo raciones activas — usar en selectores de consumo y programaciones
    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function insumos(): BelongsToMany
    {
        return $this->belongsToMany(InventarioInsumo::class, 'racion_insumo')
            ->withPivot('cantidad')
            ->withTimestamps();
    }

    public function alimentaciones(): HasMany
    {
        return $this->hasMany(Alimentacion::class);
    }

    public function programaciones(): HasMany
    {
        return $this->hasMany(ProgramacionAlimentacion::class);
    }

    public function tieneConsumos(): bool
    {
        return $this->alimentaciones()->exists();
    }

    // Genera el snapshot de composición para guardar en el momento del consumo
    public function generarSnapshotComposicion(): array
    {
        return $this->insumos->map(fn ($insumo) => [
            'insumo_id'      => $insumo->id,
            'nombre'         => $insumo->nombre,
            'cantidad'       => $insumo->pivot->cantidad,
            'unidad'         => $insumo->unidad,
            'costo_promedio' => $insumo->costo_promedio,
        ])->toArray();
    }

    // Genera el snapshot de nutrición para guardar en el momento del consumo
    public function generarSnapshotNutricion(): array
    {
        return [
            'MS'  => $this->MS,
            'PB'  => $this->PB,
            'EM'  => $this->EM,
            'FDN' => $this->FDN,
        ];
    }
}