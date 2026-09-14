<?php

namespace Database\Factories;

use App\Models\Racion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Racion> */
class RacionFactory extends Factory
{
    protected $model = Racion::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->bothify('Ración ######'),
            'precio_kg' => 5,
            'activo' => true,
        ];
    }
}
