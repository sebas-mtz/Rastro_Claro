<?php

namespace Database\Factories;

use App\Models\EventoSalud;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EventoSalud> */
class EventoSaludFactory extends Factory
{
    protected $model = EventoSalud::class;

    public function definition(): array
    {
        return [
            'animal_id' => AnimalFactory::new(),
            'fecha_programada' => today()->toDateString(),
            'diagnostico' => 'Revisión preventiva',
            'tipo' => 'consulta',
        ];
    }
}
