<?php

namespace Database\Factories;

use App\Models\Alerta;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Alerta> */
class AlertaFactory extends Factory
{
    protected $model = Alerta::class;

    public function definition(): array
    {
        return [
            'tipo' => 'revision',
            'fecha_objetivo' => today()->toDateString(),
            'severidad' => 'media',
            'estado' => 'pendiente',
        ];
    }
}
