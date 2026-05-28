<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Animal>
 */
class AnimalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'especie'  => fake()->randomElement(['Bovino', 'Porcino', 'Ovino']),
            'raza'     => 'Holstein',
            'arete'    => fake()->unique()->numerify('ARETE-####'),
            'alias'    => fake()->optional()->firstName(),
            'sexo'     => fake()->randomElement(['M', 'F']),
            'fecha_nac' => fake()->dateTimeBetween('-5 years', '-1 month'),
            'peso'     => fake()->randomFloat(2, 20, 600),
            'BCS'      => fake()->randomFloat(1, 1, 5),
            'estado_productivo' => 'activo',
            'lote_id'  => null,
        ];
    }

    public function hembra(): static
    {
        return $this->state(['sexo' => 'F']);
    }

    public function macho(): static
    {
        return $this->state(['sexo' => 'M']);
    }
}
