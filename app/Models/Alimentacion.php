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
}