<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pesaje extends Model
{
    use HasFactory;

    protected $table = 'pesajes';

    protected $fillable = [
        'animal_id',
        'fecha',
        'peso',
        'notas',
    ];

    protected $casts = [
        'fecha' => 'date',
        'peso'  => 'float',
    ];

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }
}