<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vacuna extends Model
{
    use HasFactory;

    protected $fillable = ['nombre','patogeno','pauta','refuerzo_dias','especie_objetivo'];

    public function salud() {
        return $this->hasMany(Salud::class);
    }
}

