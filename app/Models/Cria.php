<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cria extends Model
{
    protected $table = 'crias';

    /** Tipo de nacimiento según el número de crías del parto. */
    public const TIPOS_NACIMIENTO = [
        'unico'     => 'Único',
        'doble'     => 'Doble',
        'triple'    => 'Triple',
        'cuadruple' => 'Cuádruple',
        'otro'      => 'Otro',
    ];

    protected $fillable = [
        'parto_id',
        'animal_id',
        'sexo',
        'tipo_nacimiento',
        'peso_nacimiento',
        'calostro_aplicado',
        'fecha_calostro',
        'madre_nodriza_id',
        'fecha_destete',
        'peso_destete',
        'estado_actual',
        'causa_baja',
        'condicion',
        'arete_temporal',
        'observaciones',
    ];

    protected $casts = [
        'peso_nacimiento'   => 'decimal:2',
        'peso_destete'      => 'decimal:2',
        'calostro_aplicado' => 'boolean',
        'fecha_calostro'    => 'date',
        'fecha_destete'     => 'date',
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

    // Acceso directo a la madre biológica a través del parto y su evento
    public function getMadreAttribute(): ?Animal
    {
        return $this->parto?->evento?->hembra;
    }

    /**
     * Madre nodriza cuando la cría fue adoptada. La madre biológica se
     * conserva siempre a través del parto, así que ambas quedan registradas.
     */
    public function madreNodriza(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'madre_nodriza_id');
    }

    public function getFueAdoptadaAttribute(): bool
    {
        return $this->madre_nodriza_id !== null;
    }

    /**
     * Ganancia de peso del nacimiento al destete. Null si falta alguno
     * de los dos pesos.
     */
    public function getGananciaAlDesteteAttribute(): ?float
    {
        if ($this->peso_nacimiento === null || $this->peso_destete === null) {
            return null;
        }

        return round((float) $this->peso_destete - (float) $this->peso_nacimiento, 2);
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