<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventoSaludSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Limpiar tabla para evitar duplicados al volver a ejecutar el seeder
        |--------------------------------------------------------------------------
        */
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('eventos_salud')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $animales = DB::table('animals')
            ->select('id', 'arete', 'sexo', 'fecha_nac')
            ->orderBy('id')
            ->get();

        $vacunas = DB::table('vacunas')->pluck('id', 'nombre');

        if ($animales->isEmpty()) {
            return;
        }

        $this->crearVacunacionesPendientes($animales, $vacunas);
        $this->crearConsultasPendientes($animales);
        $this->crearRevisionesPendientes($animales);
        $this->crearEmergenciasPendientes($animales);
    }

    /*
    |--------------------------------------------------------------------------
    | Vacunaciones pendientes
    |--------------------------------------------------------------------------
    */
    private function crearVacunacionesPendientes(object $animales, object $vacunas): void
    {
        $hoy = Carbon::today();

        $campanias = [
            [
                'nombre_vacuna'  => 'Covexin-8',
                'diagnostico'    => 'Campaña preventiva contra clostridiosis',
                'tratamiento'    => 'Vacunación programada. Pendiente de aplicación.',
                'dosis'          => '2 ml SC',
                'responsable'    => 'MVZ Externo',
                'observaciones'  => 'Aplicar dentro de la campaña sanitaria del rebaño.',
                'dias'           => 2,
            ],
            [
                'nombre_vacuna'  => 'Lambivac',
                'diagnostico'    => 'Prevención de enterotoxemia',
                'tratamiento'    => 'Vacunación semestral programada. Pendiente de aplicación.',
                'dosis'          => '1 ml SC',
                'responsable'    => 'Encargado sanitario',
                'observaciones'  => 'Revisar condición general antes de aplicar.',
                'dias'           => 4,
            ],
            [
                'nombre_vacuna'  => 'Vacuna Antiaftosa',
                'diagnostico'    => 'Campaña preventiva contra fiebre aftosa',
                'tratamiento'    => 'Vacunación preventiva pendiente.',
                'dosis'          => '2 ml IM',
                'responsable'    => 'MVZ SENASICA',
                'observaciones'  => 'Registrar lote de vacuna al momento de aplicación.',
                'dias'           => 6,
            ],
            [
                'nombre_vacuna'  => 'Ectimavax',
                'diagnostico'    => 'Prevención de ectima contagioso',
                'tratamiento'    => 'Aplicación intradérmica programada. Pendiente.',
                'dosis'          => '0.1 ml intradérmico',
                'responsable'    => 'MVZ Externo',
                'observaciones'  => 'Aplicar solo a animales clínicamente sanos.',
                'dias'           => 8,
            ],
            [
                'nombre_vacuna'  => 'Brucella Rev-1',
                'diagnostico'    => 'Prevención de brucelosis en hembras jóvenes',
                'tratamiento'    => 'Dosis única programada. Pendiente de aplicación.',
                'dosis'          => '2 ml SC',
                'responsable'    => 'MVZ Externo',
                'observaciones'  => 'Aplicar únicamente si corresponde por edad y sexo.',
                'dias'           => 10,
            ],
        ];

        foreach ($animales->take(10) as $index => $animal) {
            $campania = $campanias[$index % count($campanias)];

            $vacunaId = $vacunas[$campania['nombre_vacuna']] ?? null;

            $this->insertarEvento([
                'animal_id'        => $animal->id,
                'fecha_programada' => $hoy->copy()->addDays($campania['dias'])->toDateString(),
                'diagnostico'      => $campania['diagnostico'],
                'tratamiento'      => $campania['tratamiento'],
                'vacuna_id'        => $vacunaId,
                'dosis'            => $campania['dosis'],
                'tipo'             => 'vacunacion',
                'responsable'      => $campania['responsable'],
                'lote_vacuna'      => 'VAC-' . now()->format('Y') . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'observaciones'    => $campania['observaciones'],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Consultas pendientes
    |--------------------------------------------------------------------------
    */
    private function crearConsultasPendientes(object $animales): void
    {
        $hoy = Carbon::today();

        $consultas = [
            [
                'diagnostico'   => 'Sospecha de parasitosis interna leve',
                'tratamiento'   => 'Valoración clínica y posible desparasitación. Pendiente de confirmar.',
                'responsable'   => 'MVZ Juan Pérez',
                'observaciones' => 'Revisar mucosas, condición corporal y realizar evaluación FAMACHA.',
                'dias'          => 1,
            ],
            [
                'diagnostico'   => 'Disminución de apetito',
                'tratamiento'   => 'Revisión general y ajuste alimenticio si es necesario. Pendiente.',
                'responsable'   => 'Encargado sanitario',
                'observaciones' => 'Observar consumo de alimento y comportamiento durante 24 horas.',
                'dias'          => 3,
            ],
            [
                'diagnostico'   => 'Posible infección ocular',
                'tratamiento'   => 'Limpieza ocular y valoración para antibiótico tópico. Pendiente.',
                'responsable'   => 'MVZ Externo',
                'observaciones' => 'Evitar contacto con polvo y revisar ambos ojos.',
                'dias'          => 5,
            ],
            [
                'diagnostico'   => 'Pérdida ligera de condición corporal',
                'tratamiento'   => 'Evaluar dieta, hidratación y presencia de parásitos. Pendiente.',
                'responsable'   => 'MVZ Juan Pérez',
                'observaciones' => 'Registrar peso y comparar con último pesaje.',
                'dias'          => 7,
            ],
            [
                'diagnostico'   => 'Revisión por tos ocasional',
                'tratamiento'   => 'Auscultación y monitoreo respiratorio. Pendiente.',
                'responsable'   => 'MVZ Externo',
                'observaciones' => 'Separar si presenta fiebre o secreción nasal.',
                'dias'          => 9,
            ],
        ];

        foreach ($animales->slice(10, 8)->values() as $index => $animal) {
            $consulta = $consultas[$index % count($consultas)];

            $this->insertarEvento([
                'animal_id'        => $animal->id,
                'fecha_programada' => $hoy->copy()->addDays($consulta['dias'])->toDateString(),
                'diagnostico'      => $consulta['diagnostico'],
                'tratamiento'      => $consulta['tratamiento'],
                'vacuna_id'        => null,
                'dosis'            => null,
                'tipo'             => 'consulta',
                'responsable'      => $consulta['responsable'],
                'lote_vacuna'      => null,
                'observaciones'    => $consulta['observaciones'],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Revisiones pendientes
    |--------------------------------------------------------------------------
    */
    private function crearRevisionesPendientes(object $animales): void
    {
        $hoy = Carbon::today();

        $revisiones = [
            [
                'diagnostico'   => 'Revisión preventiva general',
                'tratamiento'   => 'Chequeo corporal, revisión de mucosas y toma de temperatura. Pendiente.',
                'responsable'   => 'MVZ Juan Pérez',
                'observaciones' => 'Evento programado para control sanitario preventivo.',
                'dias'          => 2,
            ],
            [
                'diagnostico'   => 'Revisión posterior a cambio de lote',
                'tratamiento'   => 'Monitoreo de adaptación, alimentación y estrés. Pendiente.',
                'responsable'   => 'Encargado del rancho',
                'observaciones' => 'Verificar comportamiento después del cambio de corral.',
                'dias'          => 4,
            ],
            [
                'diagnostico'   => 'Revisión de pezuñas',
                'tratamiento'   => 'Inspección podal y posible recorte preventivo. Pendiente.',
                'responsable'   => 'MVZ Externo',
                'observaciones' => 'Revisar presencia de humedad, lesiones o cojera.',
                'dias'          => 6,
            ],
            [
                'diagnostico'   => 'Seguimiento por posible problema digestivo',
                'tratamiento'   => 'Evaluar heces, consumo de agua y rumia. Pendiente.',
                'responsable'   => 'MVZ Juan Pérez',
                'observaciones' => 'No aplicar medicamento hasta confirmar diagnóstico.',
                'dias'          => 8,
            ],
            [
                'diagnostico'   => 'Revisión de condición corporal',
                'tratamiento'   => 'Evaluación BCS y recomendación nutricional. Pendiente.',
                'responsable'   => 'Encargado sanitario',
                'observaciones' => 'Comparar con registros anteriores de peso.',
                'dias'          => 10,
            ],
        ];

        foreach ($animales->slice(18, 8)->values() as $index => $animal) {
            $revision = $revisiones[$index % count($revisiones)];

            $this->insertarEvento([
                'animal_id'        => $animal->id,
                'fecha_programada' => $hoy->copy()->addDays($revision['dias'])->toDateString(),
                'diagnostico'      => $revision['diagnostico'],
                'tratamiento'      => $revision['tratamiento'],
                'vacuna_id'        => null,
                'dosis'            => null,
                'tipo'             => 'revision',
                'responsable'      => $revision['responsable'],
                'lote_vacuna'      => null,
                'observaciones'    => $revision['observaciones'],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Emergencias pendientes
    |--------------------------------------------------------------------------
    */
    private function crearEmergenciasPendientes(object $animales): void
    {
        $hoy = Carbon::today();

        $emergencias = [
            [
                'diagnostico'   => 'Posible lesión por golpe',
                'tratamiento'   => 'Limpieza de zona afectada y valoración clínica urgente. Pendiente.',
                'responsable'   => 'MVZ Juan Pérez',
                'observaciones' => 'Revisar inflamación, dolor y movilidad del animal.',
                'dias'          => 1,
            ],
            [
                'diagnostico'   => 'Cojera repentina',
                'tratamiento'   => 'Evaluación podal y revisión de articulaciones. Pendiente.',
                'responsable'   => 'MVZ Externo',
                'observaciones' => 'Mantener en corral seco hasta valoración.',
                'dias'          => 2,
            ],
            [
                'diagnostico'   => 'Sospecha de cuadro respiratorio agudo',
                'tratamiento'   => 'Toma de temperatura, auscultación y posible antibiótico. Pendiente.',
                'responsable'   => 'MVZ Juan Pérez',
                'observaciones' => 'Separar del grupo si presenta fiebre o secreción nasal.',
                'dias'          => 1,
            ],
            [
                'diagnostico'   => 'Herida superficial en extremidad',
                'tratamiento'   => 'Limpieza, desinfección y vendaje si se requiere. Pendiente.',
                'responsable'   => 'Encargado sanitario',
                'observaciones' => 'Evitar contacto con lodo o superficies contaminadas.',
                'dias'          => 3,
            ],
        ];

        foreach ($animales->slice(26, 6)->values() as $index => $animal) {
            $emergencia = $emergencias[$index % count($emergencias)];

            $this->insertarEvento([
                'animal_id'        => $animal->id,
                'fecha_programada' => $hoy->copy()->addDays($emergencia['dias'])->toDateString(),
                'diagnostico'      => $emergencia['diagnostico'],
                'tratamiento'      => $emergencia['tratamiento'],
                'vacuna_id'        => null,
                'dosis'            => null,
                'tipo'             => 'emergencia',
                'responsable'      => $emergencia['responsable'],
                'lote_vacuna'      => null,
                'observaciones'    => $emergencia['observaciones'],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helper para insertar eventos
    |--------------------------------------------------------------------------
    */
    private function insertarEvento(array $data): int
    {
        return DB::table('eventos_salud')->insertGetId(array_merge([
            'animal_id'         => null,
            'fecha_programada'  => Carbon::today()->addDay()->toDateString(),
            'diagnostico'       => 'Revisión general',
            'tratamiento'       => null,
            'vacuna_id'         => null,
            'dosis'             => null,
            'tipo'              => 'consulta',
            'responsable'       => 'Encargado sanitario',
            'lote_vacuna'       => null,
            'observaciones'     => null,
            'user_id'           => null,
            'created_at'        => now(),
            'updated_at'        => now(),
        ], $data, [
            /*
            |--------------------------------------------------------------------------
            | Forzado para que ningún evento aparezca aplicado
            |--------------------------------------------------------------------------
            */
            'estado'            => 'pendiente',
            'fecha_aplicacion'  => null,
            'updated_at'        => now(),
        ]));
    }
}