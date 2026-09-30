<?php

namespace Database\Factories;

use App\Models\Comprador;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Comprador> */
class CompradorFactory extends Factory
{
    protected $model = Comprador::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'tipo' => 'particular',
        ];
    }
}
