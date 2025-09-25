<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reproduccion extends Model
{
    use HasFactory;

    protected $fillable = ['animal_id','evento','fecha','notas'];

    public function animal() {
        return $this->belongsTo(Animal::class);
    }
}

