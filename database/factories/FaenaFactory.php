<?php

namespace Database\Factories;

use App\Models\Faena;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Faena> */
class FaenaFactory extends Factory
{
    protected $model = Faena::class;

    public function definition(): array
    {
        return [
            'animal_id' => AnimalFactory::new(),
            'fecha' => today()->toDateString(),
            'tipo_corte' => 'completo',
            'peso_canal' => 20,
            'peso_carne' => 12,
            'rendimiento' => 60,
        ];
    }
}
