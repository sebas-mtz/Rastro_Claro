<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alimentacion extends Model
{
    use HasFactory;

    protected $table = 'alimentacions';

    protected $fillable = [
        'fecha',
        'hora',
        'tipo',
        'cantidad',
        'unidad',
        'animal_id',
        'lote_id',
        'racion_id',
        'programacion_alimentacion_id',
        'generado_automaticamente',
        'snapshot_composicion',
        'snapshot_nutricion',
        'notas',
    ];

    protected $casts = [
        'snapshot_composicion' => 'array',  // ← para que Laravel serialize/deserialize JSON automáticamente
        'snapshot_nutricion'   => 'array',
        'generado_automaticamente' => 'boolean',
    ];

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function lote()
    {
        return $this->belongsTo(Lote::class);
    }

    public function racion()
    {
        return $this->belongsTo(Racion::class);
    }

    public function programacion()
    {
        return $this->belongsTo(
            ProgramacionAlimentacion::class,
            'programacion_alimentacion_id'
        );
    }

    /**
     * Costo por kg de la ración consumida. Toma primero el precio vigente de
     * la ración y, si no existe (por ejemplo porque la ración fue eliminada),
     * lo reconstruye desde el snapshot guardado al momento del consumo.
     * Devuelve null cuando no hay forma de saberlo — nunca cero inventado.
     */
    public function costoPorKg(): ?float
    {
        if ($this->racion?->precio_kg) {
            return (float) $this->racion->precio_kg;
        }

        if (empty($this->snapshot_composicion)) {
            return null;
        }

        // Σ(cantidad_insumo × costo_promedio_insumo) / Σ(cantidad_insumo)
        $costoTotal    = 0;
        $cantidadTotal = 0;

        foreach ($this->snapshot_composicion as $insumo) {
            $cantidad       = (float) ($insumo['cantidad'] ?? 0);
            $costoPromedio  = (float) ($insumo['costo_promedio'] ?? 0);
            $costoTotal    += $cantidad * $costoPromedio;
            $cantidadTotal += $cantidad;
        }

        return $cantidadTotal > 0 ? round($costoTotal / $cantidadTotal, 4) : null;
    }

    /**
     * Costo total de este consumo. Null si no se puede determinar el costo/kg.
     */
    public function costoTotal(): ?float
    {
        $costoKg = $this->costoPorKg();

        return $costoKg === null ? null : round($costoKg * (float) $this->cantidad, 2);
    }
}