<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Venta;
use App\Models\Animal;
use App\Models\User;
use Carbon\Carbon;

class VentaSeeder extends Seeder
{
    public function run(): void
    {
        $hoy = Carbon::now();

        /*
        |--------------------------------------------------------------------------
        | VENDEDOR
        |--------------------------------------------------------------------------
        | La tabla ventas requiere vendedor_id obligatorio.
        */

        $vendedor = User::query()->first();

        if (!$vendedor) {
            $this->command->warn('No hay usuarios registrados. No se pueden crear ventas porque vendedor_id es obligatorio.');
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | 1. VENTAS DE PRODUCTOS OVINOS
        |--------------------------------------------------------------------------
        | Estas ventas no se relacionan con un animal específico.
        | Por eso vendible_id y vendible_type van en null.
        */

        $ventasProductos = [
            // ─────────────────────────────────────────────
            // LECHE OVINA
            // ─────────────────────────────────────────────
            [
                'fecha_venta'         => $hoy->copy()->subDays(24)->toDateString(),
                'producto'            => 'Leche ovina',
                'cantidad'            => 18.50,
                'unidad'              => 'litros',
                'precio_unitario'     => 32.00,
                'metodo_pago'         => 'efectivo',
                'estado_pago'         => 'completado',
                'condiciones_entrega' => 'Entrega directa en rancho.',
                'fecha_entrega'       => $hoy->copy()->subDays(24)->toDateString(),
                'observaciones'       => 'Venta de leche ovina acumulada de hembras en lactancia.',
            ],
            [
                'fecha_venta'         => $hoy->copy()->subDays(18)->toDateString(),
                'producto'            => 'Leche ovina',
                'cantidad'            => 21.00,
                'unidad'              => 'litros',
                'precio_unitario'     => 32.50,
                'metodo_pago'         => 'transferencia',
                'estado_pago'         => 'completado',
                'condiciones_entrega' => 'Entrega acordada con el comprador.',
                'fecha_entrega'       => $hoy->copy()->subDays(18)->toDateString(),
                'observaciones'       => 'Venta de leche destinada a elaboración de queso artesanal.',
            ],
            [
                'fecha_venta'         => $hoy->copy()->subDays(11)->toDateString(),
                'producto'            => 'Leche ovina',
                'cantidad'            => 16.80,
                'unidad'              => 'litros',
                'precio_unitario'     => 33.00,
                'metodo_pago'         => 'efectivo',
                'estado_pago'         => 'completado',
                'condiciones_entrega' => 'Venta directa al comprador.',
                'fecha_entrega'       => $hoy->copy()->subDays(11)->toDateString(),
                'observaciones'       => 'Venta directa de leche ovina.',
            ],
            [
                'fecha_venta'         => $hoy->copy()->subDays(5)->toDateString(),
                'producto'            => 'Leche ovina',
                'cantidad'            => 24.30,
                'unidad'              => 'litros',
                'precio_unitario'     => 33.50,
                'metodo_pago'         => 'transferencia',
                'estado_pago'         => 'completado',
                'condiciones_entrega' => 'Entrega programada y completada.',
                'fecha_entrega'       => $hoy->copy()->subDays(5)->toDateString(),
                'observaciones'       => 'Venta reciente de leche ovina.',
            ],

            // ─────────────────────────────────────────────
            // LANA OVINA
            // ─────────────────────────────────────────────
            [
                'fecha_venta'         => $hoy->copy()->subDays(35)->toDateString(),
                'producto'            => 'Lana ovina',
                'cantidad'            => 14.00,
                'unidad'              => 'kg',
                'precio_unitario'     => 48.00,
                'metodo_pago'         => 'efectivo',
                'estado_pago'         => 'completado',
                'condiciones_entrega' => 'Producto entregado en costales.',
                'fecha_entrega'       => $hoy->copy()->subDays(35)->toDateString(),
                'observaciones'       => 'Venta de lana obtenida de esquila ovina.',
            ],
            [
                'fecha_venta'         => $hoy->copy()->subDays(21)->toDateString(),
                'producto'            => 'Lana ovina',
                'cantidad'            => 11.50,
                'unidad'              => 'kg',
                'precio_unitario'     => 49.00,
                'metodo_pago'         => 'transferencia',
                'estado_pago'         => 'completado',
                'condiciones_entrega' => 'Entrega directa al comprador regional.',
                'fecha_entrega'       => $hoy->copy()->subDays(21)->toDateString(),
                'observaciones'       => 'Venta de lana clasificada para uso textil.',
            ],
            [
                'fecha_venta'         => $hoy->copy()->subDays(8)->toDateString(),
                'producto'            => 'Lana ovina',
                'cantidad'            => 9.80,
                'unidad'              => 'kg',
                'precio_unitario'     => 50.00,
                'metodo_pago'         => 'efectivo',
                'estado_pago'         => 'completado',
                'condiciones_entrega' => 'Producto entregado al taller artesanal.',
                'fecha_entrega'       => $hoy->copy()->subDays(8)->toDateString(),
                'observaciones'       => 'Venta reciente de lana ovina.',
            ],
        ];

        foreach ($ventasProductos as $index => $venta) {
            $precioTotal = round($venta['cantidad'] * $venta['precio_unitario'], 2);

            Venta::create([
                'vendible_id'          => null,
                'vendible_type'        => null,
                'comprador_id'         => null,

                'fecha_venta'          => $venta['fecha_venta'],
                'tipo_venta'           => 'produccion',
                'estado_venta'         => 'completada',
                'costo_total'          => 0.00,

                'producto'             => $venta['producto'],
                'cantidad'             => $venta['cantidad'],
                'unidad'               => $venta['unidad'],
                'precio_unitario'      => $venta['precio_unitario'],
                'precio_total'         => $precioTotal,

                'metodo_pago'          => $venta['metodo_pago'],
                'estado_pago'          => $venta['estado_pago'],
                'condiciones_entrega'  => $venta['condiciones_entrega'],
                'fecha_entrega'        => $venta['fecha_entrega'],

                'observaciones'        => $venta['observaciones'],
                'numero_factura'       => 'PROD-' . $hoy->format('Ymd') . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'vendedor_id'          => $vendedor->id,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. VENTAS DE ANIMALES VIVOS
        |--------------------------------------------------------------------------
        | Se venden pocos animales.
        | Se usan fechas recientes para evitar conflictos con actividades anteriores.
        | No se venden animales gestantes, lactantes, en empadre ni reproductores.
        */

        $animalesParaVenta = Animal::query()
            ->where('especie', 'Ovino')
            ->whereNotIn('estado_productivo', [
                'vendido',
                'sacrificado',
                'faeneado',
                'gestante',
                'lactancia',
                'empadre',
                'Reproductor',
                'desecho',
            ])
            ->whereIn('estado_productivo', [
                'reemplazo',
                'En crecimiento',
                'vacia',
                'mantenimiento',
            ])
            ->orderBy('fecha_nac', 'desc')
            ->limit(3)
            ->get();

        foreach ($animalesParaVenta as $index => $animal) {
            $precioUnitario = match ($animal->sexo) {
                'M' => 3200.00,
                'F' => 2800.00,
                default => 2500.00,
            };

            $fechaVenta = $hoy->copy()->subDays(3 - $index)->toDateString();

            Venta::create([
                'vendible_id'          => $animal->id,
                'vendible_type'        => Animal::class,
                'comprador_id'         => null,

                'fecha_venta'          => $fechaVenta,
                'tipo_venta'           => 'animal',
                'estado_venta'         => 'completada',
                'costo_total'          => 0.00,

                'producto'             => 'Ovino en pie - Arete ' . $animal->arete,
                'cantidad'             => 1,
                'unidad'               => 'animal',
                'precio_unitario'      => $precioUnitario,
                'precio_total'         => $precioUnitario,

                'metodo_pago'          => $index % 2 === 0 ? 'efectivo' : 'transferencia',
                'estado_pago'          => 'completado',
                'condiciones_entrega'  => 'Entrega del animal vivo en rancho.',
                'fecha_entrega'        => $fechaVenta,

                'observaciones'        => 'Venta reciente de animal ovino vivo. El animal queda marcado como vendido y no debe participar en actividades posteriores.',
                'numero_factura'       => 'ANI-' . $hoy->format('Ymd') . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'vendedor_id'          => $vendedor->id,
            ]);

            $animal->update([
                'estado_productivo' => 'vendido',
            ]);
        }

        $this->command->info('Ventas creadas correctamente.');
        $this->command->info('Ventas de productos: ' . count($ventasProductos));
        $this->command->info('Animales vendidos: ' . $animalesParaVenta->count());
    }
}