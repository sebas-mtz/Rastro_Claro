<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tratamiento extends Model
{
    protected $fillable = [
        'animal_id',
        'nombre',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'notas',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }
}
