<?php

namespace Database\Factories;

use App\Models\Lote;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Lote> */
class LoteFactory extends Factory
{
    protected $model = Lote::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->bothify('Lote ######'),
        ];
    }
}
