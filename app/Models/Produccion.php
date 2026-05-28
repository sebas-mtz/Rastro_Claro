<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\MorphMany;

class Produccion extends Model
{
    use HasFactory;

    protected $table = 'produccions';
    protected $fillable = ['animal_id','fecha','tipo','valor','unidad'];

    public function animal() {
        return $this->belongsTo(Animal::class);
    }
    public function ventas(): MorphMany
    {
        return $this->morphMany(Venta::class, 'vendible');
    }
}