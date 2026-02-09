<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Faena;
use App\Models\Animal;
use App\Models\Lote;
use Carbon\Carbon;

class FaenaSeeder extends Seeder
{
    public function run(): void
    {
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
                    'raza' => ['Angus', 'Duroc', 'Merino'][$i % 3],
                    'arete' => 200 + $i,
                    'sexo' => ['M', 'F'][$i % 2],
                    'fecha_nac' => Carbon::now()->subYears(2)->addMonths($i),
                    'peso' => rand(350, 450),
                    'BCS' => rand(25, 35) / 10,
                    'estado_productivo' => 'activo',
                    'lote_id' => $lotes->id,
                ]);
                $animales->push($animal);
            }
        }

        $lotes = Lote::inRandomOrder()->limit(2)->get();

        $faenas = [
            [
                'animal_id' => $animales[0]->id,
                'lote_id' => $lotes[0]->id ?? null,
                'fecha' => Carbon::now()->subDays(10),
                'tipo_corte' => 'completo',
                'peso_canal' => 85.5,
                'peso_carne' => 52.3,
                'peso_cuero' => 8.2,
                'peso_grasa' => 5.1,
                'peso_plumas' => null,
                'peso_hueso' => 12.8,
                'peso_visceras' => 6.2,
                'rendimiento' => 61.2,
                'observaciones' => 'Faena completa de bovino, buena calidad de carne'
            ],
            [
                'animal_id' => $animales[1]->id,
                'lote_id' => $lotes[1]->id ?? null,
                'fecha' => Carbon::now()->subDays(8),
                'tipo_corte' => 'media',
                'peso_canal' => 42.8,
                'peso_carne' => 25.6,
                'peso_cuero' => 4.1,
                'peso_grasa' => 2.8,
                'peso_plumas' => null,
                'peso_hueso' => 6.4,
                'peso_visceras' => 3.2,
                'rendimiento' => 59.8,
                'observaciones' => 'Media res para cortes específicos'
            ],
            [
                'animal_id' => $animales[2]->id,
                'lote_id' => null,
                'fecha' => Carbon::now()->subDays(5),
                'tipo_corte' => 'completo',
                'peso_canal' => 78.3,
                'peso_carne' => 47.8,
                'peso_cuero' => 7.5,
                'peso_grasa' => 4.6,
                'peso_plumas' => null,
                'peso_hueso' => 11.2,
                'peso_visceras' => 5.8,
                'rendimiento' => 61.0,
                'observaciones' => 'Animal de buen rendimiento'
            ],
            [
                'animal_id' => $animales[3]->id,
                'lote_id' => $lotes[0]->id ?? null,
                'fecha' => Carbon::now()->subDays(3),
                'tipo_corte' => 'cortes',
                'peso_canal' => 91.2,
                'peso_carne' => 56.1,
                'peso_cuero' => 8.8,
                'peso_grasa' => 5.5,
                'peso_plumas' => 1.2,
                'peso_hueso' => 13.5,
                'peso_visceras' => 6.5,
                'rendimiento' => 61.5,
                'observaciones' => 'Ave con buen rendimiento de plumas'
            ],
            [
                'animal_id' => $animales[4]->id,
                'lote_id' => $lotes[1]->id ?? null,
                'fecha' => Carbon::now()->subDays(1),
                'tipo_corte' => 'deshuesado',
                'peso_canal' => 88.7,
                'peso_carne' => 54.2,
                'peso_cuero' => 8.4,
                'peso_grasa' => 5.2,
                'peso_plumas' => null,
                'peso_hueso' => 13.1,
                'peso_visceras' => 6.3,
                'rendimiento' => 61.1,
                'observaciones' => 'Carne deshuesada para filetes'
            ]
        ];

        foreach ($faenas as $faena) {
            Faena::create($faena);
            
            // Actualizar estado del animal
            $animal = Animal::find($faena['animal_id']);
            $animal->update(['estado_productivo' => 'faenado']);
        }

        $this->command->info('5 faenas creadas exitosamente');
    }
}