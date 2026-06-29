<?php

namespace Database\Seeders;



use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{



    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            LoteSeeder::class,
            DonadoresExternosSeeder::class,
            TermoSeeder::class,
            PajillaSeeder::class,
            AnimalSeeder::class,
            ReproduccionSeeder::class,
            PesajeSeeder::class,
            VacunaSeeder::class,
            EventoSaludSeeder::class,
            TratamientoSeeder::class,
            AlimentacionSeeder::class,
            ProduccionSeeder::class,
            FaenaSeeder::class,      // ✅ Nuevo
            SacrificioSeeder::class, // ✅ Nuevo
            VentaSeeder::class,      // ✅ Nuevo
        ]);
    }
}