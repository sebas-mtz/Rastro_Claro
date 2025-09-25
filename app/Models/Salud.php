<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salud extends Model
{
    use HasFactory;

    protected $fillable = ['animal_id','fecha','diagnostico','tratamiento','vacuna_id','dosis','observaciones'];

    public function animal() {
        return $this->belongsTo(Animal::class);
    }

    public function vacuna() {
        return $this->belongsTo(Vacuna::class);
    }
}
