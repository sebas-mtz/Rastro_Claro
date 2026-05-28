<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sacrificio;
use App\Models\Animal;
use App\Models\Lote;
use Carbon\Carbon;

class SacrificioSeeder extends Seeder
{
    public function run(): void
    {/*
        // Obtener animales disponibles (arete >= 100 y que no estén faenados o sacrificados)
        $animales = Animal::where('arete', '>=', 100)
            ->whereNotIn('id', function($query) {
                $query->select('animal_id')->from('faenas');
            })
            ->whereNotIn('id', function($query) {
                $query->select('animal_id')->from('sacrificios');
            })
            ->where('estado_productivo', '!=', 'vendido')
            ->get();

        // Si no hay suficientes animales, crear algunos temporalmente
        if ($animales->count() < 5) {
            $this->command->warn('No hay suficientes animales. Creando animales temporales...');
            
            $lotes = Lote::inRandomOrder()->first();
            
            for ($i = 0; $i < 5; $i++) {
                $animal = Animal::create([
                    'especie' => ['Bovino', 'Porcino', 'Ovino'][$i % 3],
                    'raza' => ['Hereford', 'Pietrain', 'Dorper'][$i % 3],
                    'arete' => 300 + $i,
                    'sexo' => ['M', 'F'][$i % 2],
                    'fecha_nac' => Carbon::now()->subYears(3)->addMonths($i),
                    'peso' => rand(300, 400),
                    'BCS' => rand(20, 30) / 10,
                    'estado_productivo' => 'activo',
                    'lote_id' => $lotes->id,
                ]);
                $animales->push($animal);
            }
        }

        $lotes = Lote::inRandomOrder()->limit(2)->get();

        $sacrificios = [
            [
                'animal_id' => $animales[0]->id,
                'lote_id' => $lotes[0]->id ?? null,
                'fecha' => Carbon::now()->subDays(15),
                'motivo' => 'descarte',
                'peso_vivo' => 450.5,
                'peso_canal' => 245.8,
                'rendimiento' => 54.5,
                'cuero' => true,
                'grasa' => true,
                'visceras' => true,
                'plumas' => false,
                'observaciones' => 'Animal descartado por baja productividad'
            ],
            [
                'animal_id' => $animales[1]->id,
                'lote_id' => null,
                'fecha' => Carbon::now()->subDays(12),
                'motivo' => 'enfermedad',
                'peso_vivo' => 380.2,
                'peso_canal' => 198.6,
                'rendimiento' => 52.2,
                'cuero' => true,
                'grasa' => false,
                'visceras' => false,
                'plumas' => false,
                'observaciones' => 'Sacrificio por enfermedad crónica'
            ],
            [
                'animal_id' => $animales[2]->id,
                'lote_id' => $lotes[1]->id ?? null,
                'fecha' => Carbon::now()->subDays(9),
                'motivo' => 'accidente',
                'peso_vivo' => 420.8,
                'peso_canal' => 228.4,
                'rendimiento' => 54.3,
                'cuero' => true,
                'grasa' => true,
                'visceras' => true,
                'plumas' => false,
                'observaciones' => 'Accidente en el potrero'
            ],
            [
                'animal_id' => $animales[3]->id,
                'lote_id' => $lotes[0]->id ?? null,
                'fecha' => Carbon::now()->subDays(6),
                'motivo' => 'autoconsumo',
                'peso_vivo' => 395.7,
                'peso_canal' => 215.3,
                'rendimiento' => 54.4,
                'cuero' => true,
                'grasa' => true,
                'visceras' => true,
                'plumas' => true,
                'observaciones' => 'Para consumo de la familia'
            ],
            [
                'animal_id' => $animales[4]->id,
                'lote_id' => null,
                'fecha' => Carbon::now()->subDays(2),
                'motivo' => 'descarte',
                'peso_vivo' => 365.4,
                'peso_canal' => 192.8,
                'rendimiento' => 52.8,
                'cuero' => true,
                'grasa' => false,
                'visceras' => true,
                'plumas' => false,
                'observaciones' => 'Animal de edad avanzada'
            ]
        ];

        foreach ($sacrificios as $sacrificio) {
            Sacrificio::create($sacrificio);
            
            // Actualizar estado del animal
            $animal = Animal::find($sacrificio['animal_id']);
            $animal->update(['estado_productivo' => 'sacrificado']);
        }

        $this->command->info('5 sacrificios creados exitosamente');
        */
    }
}