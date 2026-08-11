<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesteteCria extends Model
{
    protected $table = 'destete_crias';

    protected $fillable = [
        'destete_id',
        'cria_id',
        'peso_destete',
        'estado_destino',
    ];

    protected $casts = [
        'peso_destete' => 'float',
    ];

    public function destete(): BelongsTo
    {
        return $this->belongsTo(Destete::class);
    }

    public function cria(): BelongsTo
    {
        return $this->belongsTo(Cria::class);
    }
}
