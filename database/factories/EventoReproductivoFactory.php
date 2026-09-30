<?php

namespace Database\Factories;

use App\Models\EventoReproductivo;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EventoReproductivo> */
class EventoReproductivoFactory extends Factory
{
    protected $model = EventoReproductivo::class;

    public function definition(): array
    {
        return [
            'hembra_id' => AnimalFactory::new()->state(['sexo' => 'H', 'estado_productivo' => 'Vientre']),
            'tipo_evento' => 'servicio',
            'fecha' => today()->toDateString(),
        ];
    }
}
