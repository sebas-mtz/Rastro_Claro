<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\AnimalGenetica;
use App\Models\AnimalValuationHistorial;
use App\Models\Costo;
use App\Models\Cria;
use App\Models\EventoReproductivo;
use App\Models\EventoSalud;
use App\Models\Parto;
use App\Models\ServicioReproductivo;
use App\Models\Tratamiento;
use App\Models\User;
use App\Services\AnimalValuationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Datos de ejemplo para ver el módulo de valuación funcionando con cifras
 * reales en lugar de ceros.
 *
 * Crea dos borregas marcadas con arete DEMO-*, con toda su historia de costos:
 *
 *   DEMO-001  Dorper de registro, cargada. Reproduce el ejemplo documentado:
 *             $8,000 de costo + 50 % de margen + $5,000 de plus = $17,000 MXN.
 *
 *   DEMO-002  Cría nacida de un parto de gemelas, para mostrar cómo el costo
 *             de gestación de la madre se reparte entre las dos crías.
 *
 * Es idempotente: cada corrida borra las borregas DEMO-* anteriores y las
 * vuelve a crear. Para eliminar los ejemplos sin recrearlos, borra los animales
 * con arete DEMO-001, DEMO-002 y DEMO-MADRE desde la interfaz.
 */
class ValuacionEjemploSeeder extends Seeder
{
    private const ARETES_DEMO = ['DEMO-001', 'DEMO-002', 'DEMO-MADRE'];

    public function run(): void
    {
        $usuario = $this->resolverUsuario();

        if (! $usuario) {
            $this->command?->error('No hay usuarios en la base de datos. Crea una cuenta antes de sembrar los ejemplos.');

            return;
        }

        // El seeder corre desde consola, sin sesión: se autentica para que el
        // scope de tenencia y la asignación automática de owner_id funcionen.
        Auth::login($usuario);

        $this->limpiarEjemplosPrevios();

        DB::transaction(function () use ($usuario) {
            $this->crearDorperDeRegistro($usuario);
            $this->crearCriaDeParahGemelar($usuario);
        });

        Auth::logout();

        $this->command?->info('Ejemplos creados para la cuenta ' . $usuario->email . ':');
        $this->command?->line('  DEMO-001  Dorper de registro cargada  →  $17,000.00 MXN');
        $this->command?->line('  DEMO-002  Cría de parto gemelar        →  gestación repartida entre 2 crías');
    }

    /**
     * Usa la cuenta que ya tiene animales registrados; si no hay ninguna,
     * el primer usuario del sistema.
     */
    private function resolverUsuario(): ?User
    {
        $ownerId = DB::table('animals')->whereNotNull('owner_id')->value('owner_id');

        return $ownerId
            ? User::find($ownerId) ?? User::first()
            : User::first();
    }

    private function limpiarEjemplosPrevios(): void
    {
        $animales = Animal::withoutGlobalScope('owner')
            ->whereIn('arete', self::ARETES_DEMO)
            ->get();

        foreach ($animales as $animal) {
            // Las tablas hijas usan cascadeOnDelete o nullOnDelete; los costos
            // se borran de forma explícita porque apuntan con nullOnDelete.
            Costo::withoutGlobalScope('owner')->where('animal_id', $animal->id)->delete();
            EventoSalud::where('animal_id', $animal->id)->delete();
            Tratamiento::where('animal_id', $animal->id)->delete();
            AnimalValuationHistorial::withoutGlobalScope('owner')->where('animal_id', $animal->id)->delete();

            EventoReproductivo::withoutGlobalScope('owner')->where('hembra_id', $animal->id)->delete();

            $animal->delete();
        }
    }

    /**
     * DEMO-001 — el ejemplo documentado, de punta a punta.
     */
    private function crearDorperDeRegistro(User $usuario): void
    {
        $animal = Animal::create([
            'especie' => 'Ovino',
            'raza' => 'Dorper',
            'arete' => 'DEMO-001',
            'alias' => 'Ejemplo — Dorper de registro',
            'sexo' => 'F',
            'fecha_nac' => now()->subMonths(14)->toDateString(),
            'peso' => 58.5,
            'estado_productivo' => 'Reproductora',
            'tipo_origen' => Animal::ORIGEN_NACIDO,
        ]);

        AnimalGenetica::create([
            'animal_id' => $animal->id,
            'porcentaje_pureza' => 100,
            'numero_registro' => 'REG-DOR-2025-0142',
            'asociacion' => 'Asociación Mexicana de Criadores de Ovinos',
            'linea_genetica' => 'Dorper cabeza negra',
            'calidad_fenotipica' => 'Excelente',
            'aplomos' => 'Correctos',
            'caracteristicas_destacadas' => 'Buena profundidad de costilla y aplomos firmes.',
            'premios' => '2º lugar, Expo Ganadera regional 2026',
            'porcentaje_margen_genetico' => 50,
            'observaciones' => 'Animal de ejemplo creado por el seeder de demostración.',
        ]);

        // ── Costo de nacimiento: $3,000 ──────────────────────────────────
        $this->costo($animal, $usuario, 'compra_animales', 'Costo estimado de nacimiento', 3000, 14, [
            'descripcion' => 'Valor asignado al nacimiento dentro de la unidad productiva.',
        ]);

        // ── Sanidad: $450 en total, capturado en el módulo de salud ───────
        EventoSalud::create([
            'animal_id' => $animal->id,
            'tipo' => 'vacunacion',
            'fecha_programada' => now()->subMonths(12)->toDateString(),
            'fecha_aplicacion' => now()->subMonths(12)->toDateString(),
            'estado' => 'aplicada',
            'diagnostico' => 'Vacuna contra clostridiasis',
            'dosis' => '1 dosis',
            'costo' => 95,
            'responsable' => 'MVZ. Ramírez',
            'user_id' => $usuario->id,
        ]);

        EventoSalud::create([
            'animal_id' => $animal->id,
            'tipo' => 'vacunacion',
            'fecha_programada' => now()->subMonths(9)->toDateString(),
            'fecha_aplicacion' => now()->subMonths(9)->toDateString(),
            'estado' => 'aplicada',
            'diagnostico' => 'Refuerzo de clostridiasis',
            'dosis' => '1 dosis',
            'costo' => 95,
            'responsable' => 'MVZ. Ramírez',
            'user_id' => $usuario->id,
        ]);

        EventoSalud::create([
            'animal_id' => $animal->id,
            'tipo' => 'consulta',
            'fecha_programada' => now()->subMonths(6)->toDateString(),
            'fecha_aplicacion' => now()->subMonths(6)->toDateString(),
            'estado' => 'aplicada',
            'diagnostico' => 'Revisión general y ultrasonido',
            'costo' => 160,
            'responsable' => 'MVZ. Ramírez',
            'user_id' => $usuario->id,
        ]);

        Tratamiento::create([
            'animal_id' => $animal->id,
            'nombre' => 'Desparasitación interna',
            'fecha_inicio' => now()->subMonths(8)->toDateString(),
            'fecha_fin' => now()->subMonths(8)->addDays(3)->toDateString(),
            'estado' => 'completado',
            'costo' => 100,
            'responsable' => 'MVZ. Ramírez',
            'notas' => 'Ivermectina, dosis única.',
            'user_id' => $usuario->id,
        ]);

        // ── Alimentación: $4,200 ─────────────────────────────────────────
        $this->costo($animal, $usuario, 'alimentacion', 'Alimento balanceado (acumulado)', 2800, 10, [
            'cantidad' => 393,
            'unidad_medida' => 'kg',
            'descripcion' => 'Consumo acumulado de ración de crecimiento.',
        ]);
        $this->costo($animal, $usuario, 'alimentacion', 'Forraje y pastoreo suplementario', 1400, 5, [
            'cantidad' => 200,
            'unidad_medida' => 'kg',
        ]);

        // ── Registro de pureza: $350 ─────────────────────────────────────
        $this->costo($animal, $usuario, 'registro_genetico', 'Registro de pureza ante la asociación', 350, 6, [
            'proveedor' => 'Asociación Mexicana de Criadores de Ovinos',
            'numero_comprobante' => 'F-2026-0142',
        ]);

        // ── Servicio reproductivo con semental de registro ───────────────
        $semental = Animal::create([
            'especie' => 'Ovino',
            'raza' => 'Dorper',
            'arete' => 'DEMO-MADRE',   // se reutiliza el prefijo DEMO para poder limpiarlo
            'alias' => 'Ejemplo — Semental de registro',
            'sexo' => 'M',
            'fecha_nac' => now()->subYears(3)->toDateString(),
            'tipo_origen' => Animal::ORIGEN_COMPRADO,
        ]);

        AnimalGenetica::create([
            'animal_id' => $semental->id,
            'porcentaje_pureza' => 100,
            'numero_registro' => 'REG-DOR-2023-0007',
            'asociacion' => 'Asociación Mexicana de Criadores de Ovinos',
            'porcentaje_margen_genetico' => 60,
        ]);

        $eventoServicio = EventoReproductivo::create([
            'hembra_id' => $animal->id,
            'user_id' => $usuario->id,
            'tipo_evento' => 'servicio',
            'fecha' => now()->subMonths(3)->toDateString(),
            'costo' => 0,
            'observaciones' => 'Monta natural con semental de registro.',
        ]);

        ServicioReproductivo::create([
            'evento_id' => $eventoServicio->id,
            'tipo_servicio' => 'monta_natural',
            'macho_id' => $semental->id,
            'numero_servicio' => 1,
        ]);

        // ── Cotización guardada ──────────────────────────────────────────
        // Margen 50 % (viene de la ficha genética) y plus de $5,000, que es el
        // valor del ejemplo documentado. En el simulador puedes ver qué pasa
        // con el plus configurado para "cargada por semental de registro".
        app(AnimalValuationService::class)->guardar(
            $animal,
            [
                'porcentaje_margen_genetico' => 50,
                'estado_reproductivo_valuacion' => 'cargada_semental_registro',
                'plus_reproductivo' => 5000,
            ],
            AnimalValuationHistorial::TIPO_CREACION,
            'Cotización inicial del animal de ejemplo.'
        );
    }

    /**
     * DEMO-002 — cría de un parto gemelar, para ver el reparto de la gestación.
     */
    private function crearCriaDeParahGemelar(User $usuario): void
    {
        $madre = Animal::where('arete', 'DEMO-001')->first();

        if (! $madre) {
            return;
        }

        $cria = Animal::create([
            'especie' => 'Ovino',
            'raza' => 'Dorper',
            'arete' => 'DEMO-002',
            'alias' => 'Ejemplo — Cría de parto gemelar',
            'sexo' => 'F',
            'fecha_nac' => now()->subMonths(2)->toDateString(),
            'peso' => 18.2,
            'madre_id' => $madre->id,
            'tipo_origen' => Animal::ORIGEN_NACIDO,
        ]);

        AnimalGenetica::create([
            'animal_id' => $cria->id,
            'porcentaje_pureza' => 100,
            'linea_genetica' => 'Dorper cabeza negra',
            'porcentaje_margen_genetico' => 30,
        ]);

        // Gestación de la madre: servicio + revisiones, $1,200 en total
        $eventoServicio = EventoReproductivo::create([
            'hembra_id' => $madre->id,
            'user_id' => $usuario->id,
            'tipo_evento' => 'servicio',
            'fecha' => now()->subMonths(7)->toDateString(),
            'costo' => 400,
            'observaciones' => 'Servicio que originó el parto gemelar.',
        ]);

        EventoReproductivo::create([
            'hembra_id' => $madre->id,
            'user_id' => $usuario->id,
            'tipo_evento' => 'diagnostico',
            'fecha' => now()->subMonths(5)->toDateString(),
            'costo' => 350,
            'observaciones' => 'Ultrasonido de confirmación: gestación doble.',
        ]);

        $eventoParto = EventoReproductivo::create([
            'hembra_id' => $madre->id,
            'user_id' => $usuario->id,
            'tipo_evento' => 'parto',
            'fecha' => now()->subMonths(2)->toDateString(),
            'costo' => 450,
            'observaciones' => 'Parto gemelar asistido, sin complicaciones.',
        ]);

        $parto = Parto::create([
            'evento_id' => $eventoParto->id,
            'servicio_evento_id' => $eventoServicio->id,
            'tipo_parto' => 'normal',
            'asistencia_requerida' => true,
            'complicaciones' => false,
            'numero_crias' => 2,
        ]);

        // Esta cría queda ligada al parto; la gemela solo existe como registro
        // para que el divisor sea 2 y el reparto quede evidente.
        Cria::create([
            'parto_id' => $parto->id,
            'animal_id' => $cria->id,
            'sexo' => 'hembra',
            'peso_nacimiento' => 4.1,
            'condicion' => 'vivo',
        ]);

        Cria::create([
            'parto_id' => $parto->id,
            'animal_id' => null,
            'sexo' => 'macho',
            'peso_nacimiento' => 3.9,
            'condicion' => 'vivo',
            'arete_temporal' => 'DEMO-002-B',
            'observaciones' => 'Gemelo, aún sin arete definitivo.',
        ]);

        // Gastos propios de la cría
        EventoSalud::create([
            'animal_id' => $cria->id,
            'tipo' => 'vacunacion',
            'fecha_programada' => now()->subMonth()->toDateString(),
            'fecha_aplicacion' => now()->subMonth()->toDateString(),
            'estado' => 'aplicada',
            'diagnostico' => 'Primera vacuna de clostridiasis',
            'dosis' => '1 dosis',
            'costo' => 95,
            'user_id' => $usuario->id,
        ]);

        $this->costo($cria, $usuario, 'alimentacion', 'Sustituto de leche', 620, 1, [
            'cantidad' => 40,
            'unidad_medida' => 'kg',
        ]);

        app(AnimalValuationService::class)->guardar(
            $cria,
            [
                'porcentaje_margen_genetico' => 30,
                'estado_reproductivo_valuacion' => 'joven_sin_edad_reproductiva',
            ],
            AnimalValuationHistorial::TIPO_CREACION,
            'Cotización inicial de la cría de ejemplo.'
        );
    }

    private function costo(
        Animal $animal,
        User $usuario,
        string $categoria,
        string $concepto,
        float $monto,
        int $mesesAtras,
        array $extra = [],
    ): Costo {
        return Costo::create(array_merge([
            'concepto' => $concepto,
            'categoria' => $categoria,
            'tipo_costo' => 'directo',
            'monto' => $monto,
            'fecha' => now()->subMonths($mesesAtras)->toDateString(),
            'animal_id' => $animal->id,
            'user_id' => $usuario->id,
        ], $extra));
    }
}
