<?php

namespace Database\Factories;

use App\Models\InventarioInsumo;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventarioInsumo> */
class InventarioInsumoFactory extends Factory
{
    protected $model = InventarioInsumo::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->bothify('Insumo ######'),
            'tipo' => 'Forraje',
            'existencias' => 100,
            'unidad' => 'kg',
            'costo_promedio' => 5,
        ];
    }
}
