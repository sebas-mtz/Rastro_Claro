<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;   // 👈 ESTA ES LA CLAVE

class Alimentacion extends Model
{
    use HasFactory;

    protected $table = 'alimentacions';

    protected $fillable = [
        'fecha',
        'animal_id',
        'lote_id',
        'racion_id',
        'consumo_kg',
        'costo',
        'proveedor_id',
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
}
