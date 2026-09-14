<?php

namespace Database\Factories;

use App\Models\Produccion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Produccion> */
class ProduccionFactory extends Factory
{
    protected $model = Produccion::class;

    public function definition(): array
    {
        return [
            'animal_id' => AnimalFactory::new(),
            'fecha' => today()->toDateString(),
            'tipo' => 'lana',
            'valor' => 2,
            'unidad' => 'kg',
        ];
    }
}
