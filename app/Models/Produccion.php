<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produccion extends Model
{
    use HasFactory;

    protected $fillable = ['animal_id','fecha','tipo','valor','unidad'];

    public function animal() {
        return $this->belongsTo(Animal::class);
    }
}
