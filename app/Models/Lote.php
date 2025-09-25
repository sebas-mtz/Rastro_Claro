<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lote extends Model
{
    protected $fillable = ['nombre','corral_potrero','responsable_id'];

    public function animales() {
        return $this->hasMany(Animal::class);
    }

    public function responsable() {
        return $this->belongsTo(User::class,'responsable_id');
    } /** @use HasFactory<\Database\Factories\LoteFactory> */
    use HasFactory;
}
