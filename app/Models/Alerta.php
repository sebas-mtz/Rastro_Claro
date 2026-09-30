<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alerta extends Model
{
    use HasFactory;

    protected $fillable = ['tipo','fecha_objetivo','severidad','asignado_a','estado'];

    public function asignado() {
        return $this->belongsTo(User::class,'asignado_a');
    }
}

