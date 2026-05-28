<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lote>
 */
class LoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre'          => 'Lote ' . fake()->word(),
            'corral_potrero'  => 'Corral-' . fake()->numberBetween(1, 20),
            'descripcion'     => fake()->optional()->sentence(),
            'responsable_id'  => null,
        ];
    }
}
