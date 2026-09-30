<?php

namespace Database\Factories;

use App\Models\Vacuna;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Vacuna> */
class VacunaFactory extends Factory
{
    protected $model = Vacuna::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->bothify('Vacuna ######'),
            'especie_objetivo' => 'Ovino',
        ];
    }
}
