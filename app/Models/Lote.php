<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Lote extends Model
{
    protected $fillable = ['nombre','corral_potrero','descripcion','responsable_id'];

    public function animales() {
        return $this->hasMany(Animal::class);
    }

    public function ventas(): MorphMany
    {
        return $this->morphMany(Venta::class, 'vendible');
    }
    public function salud() {
        return $this->hasMany(EventoSalud::class);
    }
    public function responsable() {
        return $this->belongsTo(User::class,'responsable_id');
    } /** @use HasFactory<\Database\Factories\LoteFactory> */
    use HasFactory;
}