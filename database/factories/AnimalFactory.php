<?php

namespace Database\Factories;

use App\Models\Animal;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Animal> */
class AnimalFactory extends Factory
{
    protected $model = Animal::class;

    public function definition(): array
    {
        return [
            'especie' => 'Ovino',
            'raza' => 'Dorper',
            'arete' => fake()->unique()->bothify('OV-########'),
            'sexo' => fake()->randomElement(['M', 'H']),
            'fecha_nac' => now()->subYear()->toDateString(),
            'peso' => 40,
            'estado_productivo' => 'Cordero',
        ];
    }
}
