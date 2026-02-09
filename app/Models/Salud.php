<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute; // 👈 para el accessor color()

class Salud extends Model
{
    // (opcional, pero explícito)
    protected $table = 'saluds';

    protected $fillable = [
        'animal_id','vacuna_id',
        'fecha_programada','fecha_aplicacion','hora',
        'diagnostico','tratamiento','dosis','observaciones',
        'estado',
    ];

    protected $casts = [
        'fecha_programada' => 'date',
        'fecha_aplicacion' => 'date',
    ];

    /* ==== Relaciones ==== */
    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function vacuna()
    {
        return $this->belongsTo(Vacuna::class);
    }

    /* ==== Accesor para el color del calendario ==== */
    protected function color(): Attribute
    {
        return Attribute::get(function () {
            $hoy = now()->startOfDay();

            if ($this->fecha_aplicacion) {
                return 'green'; // hecha
            }
            if ($this->fecha_programada?->lt($hoy)) {
                return 'red';   // vencida
            }
            if ($this->fecha_programada?->lte($hoy->copy()->addDays(7))) {
                return 'yellow'; // pronto
            }
            return null;
        });
    }
}
