<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioInsumo extends Model
{
    use HasFactory;

    protected $fillable = ['nombre','tipo','existencias','unidad','costo_promedio'];
}

