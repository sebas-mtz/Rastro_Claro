<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cria extends Model
{
    protected $table = 'crias';

    protected $fillable = [
        'parto_id',
        'animal_id',
        'sexo',
        'peso_nacimiento',
        'condicion',
        'arete_temporal',
        'observaciones',
    ];

    protected $casts = [
        'peso_nacimiento' => 'decimal:2',
    ];

    // ─── Relaciones ───────────────────────────────────────────────────────

    public function parto(): BelongsTo
    {
        return $this->belongsTo(Parto::class, 'parto_id');
    }

    // Animal creado en el sistema para esta cría (si nació viva)
    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'animal_id');
    }

    // Acceso directo a la madre a través del parto y su evento
    public function getMadreAttribute(): ?Animal
    {
        return $this->parto?->evento?->hembra;
    }

    // ─── Accessors ────────────────────────────────────────────────────────

    public function getNacioVivaAttribute(): bool
    {
        return $this->condicion === 'vivo';
    }

    public function getIdentificadorAttribute(): string
    {
        if ($this->animal?->arete) {
            return $this->animal->arete;
        }
        if ($this->arete_temporal) {
            return "Temp: {$this->arete_temporal}";
        }
        return "Sin arete — {$this->sexo}";
    }
}