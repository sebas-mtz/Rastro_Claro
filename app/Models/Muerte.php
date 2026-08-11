<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Muerte extends Model
{
    protected $table = 'muertes';

    protected $fillable = [
        'animal_id',
        'fecha',
        'causa',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
