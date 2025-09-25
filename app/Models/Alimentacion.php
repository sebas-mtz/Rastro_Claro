<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alimentacion extends Model
{
    use HasFactory;

    protected $fillable = ['animal_id','lote_id','fecha','racion_id','consumo_kg','costo','proveedor_id'];

    public function animal() {
        return $this->belongsTo(Animal::class);
    }

    public function lote() {
        return $this->belongsTo(Lote::class);
    }

    public function racion() {
        return $this->belongsTo(Racion::class);
    }

    public function proveedor() {
        return $this->belongsTo(User::class,'proveedor_id');
    }
}

