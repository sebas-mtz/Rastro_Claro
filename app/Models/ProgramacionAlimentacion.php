<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramacionAlimentacion extends Model
{
    use HasFactory;

    protected $table = 'programacion_alimentacions';

    protected $fillable = [
        'racion_id',
        'animal_id',
        'lote_id',
        'fecha_inicio',
        'fecha_fin',
        'hora',
        'cantidad',
        'unidad',
        'frecuencia',
        'activa',
        'notas',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activa' => 'boolean',
    ];

    public function racion()
    {
        return $this->belongsTo(Racion::class);
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function lote()
    {
        return $this->belongsTo(Lote::class);
    }

    public function alimentaciones()
    {
        return $this->hasMany(Alimentacion::class, 'programacion_alimentacion_id');
    }
}