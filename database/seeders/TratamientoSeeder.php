<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * TratamientosSeeder
 * ─────────────────────────────────────────────────────────────────────────
 * Genera registros en `tratamientos` para los eventos de salud
 * individuales (tipo consulta / emergencia / revision) que requirieron
 * un seguimiento terapéutico.
 *
 * Cada tratamiento se vincula al `salud_id` del evento que lo originó
 * mediante búsqueda por animal + fecha + diagnóstico.
 *
 * Estados:
 *   • completado — eventos anteriores a los últimos 30 días
 *   • activo     — eventos recientes o que siguen en curso
 *
 * IMPORTANTE: Ejecutar DESPUÉS de EventosSaludSeeder.
 * ─────────────────────────────────────────────────────────────────────────
 */
class TratamientoSeeder extends Seeder
{
    public function run(): void
    {
        $animales = DB::table('animals')->pluck('id', 'arete');
        $hoy      = Carbon::today();

        /*
         * Definición de tratamientos.
         * 'dx_fragmento' es un fragmento del campo `diagnostico` del
         * evento_salud para localizarlo sin depender del ID hardcodeado.
         *
         * duracion_dias: duración esperada del tratamiento.
         */
        $tratamientos = [

            /* ── Parasitosis ─────────────────────────────────────────── */
            [
                'arete'          => 'B17-001',
                'dx_fragmento'   => 'Haemonchus',
                'nombre'         => 'Antiparasitario — Ivermectina + Albendazol',
                'fecha_inicio'   => '2020-07-14',
                'duracion_dias'  => 14,
                'responsable'    => 'MVZ Juan Pérez',
                'notas'          => 'Administrar Ivermectina día 1. Albendazol días 1 y 14. Repetir FAMACHA a los 21 días. Retirada de leche 10 días.',
            ],
            [
                'arete'          => 'B18-003',
                'dx_fragmento'   => 'Parasitosis interna moderada',
                'nombre'         => 'Antiparasitario — Ivermectina',
                'fecha_inicio'   => '2021-08-03',
                'duracion_dias'  => 7,
                'responsable'    => 'MVZ Juan Pérez',
                'notas'          => 'Dosis única. Verificar FAMACHA en 21 días.',
            ],

            /* ── Conjuntivitis infecciosa ────────────────────────────── */
            [
                'arete'          => 'B18-001',
                'dx_fragmento'   => 'Queratoconjuntivitis infecciosa',
                'nombre'         => 'Tratamiento conjuntivitis — Oxitetraciclina tópica + LA',
                'fecha_inicio'   => '2020-04-05',
                'duracion_dias'  => 10,
                'responsable'    => 'MVZ Externo',
                'notas'          => 'Aplicar ungüento oftálmico cada 12h × 7 días. Oxitetraciclina LA dosis única IM. Aislar del rebaño los primeros 5 días.',
            ],
            [
                'arete'          => 'B20-004',
                'dx_fragmento'   => 'Queratoconjuntivitis leve',
                'nombre'         => 'Antibiótico oftálmico tópico',
                'fecha_inicio'   => '2022-05-20',
                'duracion_dias'  => 5,
                'responsable'    => 'MVZ Juan Pérez',
                'notas'          => 'Aplicación tópica cada 12h. Sin aislamiento requerido.',
            ],

            /* ── Neumonía ────────────────────────────────────────────── */
            [
                'arete'          => 'S19-001',
                'dx_fragmento'   => 'Bronconeumonía',
                'nombre'         => 'Antibioterapia respiratoria — Florfenicol',
                'fecha_inicio'   => '2019-03-01',
                'duracion_dias'  => 6,
                'responsable'    => 'MVZ Juan Pérez',
                'notas'          => 'Florfenicol 20 mg/kg IM días 1 y 3. AINE (Meloxicam) día 1. Control temperatura días 2, 4 y 6.',
            ],
            [
                'arete'          => 'B21-003',
                'dx_fragmento'   => 'Cuadro respiratorio leve',
                'nombre'         => 'Antibioterapia preventiva post-parto — Oxitetraciclina LA',
                'fecha_inicio'   => '2021-04-10',
                'duracion_dias'  => 5,
                'responsable'    => 'MVZ Juan Pérez',
                'notas'          => 'Dosis única LA. Suplementación vitamínica A/D/E por 5 días.',
            ],

            /* ── Post-cesárea ─────────────────────────────────────────── */
            [
                'arete'          => 'B22-004',
                'dx_fragmento'   => 'post-cesárea',
                'nombre'         => 'Antibioterapia y cuidado post-quirúrgico',
                'fecha_inicio'   => '2024-01-16',
                'duracion_dias'  => 7,
                'responsable'    => 'MVZ Juan Pérez',
                'notas'          => 'Amoxicilina 15 mg/kg IM cada 24h × 5 días. Limpieza de herida con clorhexidina cada 12h. Retirar puntos al día 10.',
            ],

            /* ── Post-parto distócico ────────────────────────────────── */
            [
                'arete'          => 'B18-003',
                'dx_fragmento'   => 'post-parto distócico',
                'nombre'         => 'Tratamiento post-parto distócico',
                'fecha_inicio'   => '2019-01-16',
                'duracion_dias'  => 3,
                'responsable'    => 'MVZ Externo',
                'notas'          => 'Oxitocina 5 UI IM dosis única. Antiséptico intrauterino días 1 y 3. Observar descarga vaginal.',
            ],

            /* ── Pododermatitis ──────────────────────────────────────── */
            [
                'arete'          => 'S18-001',
                'dx_fragmento'   => 'pododermatitis',
                'nombre'         => 'Tratamiento podal — Penicilina + pediluvio',
                'fecha_inicio'   => '2021-10-12',
                'duracion_dias'  => 10,
                'responsable'    => 'MVZ Juan Pérez',
                'notas'          => 'Penicilina G IM días 1–5. Pediluvio sulfato de zinc 10% cada 3 días. Mantener en zona seca. Revisar casco al día 7.',
            ],
            [
                'arete'          => 'B20-001',
                'dx_fragmento'   => 'Cojera leve',
                'nombre'         => 'AINE + reposo',
                'fecha_inicio'   => '2023-06-05',
                'duracion_dias'  => 5,
                'responsable'    => 'MVZ Juan Pérez',
                'notas'          => 'Meloxicam 0.5 mg/kg SC días 1 y 3. Corral seco durante tratamiento.',
            ],
        ];

        foreach ($tratamientos as $t) {
            $animalId = $animales[$t['arete']] ?? null;
            if (!$animalId) continue;

            // Localizar el evento_salud que originó este tratamiento
            $evento = DB::table('eventos_salud')
                ->where('animal_id', $animalId)
                ->where('fecha_programada', $t['fecha_inicio'])
                ->where('diagnostico', 'like', '%' . $t['dx_fragmento'] . '%')
                ->first();

            $fechaInicio = Carbon::parse($t['fecha_inicio']);
            $fechaFin    = $fechaInicio->copy()->addDays($t['duracion_dias']);
            $estado      = 'activo';

            DB::table('tratamientos')->insert([
                'animal_id'   => $animalId,
                'nombre'      => $t['nombre'],
                'fecha_inicio'=> $fechaInicio->toDateString(),
                'fecha_fin'   => $fechaFin->toDateString(),
                'estado'      => $estado,
                'salud_id'    => $evento?->id,
                'responsable' => $t['responsable'],
                'notas'       => $t['notas'],
                'user_id'     => null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}