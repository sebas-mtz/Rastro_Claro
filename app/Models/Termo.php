<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Termo extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'nombre',
        'ubicacion',
        'capacidad',
        'estado',
        'descripcion',
    ];

    public function pajillas()
    {
        return $this->hasMany(Pajilla::class);
    }

    public function pajillasDisponibles()
    {
        return $this->hasMany(Pajilla::class)
            ->where('estado', 'disponible');
    }
}