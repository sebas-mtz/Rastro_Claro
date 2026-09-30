<?php

namespace Database\Factories;

use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Venta> */
class VentaFactory extends Factory
{
    protected $model = Venta::class;

    public function definition(): array
    {
        return [
            'vendible_type' => \App\Models\Animal::class,
            'vendible_id' => AnimalFactory::new(),
            'comprador_id' => CompradorFactory::new(),
            'vendedor_id' => auth()->id() ?? UserFactory::new(),
            'fecha_venta' => today()->toDateString(),
            'tipo_venta' => 'animal',
            'estado_venta' => 'pendiente',
            'producto' => 'Ovino',
            'cantidad' => 1,
            'unidad' => 'unidad',
            'precio_unitario' => 3000,
            'precio_total' => 3000,
            'metodo_pago' => 'efectivo',
            'estado_pago' => 'pendiente',
        ];
    }
}
