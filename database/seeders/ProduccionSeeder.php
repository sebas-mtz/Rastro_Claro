<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\Lote;
use App\Models\Animal;
use App\Models\Produccion;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ProduccionSeeder extends Seeder
{
    public function run()



    {
        $hoy = Carbon::now();
        $ayer = $hoy->subDay();

        $lotesData = [
            ['nombre' => 'Lote Vacas', 'especie' => 'Bovino', 'raza' => 'Holstein', 'areteStart' => 11, 'producciones' => ['leche'], 'estados' => ["Lactante","Gestante","En crecimiento"], 'irregularidad' => 20],
            ['nombre' => 'Lote Borregas', 'especie' => 'Ovino', 'raza' => 'Merino', 'areteStart' => 21, 'producciones' => ['lana'], 'estados' => ["Gestante","En crecimiento","Reproductor"], 'irregularidad' => 15],
            ['nombre' => 'Lote Gallinas', 'especie' => 'Aves de corral (gallinas y pollitos)', 'raza' => 'Leghorn', 'areteStart' => 31, 'producciones' => ['huevo'], 'estados' => ["Postura","En crecimiento","En descanso"], 'irregularidad' => 25],
            ['nombre' => 'Lote Cabras', 'especie' => 'Caprino', 'raza' => 'Saanen', 'areteStart' => 38, 'producciones' => ['leche'], 'estados' => ["Lactante","Gestante","En crecimiento"], 'irregularidad' => 15],
            // Los cerdos se eliminan porque no tienen producciones diarias
        ];

        $sexos = ['M','F'];

        foreach ($lotesData as $loteInfo) {
            $lote = Lote::create([
                'nombre' => $loteInfo['nombre'],
                'corral_potrero' => $loteInfo['nombre'] . ' Corral',
                'descripcion' => 'Lote de ' . $loteInfo['especie'] . ' raza ' . $loteInfo['raza'],
                'responsable_id' => 1,
            ]);

            $arete = $loteInfo['areteStart'];

            for ($i = 0; $i < 7; $i++) {
                $estado = $loteInfo['estados'][$i % count($loteInfo['estados'])];

                $animal = Animal::create([
                    'arete' => $arete++,
                    'peso' => rand(20,200),
                    'fecha_nac' => Carbon::now()->subYears(rand(1,6))->subMonths(rand(0,12)),
                    'especie' => $loteInfo['especie'],
                    'raza' => $loteInfo['raza'],
                    'sexo' => $sexos[array_rand($sexos)],
                    'estado_productivo' => $estado,
                    'lote_id' => $lote->id,
                ]);

                // Definir tendencia: 0=estable, 1=sube, 2=baja
                $tendencia = rand(0,2);

                for ($d = 7; $d >= 1; $d--) {
                    $fechaProduccion = Carbon::now()->subDays($d);

                    // Irregularidades por especie
                    if(rand(1,100) <= $loteInfo['irregularidad']){
                        continue; // día sin producción
                    }

                    foreach ($loteInfo['producciones'] as $tipo) {
                        switch($tipo){
                            case 'leche': 
                                $min=5; $max=15; $unidad='litros'; 
                                break;
                            case 'lana': 
                                $min=1; $max=3; $unidad='kg'; 
                                break;
                            case 'huevo': 
                                $min=3; $max=12; $unidad='unidades'; 
                                break;
                            default: 
                                $min=1; $max=10; $unidad='unidades';
                        }

                        // Ajuste por estado productivo
                        switch($estado){
                            case 'En crecimiento': 
                                $factor = rand(0,50)/100; 
                                break;
                            case 'Gestante': 
                                $factor = rand(50,80)/100; 
                                break;
                            case 'Lactante':
                            case 'Postura': 
                                $factor = rand(80,100)/100; 
                                break;
                            case 'Vaca seca':
                            case 'Reproductor':
                            case 'En descanso':
                            case 'En entrenamiento': 
                                $factor = rand(0,20)/100; 
                                break;
                            default: 
                                $factor = 1;
                        }

                        $valorBase = rand($min*100,$max*100)/100 * $factor;

                        // Aplicar tendencia
                        switch($tendencia){
                            case 1: 
                                $valor = $valorBase * (1 + 0.05 * (7-$d)); 
                                break; // sube
                            case 2: 
                                $valor = $valorBase * (1 - 0.05 * (7-$d)); 
                                break; // baja
                            default: 
                                $valor = $valorBase; // estable
                        }

                        $valor = round($valor,2);

                        if($valor > 0){
                            Produccion::create([
                                'animal_id' => $animal->id,
                                'fecha' => $fechaProduccion,
                                'tipo' => $tipo,
                                'valor' => $valor,
                                'unidad' => $unidad,
                            ]);
                        }
                    }
                }
            }
        }

        $this->command->info('Producciones diarias creadas: Leche, Huevo, Lana');
        $this->command->info('Productos de faena (carne, grasa, cuero, plumas, canal) se manejarán posteriormente');
    }
}