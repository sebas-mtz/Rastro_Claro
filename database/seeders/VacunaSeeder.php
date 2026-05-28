<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * VacunasSeeder
 * ─────────────────────────────────────────────────────────────────────────
 * Catálogo de vacunas para ovinos usadas en México.
 * Sin dependencias externas; debe correr ANTES de EventosSaludSeeder.
 * ─────────────────────────────────────────────────────────────────────────
 */
class VacunaSeeder extends Seeder
{
    public function run(): void
    {
        $vacunas = [
            [
                'nombre'           => 'Covexin-8',
                'patogeno'         => 'Clostridium perfringens tipos A/B/C/D, C. novyi, C. septicum, C. tetani, C. haemolyticum',
                'pauta'            => 'Dosis inicial + refuerzo a las 4 semanas; luego anual. En gestantes: 4 semanas antes del parto.',
                'refuerzo_dias'    => 365,
                'especie_objetivo' => 'Ovino, Caprino',
            ],
            [
                'nombre'           => 'Lambivac',
                'patogeno'         => 'Clostridium perfringens tipo D (Enterotoxemia), C. tetani',
                'pauta'            => 'Dosis inicial + refuerzo a las 3–4 semanas; después cada 6 meses.',
                'refuerzo_dias'    => 180,
                'especie_objetivo' => 'Ovino, Caprino',
            ],
            [
                'nombre'           => 'Ectimavax',
                'patogeno'         => 'Parapoxvirus (Ectima contagioso)',
                'pauta'            => 'Dosis única intradermal en la axila. Anual antes de la época húmeda.',
                'refuerzo_dias'    => 365,
                'especie_objetivo' => 'Ovino',
            ],
            [
                'nombre'           => 'Brucella Rev-1',
                'patogeno'         => 'Brucella melitensis',
                'pauta'            => 'Dosis única en corderas de 3–6 meses. No aplicar en gestantes ni machos.',
                'refuerzo_dias'    => null,    // dosis única de por vida
                'especie_objetivo' => 'Ovino hembra (corderas 3–6 meses)',
            ],
            [
                'nombre'           => 'Bovipast RSP',
                'patogeno'         => 'Pasteurella multocida, Mannheimia haemolytica, BRSV, Pi3',
                'pauta'            => 'Dosis inicial + refuerzo a las 4 semanas; luego anual antes de la época fría.',
                'refuerzo_dias'    => 365,
                'especie_objetivo' => 'Ovino, Bovino',
            ],
            [
                'nombre'           => 'Vacuna Antiaftosa',
                'patogeno'         => 'Virus de la Fiebre Aftosa (cepas O, A, C)',
                'pauta'            => 'Bianual obligatoria en zonas de riesgo. Campaña oficial SENASICA.',
                'refuerzo_dias'    => 180,
                'especie_objetivo' => 'Ovino, Bovino, Caprino, Porcino',
            ],
            [
                'nombre'           => 'Toxoide Tetánico',
                'patogeno'         => 'Clostridium tetani',
                'pauta'            => 'Dosis inicial + refuerzo a las 4 semanas; luego anual. Obligatoria pre-castración.',
                'refuerzo_dias'    => 365,
                'especie_objetivo' => 'Ovino, Caprino, Bovino',
            ],
            [
                'nombre'           => 'Pestigard',
                'patogeno'         => 'Virus de la Diarrea Viral Bovina / Border Disease (BDV)',
                'pauta'            => 'Dos dosis con 3–4 semanas de diferencia; refuerzo anual antes del empadre.',
                'refuerzo_dias'    => 365,
                'especie_objetivo' => 'Ovino, Bovino',
            ],
        ];

        foreach ($vacunas as $v) {
            DB::table('vacunas')->insert(array_merge($v, [
                'user_id'    => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}