<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Animal;
use App\Models\Produccion;
use Carbon\Carbon;

class ProduccionSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Seeder pensado para ejecutarse DESPUÉS de:
         * 1. AnimalSeeder
         * 2. ReproduccionSeeder, si lo usas
         *
         * Solo genera producción para OVINOS.
         * - Lana: puede generarse en machos y hembras adultas.
         * - Leche: SOLO hembras ovinas seleccionadas como lactancia.
         * - Nunca genera leche para machos.
         */

        $hoy = Carbon::now();

        // Hembras elegidas para simular que actualmente están en lactancia.
        // Todas deben existir en AnimalSeeder y ser sexo F.
        $ovejasEnLactancia = [
            'B21-004', // Oliva
            'B21-005', // Mirra
            'B22-001', // Dulce
            'B22-002', // Valeria
            'B22-004', // Mirto
            'B22-005', // Nimfa
            'B22-006', // Naiara
            'B22-007', // Miriam
        ];

        $animales = Animal::query()
            ->where('especie', 'Ovino')
            ->get();

        if ($animales->isEmpty()) {
            $this->command?->warn('No hay animales ovinos para generar producción. Ejecuta primero AnimalSeeder.');
            return;
        }

        // Evita duplicados si vuelves a correr el seeder.
        Produccion::query()
            ->whereIn('animal_id', $animales->pluck('id'))
            ->delete();

        // Actualiza únicamente las hembras seleccionadas como lactancia.
        // La condición sexo = F evita que un macho quede como lactancia por error.
        Animal::query()
            ->where('especie', 'Ovino')
            ->where('sexo', 'F')
            ->whereIn('arete', $ovejasEnLactancia)
            ->update(['estado_productivo' => 'lactancia']);

        // Recargamos animales después de actualizar estados.
        $animales = Animal::query()
            ->where('especie', 'Ovino')
            ->get();

        foreach ($animales as $animal) {
            // 1) Producción de leche: SOLO hembras ovinas en lactancia.
            if ($this->puedeProducirLeche($animal, $ovejasEnLactancia)) {
                $this->crearProduccionLeche($animal, $hoy);
            }

            // 2) Producción de lana: ovinos adultos o de reemplazo con edad suficiente.
            if ($this->puedeProducirLana($animal)) {
                $this->crearProduccionLana($animal, $hoy);
            }
        }

        $this->command?->info('Producción ovina creada correctamente: leche solo para hembras en lactancia y lana para ovinos permitidos.');
    }

    private function puedeProducirLeche(Animal $animal, array $ovejasEnLactancia): bool
    {
        return $animal->especie === 'Ovino'
            && $animal->sexo === 'F'
            && in_array($animal->arete, $ovejasEnLactancia, true)
            && $animal->estado_productivo === 'lactancia';
    }

    private function puedeProducirLana(Animal $animal): bool
    {
        if ($animal->especie !== 'Ovino') {
            return false;
        }

        // Crías muy jóvenes todavía no se consideran para esquila productiva.
        if ($animal->estado_productivo === 'En crecimiento') {
            return false;
        }

        // Estados donde todavía puede tener producción de lana.
        return in_array($animal->estado_productivo, [
            'Reproductor',
            'reemplazo',
            'mantenimiento',
            'vacia',
            'empadre',
            'gestante',
            'lactancia',
        ], true);
    }

    private function crearProduccionLeche(Animal $animal, Carbon $hoy): void
    {
        // Producción diaria simulada de los últimos 30 días.
        // En ovinos de leche, se maneja en litros por día.
        $base = $this->randomFloat(0.7, 2.4);
        $tendencia = random_int(-1, 1); // -1 baja, 0 estable, 1 sube

        for ($d = 29; $d >= 0; $d--) {
            // Pequeña probabilidad de día sin registro/ordeña.
            if (random_int(1, 100) <= 8) {
                continue;
            }

            $factorDia = 1 + ($tendencia * 0.006 * (29 - $d));
            $variacion = $this->randomFloat(0.88, 1.12);
            $valor = round(max(0.2, $base * $factorDia * $variacion), 2);

            Produccion::create([
                'animal_id' => $animal->id,
                'fecha'     => $hoy->copy()->subDays($d)->toDateString(),
                'tipo'      => 'leche',
                'valor'     => $valor,
                'unidad'    => 'litros',
            ]);
        }
    }

    private function crearProduccionLana(Animal $animal, Carbon $hoy): void
    {
        // La lana no debería registrarse como producción diaria intensa.
        // Se simulan 3 esquilas/recortes recientes para reportes históricos.
        $fechas = [
            $hoy->copy()->subMonths(8),
            $hoy->copy()->subMonths(4),
            $hoy->copy()->subDays(20),
        ];

        foreach ($fechas as $fecha) {
            $valor = $this->valorLanaSegunAnimal($animal);

            Produccion::create([
                'animal_id' => $animal->id,
                'fecha'     => $fecha->toDateString(),
                'tipo'      => 'lana',
                'valor'     => $valor,
                'unidad'    => 'kg',
            ]);
        }
    }

    private function valorLanaSegunAnimal(Animal $animal): float
    {
        $peso = (float) ($animal->peso ?? 40);

        // Machos adultos suelen dar más volumen por tamaño corporal.
        if ($animal->sexo === 'M' && $animal->estado_productivo === 'Reproductor') {
            return $this->randomFloat(2.4, 4.2);
        }

        // Reemplazos/jóvenes seleccionados producen menos.
        if ($animal->estado_productivo === 'reemplazo' || $peso < 42) {
            return $this->randomFloat(0.8, 1.8);
        }

        // Hembras adultas.
        return $this->randomFloat(1.5, 3.2);
    }

    private function randomFloat(float $min, float $max): float
    {
        return round($min + mt_rand() / mt_getrandmax() * ($max - $min), 2);
    }
}
