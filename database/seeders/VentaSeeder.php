<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Venta;
use App\Models\Animal;
use App\Models\Produccion;
use App\Models\Faena;
use App\Models\Comprador;
use App\Models\User;
use Carbon\Carbon;

class VentaSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (!$user) {
            $this->command->error('No hay usuarios en la base de datos. Ejecuta UserSeeder primero.');
            return;
        }
        
        // Obtener o crear compradores
        $compradores = Comprador::all();
        if ($compradores->isEmpty()) {
            $this->command->warn('No hay compradores. Creando compradores de prueba...');
            
            $compradores = [
                Comprador::create([
                    'nombre' => 'Juan Pérez',
                    'tipo' => 'particular',
                    'telefono' => '555-0101',
                    'email' => 'juan@email.com',
                    'direccion' => 'Calle Principal 123'
                ]),
                Comprador::create([
                    'nombre' => 'María González',
                    'tipo' => 'empresa',
                    'telefono' => '555-0102',
                    'email' => 'maria@email.com',
                    'direccion' => 'Avenida Central 456'
                ]),
                Comprador::create([
                    'nombre' => 'Lacteos S.A.',
                    'tipo' => 'empresa', 
                    'telefono' => '555-0103',
                    'email' => 'ventas@lacteos.com',
                    'direccion' => 'Zona Industrial 789'
                ]),
                Comprador::create([
                    'nombre' => 'Textiles del Norte',
                    'tipo' => 'empresa',
                    'telefono' => '555-0104',
                    'email' => 'info@textilesnorte.com',
                    'direccion' => 'Polígono Industrial Norte'
                ]),
                Comprador::create([
                    'nombre' => 'Supermercado Central',
                    'tipo' => 'empresa',
                    'telefono' => '555-0105',
                    'email' => 'compras@supercentral.com',
                    'direccion' => 'Centro Comercial Plaza'
                ]),
                Comprador::create([
                    'nombre' => 'Carnicería El Buen Corte',
                    'tipo' => 'comercio',
                    'telefono' => '555-0106',
                    'email' => 'pedidos@buencorte.com',
                    'direccion' => 'Mercado Municipal 321'
                ]),
                Comprador::create([
                    'nombre' => 'Curtiembre Industrial',
                    'tipo' => 'empresa',
                    'telefono' => '555-0107',
                    'email' => 'produccion@curtiduria.com',
                    'direccion' => 'Parque Industrial Sur'
                ])
            ];
        }

        $fechaBase = Carbon::now();
        $todasVentas = [];

        // 1. Venta de Animal (2 ventas) - Solo si hay animales disponibles
        $animalesDisponibles = Animal::where('arete', '>=', 100)
            ->whereNotIn('id', function($query) {
                $query->select('animal_id')->from('faenas');
            })
            ->whereNotIn('id', function($query) {
                $query->select('animal_id')->from('sacrificios');
            })
            ->whereNotIn('id', function($query) {
                $query->select('vendible_id')->from('ventas')->where('vendible_type', 'App\Models\Animal');
            })
            ->where('estado_productivo', '!=', 'vendido')
            ->get();

        if ($animalesDisponibles->count() >= 2) {
            $ventasAnimales = [
                [
                    'vendible_type' => 'App\Models\Animal',
                    'vendible_id' => $animalesDisponibles[0]->id,
                    'comprador_id' => $compradores[0]->id,
                    'fecha_venta' => $fechaBase->copy()->subDays(7),
                    'tipo_venta' => 'animal',
                    'estado_venta' => 'completada',
                    'producto' => 'Bovino - ' . ($animalesDisponibles[0]->alias ?? $animalesDisponibles[0]->arete),
                    'cantidad' => 1,
                    'unidad' => 'unidad',
                    'precio_unitario' => 850.00,
                    'precio_total' => 850.00,
                    'metodo_pago' => 'efectivo',
                    'estado_pago' => 'completado',
                    'numero_factura' => 'FACT-' . $fechaBase->format('Ymd') . '-001',
                    'vendedor_id' => $user->id,
                    'observaciones' => 'Venta de animal en pie'
                ],
                [
                    'vendible_type' => 'App\Models\Animal',
                    'vendible_id' => $animalesDisponibles[1]->id,
                    'comprador_id' => $compradores[1]->id,
                    'fecha_venta' => $fechaBase->copy()->subDays(5),
                    'tipo_venta' => 'animal',
                    'estado_venta' => 'completada',
                    'producto' => 'Porcino - ' . ($animalesDisponibles[1]->alias ?? $animalesDisponibles[1]->arete),
                    'cantidad' => 1,
                    'unidad' => 'unidad',
                    'precio_unitario' => 620.00,
                    'precio_total' => 620.00,
                    'metodo_pago' => 'transferencia',
                    'estado_pago' => 'completado',
                    'numero_factura' => 'FACT-' . $fechaBase->format('Ymd') . '-002',
                    'vendedor_id' => $user->id,
                    'observaciones' => 'Animal para engorda'
                ]
            ];
            $todasVentas = array_merge($todasVentas, $ventasAnimales);
            $this->command->info('2 ventas de animales preparadas');
        } else {
            $this->command->warn('No hay suficientes animales disponibles para ventas');
        }

        // 2. Venta de Producción (3 ventas) - Solo si hay producciones disponibles
        $produccionesDisponibles = Produccion::whereDoesntHave('ventas')
            ->orWhereHas('ventas', function($query) {
                $query->where('estado_venta', '!=', 'completada');
            })
            ->inRandomOrder()
            ->limit(3)
            ->get();

        if ($produccionesDisponibles->count() >= 3) {
            $ventasProducciones = [
                [
                    'vendible_type' => 'App\Models\Produccion',
                    'vendible_id' => $produccionesDisponibles[0]->id,
                    'comprador_id' => $compradores[2]->id,
                    'fecha_venta' => $fechaBase->copy()->subDays(4),
                    'tipo_venta' => 'produccion',
                    'estado_venta' => 'completada',
                    'producto' => 'Leche Fresca',
                    'cantidad' => 45.5,
                    'unidad' => 'litros',
                    'precio_unitario' => 1.20,
                    'precio_total' => 54.60,
                    'metodo_pago' => 'transferencia',
                    'estado_pago' => 'completado',
                    'numero_factura' => 'FACT-' . $fechaBase->format('Ymd') . '-003',
                    'vendedor_id' => $user->id,
                    'observaciones' => 'Venta de leche fresca'
                ],
                [
                    'vendible_type' => 'App\Models\Produccion',
                    'vendible_id' => $produccionesDisponibles[1]->id,
                    'comprador_id' => $compradores[3]->id,
                    'fecha_venta' => $fechaBase->copy()->subDays(3),
                    'tipo_venta' => 'produccion',
                    'estado_venta' => 'completada',
                    'producto' => 'Lana Ovina',
                    'cantidad' => 18.3,
                    'unidad' => 'kg',
                    'precio_unitario' => 8.50,
                    'precio_total' => 155.55,
                    'metodo_pago' => 'cheque',
                    'estado_pago' => 'completado',
                    'numero_factura' => 'FACT-' . $fechaBase->format('Ymd') . '-004',
                    'vendedor_id' => $user->id,
                    'observaciones' => 'Lana de primera calidad'
                ],
                [
                    'vendible_type' => 'App\Models\Produccion',
                    'vendible_id' => $produccionesDisponibles[2]->id,
                    'comprador_id' => $compradores[4]->id,
                    'fecha_venta' => $fechaBase->copy()->subDays(2),
                    'tipo_venta' => 'produccion',
                    'estado_venta' => 'completada',
                    'producto' => 'Huevos Frescos',
                    'cantidad' => 120,
                    'unidad' => 'unidades',
                    'precio_unitario' => 0.35,
                    'precio_total' => 42.00,
                    'metodo_pago' => 'efectivo',
                    'estado_pago' => 'completado',
                    'numero_factura' => 'FACT-' . $fechaBase->format('Ymd') . '-005',
                    'vendedor_id' => $user->id,
                    'observaciones' => 'Huevos frescos'
                ]
            ];
            $todasVentas = array_merge($todasVentas, $ventasProducciones);
            $this->command->info('3 ventas de producciones preparadas');
        } else {
            $this->command->warn('No hay suficientes producciones disponibles para ventas');
        }

        // 3. Venta de Subproductos de Faena (2 ventas) - Solo si hay faenas disponibles
        $faenasDisponibles = Faena::inRandomOrder()->limit(2)->get();

        if ($faenasDisponibles->count() >= 2) {
            $ventasSubproductos = [
                [
                    'vendible_type' => 'App\Models\Faena',
                    'vendible_id' => $faenasDisponibles[0]->id,
                    'comprador_id' => $compradores[5]->id,
                    'fecha_venta' => $fechaBase->copy()->subDays(6),
                    'tipo_venta' => 'subproducto_faena',
                    'estado_venta' => 'completada',
                    'producto' => 'Carne de Res',
                    'cantidad' => 25.5,
                    'unidad' => 'kg',
                    'precio_unitario' => 12.80,
                    'precio_total' => 326.40,
                    'metodo_pago' => 'efectivo',
                    'estado_pago' => 'completado',
                    'numero_factura' => 'FACT-' . $fechaBase->format('Ymd') . '-006',
                    'vendedor_id' => $user->id,
                    'observaciones' => 'Carne de res premium'
                ],
                [
                    'vendible_type' => 'App\Models\Faena',
                    'vendible_id' => $faenasDisponibles[1]->id,
                    'comprador_id' => $compradores[6]->id,
                    'fecha_venta' => $fechaBase->copy()->subDays(1),
                    'tipo_venta' => 'subproducto_faena',
                    'estado_venta' => 'completada',
                    'producto' => 'Cuero Bovino',
                    'cantidad' => 8.2,
                    'unidad' => 'kg',
                    'precio_unitario' => 6.50,
                    'precio_total' => 53.30,
                    'metodo_pago' => 'transferencia',
                    'estado_pago' => 'completado',
                    'numero_factura' => 'FACT-' . $fechaBase->format('Ymd') . '-007',
                    'vendedor_id' => $user->id,
                    'observaciones' => 'Cuero bovino curtido'
                ]
            ];
            $todasVentas = array_merge($todasVentas, $ventasSubproductos);
            $this->command->info('2 ventas de subproductos preparadas');
        } else {
            $this->command->warn('No hay suficientes faenas disponibles para ventas');
        }

        // Crear las ventas
        if (empty($todasVentas)) {
            $this->command->error('No se pudieron crear ventas. No hay datos suficientes.');
            return;
        }

        foreach ($todasVentas as $venta) {
            Venta::create($venta);
            
            // Actualizar estado del animal si es venta de animal
            if ($venta['vendible_type'] === 'App\Models\Animal') {
                $animal = Animal::find($venta['vendible_id']);
                if ($animal) {
                    $animal->update(['estado_productivo' => 'vendido']);
                }
            }
        }

        $this->command->info(count($todasVentas) . ' ventas creadas exitosamente');
    }
}