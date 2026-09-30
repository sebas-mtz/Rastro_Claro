<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DonadorExterno extends Model
{
    use HasFactory;

    protected $table = 'donadores_externos';

    protected $fillable = [
        'codigo',
        'nombre',
        'raza',
        'proveedor',
        'registro_genealogico',
        'pais_origen',
        'observaciones',
    ];

    public function pajillas()
    {
        return $this->hasMany(Pajilla::class);
    }

public function crias(): HasMany
{
    return $this->hasMany(Animal::class, 'padre_externo_id');
}
}