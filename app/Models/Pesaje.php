<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Pesaje extends Model
{
    use HasFactory;

    protected $table = 'pesajes';

    /** Cómo se obtuvo el peso. */
    public const METODOS = [
        'bascula'    => 'Báscula',
        'cinta'      => 'Cinta métrica (estimado)',
        'estimacion' => 'Estimación visual',
    ];

    protected $fillable = [
        'animal_id',
        'fecha',
        'peso',
        'unidad',
        'condicion_corporal',
        'metodo',
        'responsable',
        'notas',
    ];

    protected $casts = [
        'fecha'              => 'date',
        'peso'               => 'float',
        'condicion_corporal' => 'decimal:1',
    ];

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    /**
     * Registro de condición corporal generado desde este pesaje, cuando se
     * capturó la CC junto con el peso.
     */
    public function condicionCorporal(): MorphMany
    {
        return $this->morphMany(CondicionCorporal::class, 'origen', 'origen_tipo', 'origen_id');
    }

    public function getMetodoLegibleAttribute(): ?string
    {
        return $this->metodo ? (self::METODOS[$this->metodo] ?? $this->metodo) : null;
    }
}
