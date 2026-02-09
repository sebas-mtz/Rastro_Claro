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
            AnimalSeeder::class,
            ProduccionSeeder::class,
            FaenaSeeder::class,      // ✅ Nuevo
            SacrificioSeeder::class, // ✅ Nuevo
            VentaSeeder::class,      // ✅ Nuevo
        ]);
    }
}