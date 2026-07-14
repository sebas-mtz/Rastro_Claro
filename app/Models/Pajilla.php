<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Pajilla extends Model
{
    use HasFactory;

    protected $fillable = [
        'termo_id',
        'animal_id',
        'donador_externo_id', // agregar este
        'codigo',
        'lote',
        'fecha_ingreso',
        'fecha_vencimiento',
        'fecha_utilizacion',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_utilizacion' => 'date',
    ];

    public function termo()
    {
        return $this->belongsTo(Termo::class);
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function serviciosReproductivos(): HasMany
    {
        return $this->hasMany(ServicioReproductivo::class, 'pajilla_id');
    }
    public function donadorExterno()
    {
        return $this->belongsTo(DonadorExterno::class);
    }
    // app/Models/Pajilla.php
// app/Models/Pajilla.php

protected static function booted()
{
    static::saving(function (Pajilla $pajilla) {
        if (
            $pajilla->estado === 'disponible'
            && $pajilla->fecha_vencimiento
            && $pajilla->fecha_vencimiento <= today()->toDateString()
        ) {
            $pajilla->estado = 'vencida';
        }
    });
}
}