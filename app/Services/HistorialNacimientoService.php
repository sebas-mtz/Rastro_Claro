<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Cria;
use App\Models\EventoReproductivo;
use App\Models\Parto;
use App\Models\ServicioReproductivo;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class HistorialNacimientoService
{
    /**
     * Reconstruye el servicio, parto y registro de cría de un animal
     * que fue dado de alta después de su nacimiento.
     *
     * Este método debe invocarse dentro de una transacción.
     */
    public function crear(
        Animal $animal,
        array $datos,
        int $userId,
    ): void {
        $this->validarDatos($animal, $datos);

        $madre = Animal::findOrFail($animal->madre_id);

        $fechaNacimiento = Carbon::parse($animal->fecha_nac)->startOfDay();

        $fechaServicio = $this->calcularFechaServicio(
            $madre->especie ?? $animal->especie,
            $fechaNacimiento
        );

        // ── Validación reproductiva de la madre ─────────────────────────
        // Debe haber tenido edad suficiente tanto en la fecha estimada de
        // concepción como en la fecha del parto, y no debe tener un parto
        // previo que entre en conflicto con el intervalo mínimo biológico.
        $this->validarDisponibilidadMadre($madre, $fechaServicio, $fechaNacimiento);

        // ── Validación de edad del padre interno ────────────────────────
        // Aplica sin importar el tipo de concepción histórica: el padre
        // genealógico (padre_id) debe haber tenido edad reproductiva en la
        // fecha estimada de concepción, ya sea que la cría se haya originado
        // por monta natural o por un método artificial (IATF, IA, etc.)
        // usando material genético de ese padre.
        if ($animal->padre_id) {
            $this->validarEdadPadre(
                Animal::findOrFail($animal->padre_id),
                $fechaServicio
            );
        }

        $numeroCrias = $this->numeroCriasDesdeTipo(
            $datos['tipo_nacimiento_historico']
        );

        /*
         * 1. Crear evento histórico de servicio.
         */
        $eventoServicio = EventoReproductivo::create([
            'hembra_id' => $madre->id,
            'lote_id' => $madre->lote_id,
            'user_id' => $userId,
            'tipo_evento' => 'servicio',
            'fecha' => $fechaServicio->toDateString(),
            'costo' => null,
            'observaciones' => sprintf(
                'Servicio histórico reconstruido al dar de alta al animal #%d.',
                $animal->id
            ),
            'es_historico' => true,
        ]);

        /*
         * 2. Crear detalle del servicio.
         */
        ServicioReproductivo::create([
            'evento_id' => $eventoServicio->id,
            'tipo_servicio' => $datos['concepcion_historica'],
            'macho_id' => $datos['concepcion_historica'] === 'monta_natural'
                ? $animal->padre_id
                : null,

            'pajilla_id' => null,
             'tecnico_id'      => null,
    'tecnico_externo' => null,
    'numero_servicio' => 1,
        ]);

        /*
         * 3. Crear evento histórico de parto.
         */
        $eventoParto = EventoReproductivo::create([
            'hembra_id' => $madre->id,
            'lote_id' => $madre->lote_id,
            'user_id' => $userId,
            'tipo_evento' => 'parto',
            'fecha' => $fechaNacimiento->toDateString(),
            'costo' => null,
            'observaciones' => sprintf(
                'Parto histórico reconstruido al dar de alta al animal #%d.',
                $animal->id
            ),
            'es_historico' => true,
        ]);

        /*
         * 4. Crear parto histórico.
         *
         * tipo_nacimiento no se guarda físicamente:
         * el accessor lo obtiene desde numero_crias.
         */
        $parto = Parto::create([
            'evento_id' => $eventoParto->id,
            'servicio_evento_id' => $eventoServicio->id,
            'tipo_parto' => $datos['tipo_parto_origen'],
            'asistencia_requerida' => false,
            'complicaciones' => false,
            'detalle_complicaciones' => null,
            'numero_crias' => $numeroCrias,
            'salio_leche' => false,
            'observaciones_leche' => null,
            'facilidad_materna' => false,
            'observaciones_maternas' => null,
        ]);

        /*
         * 5. Relacionar al animal adulto con ese parto histórico.
         *
         * Solo se crea la cría conocida. Si fue gemelar o triple, las demás
         * crías pueden ser desconocidas y no conviene inventarlas.
         */
        Cria::create([
            'parto_id' => $parto->id,
            'animal_id' => $animal->id,
            'sexo' => $this->sexoCria($animal->sexo),
            'peso_nacimiento' => null,
            'condicion' => 'vivo',
            'vigor' => null,
            'arete_temporal' => null,
            'observaciones' => sprintf(
                'Cría histórica asociada al alta del animal #%d.',
                $animal->id
            ),
        ]);
    }

    /**
     * Calcula la fecha estimada de concepción/servicio a partir de la fecha
     * de nacimiento y la duración de gestación de la especie indicada.
     *
     * Público para poder reutilizarse desde el controlador cuando se asigna
     * un padre sin madre (caso en el que crear() nunca se ejecuta).
     */
    public function calcularFechaServicio(string $especie, Carbon $fechaNacimiento): Carbon
    {
        return $fechaNacimiento->copy()->subDays(
            $this->diasGestacionPorEspecie($especie)
        );
    }

    /**
     * Valida que el padre interno haya tenido edad reproductiva suficiente
     * en la fecha estimada de concepción. Público para poder reutilizarse
     * desde el controlador en el caso de padre asignado sin madre.
     */
    public function validarEdadPadre(Animal $padre, Carbon $fechaServicio): void
    {
        if (!$padre->esAptoParaReproduccion($fechaServicio)) {
            throw ValidationException::withMessages([
                'padre_id' => sprintf(
                    "El padre '%s' no cumplía con la edad mínima de reproducción en la fecha estimada de concepción (%s).",
                    $padre->alias ?: $padre->arete,
                    $fechaServicio->toDateString()
                ),
            ]);
        }
    }

    /**
     * Valida que la madre haya tenido edad reproductiva suficiente y no
     * tenga registros previos (servicio o parto) que entren en conflicto
     * con las fechas del historial que se está reconstruyendo.
     */
    private function validarDisponibilidadMadre(
        Animal $madre,
        Carbon $fechaServicio,
        Carbon $fechaNacimiento
    ): void {
        [$aptaParaServicio, $mensajeServicio] = $madre->puedeRecibirServicio($fechaServicio);
        if (!$aptaParaServicio) {
            throw ValidationException::withMessages([
                'madre_id' => $mensajeServicio,
            ]);
        }

        [$aptaParaParto, $mensajeParto] = $madre->puedeRegistrarParto($fechaNacimiento);
        if (!$aptaParaParto) {
            throw ValidationException::withMessages([
                'madre_id' => $mensajeParto,
            ]);
        }
    }

    private function validarDatos(Animal $animal, array $datos): void
    {
        if (!$animal->madre_id) {
            throw ValidationException::withMessages([
                'madre_id' => 'La madre es necesaria para crear el historial reproductivo.',
            ]);
        }

        if (!$animal->fecha_nac) {
            throw ValidationException::withMessages([
                'fecha_nac' => 'La fecha de nacimiento es necesaria para crear el historial.',
            ]);
        }

        if (empty($datos['concepcion_historica'])) {
            throw ValidationException::withMessages([
                'concepcion_historica' => 'Selecciona el tipo de concepción.',
            ]);
        }

        if (empty($datos['tipo_nacimiento_historico'])) {
            throw ValidationException::withMessages([
                'tipo_nacimiento_historico' => 'Selecciona el tipo de nacimiento.',
            ]);
        }

        if (empty($datos['tipo_parto_origen'])) {
            throw ValidationException::withMessages([
                'tipo_parto_origen' => 'Selecciona el tipo de parto.',
            ]);
        }

        if (
            $datos['concepcion_historica'] === 'monta_natural'
            && !$animal->padre_id
        ) {
            throw ValidationException::withMessages([
                'padre_id' => 'Para una monta natural histórica debes seleccionar un padre interno.',
            ]);
        }
    }

    private function numeroCriasDesdeTipo(string $tipo): int
    {
        return match ($tipo) {
            'simple' => 1,
            'gemelar' => 2,
            'triple' => 3,
            'cuadruple' => 4,
            'quintuple' => 5,

            default => throw ValidationException::withMessages([
                'tipo_nacimiento_historico' => 'El tipo de nacimiento seleccionado no es válido.',
            ]),
        };
    }

    private function diasGestacionPorEspecie(?string $especie): int
    {
        $especieNormalizada = strtolower(trim((string) $especie));

        return match ($especieNormalizada) {
            'bovino', 'bovina', 'bovinos', 'bovinas', 'vaca', 'ganado bovino' => 283,
            'ovino', 'ovina', 'ovinos', 'ovinas' => 147,
            'caprino', 'caprina', 'caprinos', 'caprinas' => 150,
            'porcino', 'porcina', 'porcinos', 'porcinas' => 114,
            'equino', 'equina', 'equinos', 'equinas' => 340,

            /*
             * Es mejor detener la operación que inventar una fecha incorrecta.
             */
            default => throw ValidationException::withMessages([
                'especie' => 'No hay una duración de gestación configurada para esta especie.',
            ]),
        };
    }

    private function sexoCria(?string $sexoAnimal): string
    {
        $sexo = strtolower(trim((string) $sexoAnimal));

        return match ($sexo) {
            'm', 'macho', 'male' => 'macho',
            'h', 'hembra', 'female' => 'hembra',

            default => throw ValidationException::withMessages([
                'sexo' => 'No se pudo determinar el sexo de la cría histórica.',
            ]),
        };
    }
}