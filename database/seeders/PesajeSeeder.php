<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * PesajesSeeder
 * ─────────────────────────────────────────────────────────────────────────
 * Genera el historial de pesajes de cada animal desde el nacimiento
 * hasta la fecha actual, simulando una curva de crecimiento ovina.
 *
 * Frecuencia de pesajes por etapa:
 *   • 0 – 3 meses  : cada 30 días  (fase de lactancia)
 *   • 3 – 12 meses : cada 45 días  (crecimiento activo)
 *   • > 12 meses   : cada 90 días  (mantenimiento / adulto)
 *
 * Curva de crecimiento (kg):
 *   Hembras → máx. ~50 kg  |  Machos → máx. ~85 kg
 *
 * Parámetros por etapa (ganancia diaria promedio):
 *   0–90 días   : 0.230 kg/día
 *   91–180 días : 0.180 kg/día
 *   181–365 días: 0.110 kg/día
 *   > 365 días  : 0.025 kg/día  (adulto)
 *
 * Los machos tienen un factor ×1.35 sobre el peso calculado.
 *
 * El peso de nacimiento se obtiene de `crias.peso_nacimiento`
 * cuando existe; en caso contrario se estima según sexo.
 *
 * IMPORTANTE: Ejecutar DESPUÉS de AnimalsSeeder (+ ReproduccionSeeder).
 * ─────────────────────────────────────────────────────────────────────────
 */
class PesajeSeeder extends Seeder
{
    private const PESO_NAC_HEMBRA = 3.8;
    private const PESO_NAC_MACHO  = 4.3;

    public function run(): void
    {
        DB::table('pesajes')->delete();

        $pesosNacimiento = DB::table('crias')
            ->whereNotNull('animal_id')
            ->whereNotNull('peso_nacimiento')
            ->pluck('peso_nacimiento', 'animal_id');

        $animales = DB::table('animals')
            ->where('especie', 'Ovino')
            ->whereNotNull('fecha_nac')
            ->whereNotIn('estado_productivo', ['vendido', 'sacrificado', 'faeneado'])
            ->orderBy('fecha_nac')
            ->get(['id', 'arete', 'sexo', 'fecha_nac', 'peso', 'estado_productivo']);

        $hoy = Carbon::today();
        $batch = [];

        foreach ($animales as $animal) {
            $nacimiento = Carbon::parse($animal->fecha_nac)->startOfDay();

            if ($nacimiento->greaterThan($hoy)) {
                continue;
            }

            $esMacho = $animal->sexo === 'M';

            $pesoNacimiento = (float) ($pesosNacimiento[$animal->id]
                ?? ($esMacho ? self::PESO_NAC_MACHO : self::PESO_NAC_HEMBRA));

            // Peso adulto esperado para ovinos. Se varía un poco por animal para que no todos pesen igual.
            $pesoAdulto = $this->pesoAdultoEsperado($animal->id, $esMacho);

            // Si el animal ya tiene peso actual en animals, úsalo como referencia superior.
            // Esto ayuda a que la gráfica sea coherente con la ficha del animal.
            if (!is_null($animal->peso) && (float) $animal->peso > 0) {
                $pesoAdulto = max($pesoAdulto, (float) $animal->peso + 3);
            }

            // Primer pesaje: nacimiento.
            $batch[] = $this->registro($animal->id, $nacimiento, $pesoNacimiento, 'Peso estimado de nacimiento');

            // Historial general.
            $fecha = $nacimiento->copy();

            while (true) {
                $edadDias = $nacimiento->diffInDays($fecha);
                $intervalo = $this->intervalo($edadDias);
                $fecha->addDays($intervalo);

                // Dejamos los últimos 35 días para insertar manualmente dos pesajes recientes positivos.
                if ($fecha->greaterThan($hoy->copy()->subDays(35))) {
                    break;
                }

                $edadEnPesaje = $nacimiento->diffInDays($fecha);
                $peso = $this->calcularPeso($pesoNacimiento, $pesoAdulto, $edadEnPesaje);

                $batch[] = $this->registro($animal->id, $fecha, $peso, null);

                if (count($batch) >= 300) {
                    DB::table('pesajes')->insert($batch);
                    $batch = [];
                }
            }

            // Últimos dos pesajes recientes.
            // Esto asegura que en la gráfica de ganancia aparezcan animales con ganancia positiva.
            $fechaAnterior = $hoy->copy()->subDays(30);
            $fechaActual   = $hoy->copy();

            // Si el animal nació hace menos de 30 días, ajustamos las fechas para evitar pesajes antes de nacer.
            if ($fechaAnterior->lessThan($nacimiento)) {
                $fechaAnterior = $nacimiento->copy()->addDays(1);
            }

            if ($fechaActual->lessThanOrEqualTo($fechaAnterior)) {
                $fechaActual = $fechaAnterior->copy()->addDays(1);
            }

            $edadAnterior = $nacimiento->diffInDays($fechaAnterior);
            $edadActual   = $nacimiento->diffInDays($fechaActual);

            $pesoAnterior = $this->calcularPeso($pesoNacimiento, $pesoAdulto, $edadAnterior);
            $pesoActual   = $this->calcularPeso($pesoNacimiento, $pesoAdulto, $edadActual);

            // Ganancia mínima reciente para que no aparezca 0 en todos los adultos.
            $gananciaMinima = $this->gananciaRecienteMinima($animal->estado_productivo, $esMacho);

            if ($pesoActual <= $pesoAnterior) {
                $pesoActual = $pesoAnterior + $gananciaMinima;
            }

            // Si la diferencia es muy pequeña, la reforzamos.
            if (($pesoActual - $pesoAnterior) < $gananciaMinima) {
                $pesoActual = $pesoAnterior + $gananciaMinima;
            }

            $batch[] = $this->registro($animal->id, $fechaAnterior, $pesoAnterior, 'Pesaje reciente anterior');
            $batch[] = $this->registro($animal->id, $fechaActual, $pesoActual, 'Pesaje reciente actual');
        }

        if (!empty($batch)) {
            DB::table('pesajes')->insert($batch);
        }

        $this->command->info('Pesajes ovinos creados correctamente con ganancias positivas recientes.');
    }

    private function pesoAdultoEsperado(int $animalId, bool $esMacho): float
    {
        $variacion = ($animalId % 7) - 3; // -3 a +3

        return $esMacho
            ? 74 + ($variacion * 2.2)   // machos aprox. 67.4 a 80.6 kg
            : 48 + ($variacion * 1.4);  // hembras aprox. 43.8 a 52.2 kg
    }

    /**
     * Curva de crecimiento tipo exponencial suave.
     * No usa techo rígido, por eso no aplana todos los últimos pesajes.
     */
    private function calcularPeso(float $pesoNacimiento, float $pesoAdulto, int $edadDias): float
    {
        if ($edadDias <= 0) {
            return round($pesoNacimiento, 2);
        }

        // k controla qué tan rápido se acerca al peso adulto.
        $k = 0.0048;

        $peso = $pesoAdulto - (($pesoAdulto - $pesoNacimiento) * exp(-$k * $edadDias));

        return round($peso, 2);
    }

    private function gananciaRecienteMinima(?string $estadoProductivo, bool $esMacho): float
    {
        return match ($estadoProductivo) {
            'En crecimiento' => $esMacho ? 1.8 : 1.4,
            'reemplazo'      => $esMacho ? 1.5 : 1.1,
            'lactancia'      => 0.6,
            'gestante'       => 0.8,
            'empadre'        => 0.5,
            default          => $esMacho ? 0.7 : 0.4,
        };
    }

    private function intervalo(int $edadDias): int
    {
        if ($edadDias < 90) {
            return 30;
        }

        if ($edadDias < 365) {
            return 45;
        }

        return 90;
    }

    private function registro(int $animalId, Carbon $fecha, float $peso, ?string $notas): array
    {
        return [
            'animal_id'  => $animalId,
            'fecha'      => $fecha->toDateString(),
            'peso'       => round(max($peso, 0.1), 2),
            'notas'      => $notas,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
