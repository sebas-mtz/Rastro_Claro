<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Racion extends Model
{
    use HasFactory;

    protected $fillable = ['nombre','MS','PB','EM','FDN','minerales','precio_kg'];

    public function alimentaciones() {
        return $this->hasMany(Alimentacion::class);
    }
}

