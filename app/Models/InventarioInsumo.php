<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class InventarioInsumo extends Model
{
    use HasFactory;
    protected $table = 'inventario_insumos';
 
    protected $fillable = [
        'nombre',
        'tipo',
        'marca',
        'existencias',
        'unidad',
        'costo_promedio',
        'MS',
        'PB',
        'EM',
        'FDN',
        'auto_rellenar',
        'dias_rellenado',
        'cantidad_rellenado',
        'activo',
        'desactivado_at',
    ];
 
    protected $casts = [
        'existencias'        => 'float',
        'costo_promedio'     => 'float',
        'MS'                 => 'float',
        'PB'                 => 'float',
        'EM'                 => 'float',
        'FDN'                => 'float',
        'auto_rellenar'      => 'boolean',
        'cantidad_rellenado' => 'float',
        'activo'             => 'boolean',
        'desactivado_at'     => 'datetime',
    ];
 
    // Solo insumos activos — usar en selectores y cálculos nuevos
    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }
 
    public function raciones(): BelongsToMany
    {
        return $this->belongsToMany(Racion::class, 'racion_insumo')
            ->withPivot('cantidad')
            ->withTimestamps();
    }
 
    public function alimentaciones()
    {
        return $this->hasMany(Alimentacion::class, 'racion_id');
    }
 
    // Verifica si el insumo está siendo usado en raciones activas o tiene consumos registrados
    public function tieneReferencias(): bool
    {
        $enRaciones = $this->raciones()->whereHas('racion', fn ($q) => $q->where('activo', true))->exists();
 
        if ($enRaciones) {
            return true;
        }
 
        // Verificar si aparece en alimentaciones via la tabla pivote
        return DB::table('racion_insumo')
            ->where('inventario_insumo_id', $this->id)
            ->exists();
    }
}