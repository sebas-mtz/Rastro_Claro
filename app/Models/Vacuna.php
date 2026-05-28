<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vacuna extends Model
{
    protected $fillable = [
        'nombre',
        'patogeno',
        'pauta',
        'refuerzo_dias',
        'especie_objetivo',
    ];

    protected $casts = [
        'refuerzo_dias' => 'integer',
    ];

    // Una vacuna puede estar en muchos eventos de salud
    public function eventosSalud(): HasMany
    {
        return $this->hasMany(EventoSalud::class, 'vacuna_id');
    }

    /**
     * Calcula la fecha de próximo refuerzo a partir de una fecha dada.
     * Útil para calcular cuando se programa un evento de vacunación.
     */
    public function proximoRefuerzo(\Carbon\Carbon $fechaAplicacion): ?\Carbon\Carbon
    {
        if (!$this->refuerzo_dias) return null;

        return $fechaAplicacion->copy()->addDays($this->refuerzo_dias);
    }
}