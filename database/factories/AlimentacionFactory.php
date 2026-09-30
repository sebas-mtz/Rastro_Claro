<?php

namespace Database\Factories;

use App\Models\Alimentacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Alimentacion> */
class AlimentacionFactory extends Factory
{
    protected $model = Alimentacion::class;

    public function definition(): array
    {
        return [
            'animal_id' => AnimalFactory::new(),
            'racion_id' => RacionFactory::new(),
            'fecha' => today()->toDateString(),
            'cantidad' => 2,
            'unidad' => 'kg',
        ];
    }
}
