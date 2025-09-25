<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Animal extends Model
{
    use HasFactory;

    protected $fillable = [
        'especie','raza','arete','sexo','fecha_nac','peso','BCS','estado_productivo','lote_id'
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
}
