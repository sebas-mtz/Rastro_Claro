<?php

namespace Database\Factories;

use App\Models\Sacrificio;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Sacrificio> */
class SacrificioFactory extends Factory
{
    protected $model = Sacrificio::class;

    public function definition(): array
    {
        return [
            'animal_id' => AnimalFactory::new(),
            'fecha' => today()->toDateString(),
            'motivo' => 'autoconsumo',
            'peso_vivo' => 40,
            'peso_canal' => 20,
            'rendimiento' => 50,
        ];
    }
}
