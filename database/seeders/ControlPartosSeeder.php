<?php

namespace Database\Seeders;

use App\Models\Animal;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ControlPartosSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('CONTROL_PARTOS_SEED_EMAIL');

        if (!$email) {
            $this->command->error(
                'Define CONTROL_PARTOS_SEED_EMAIL con el correo de la cuenta que recibirá los datos.'
            );
            return;
        }

        $ownerId = DB::table('users')->where('email', $email)->value('id');

        if (!$ownerId) {
            $this->command->error("No existe un usuario con el correo {$email}.");
            return;
        }

        $loteId = DB::table('lotes')
            ->where('nombre', 'Crías en seguimiento')
            ->where(function ($query) use ($ownerId) {
                $query->where('owner_id', $ownerId)->orWhereNull('owner_id');
            })
            ->value('id');

        if (!$loteId) {
            $loteId = DB::table('lotes')->insertGetId([
                'owner_id' => $ownerId,
                'nombre' => 'Crías en seguimiento',
                'corral_potrero' => 'Potrero de destete',
                'responsable_id' => $ownerId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('lotes')->where('id', $loteId)->update([
                'owner_id' => $ownerId,
                'updated_at' => now(),
            ]);
        }

        $seguimientos = [
            'B24-002' => [
                'destino' => 'En crecimiento',
                'peso_destete' => 19.40,
                'estado_madre' => 'bueno',
                'pesajes' => [29 => 10.70],
            ],
            'S24-001' => [
                'destino' => 'reemplazo',
                'peso_destete' => 21.00,
                'estado_madre' => 'regular',
                'pesajes' => [31 => 11.80],
            ],
            'B24-003' => [
                'destino' => 'En crecimiento',
                'peso_destete' => 19.10,
                'estado_madre' => 'regular',
                'pesajes' => [30 => 10.50, 75 => 19.10, 90 => 21.80, 122 => 27.10, 150 => 32.00],
            ],
            'B25-001' => [
                'destino' => 'En crecimiento',
                'peso_destete' => 20.00,
                'estado_madre' => 'bueno',
                'pesajes' => [28 => 10.40, 76 => 20.00, 90 => 22.90, 120 => 28.30, 151 => 33.20],
            ],
        ];

        $partos = [];

        foreach ($seguimientos as $arete => $configuracion) {
            $animal = DB::table('animals')
                ->where('arete', $arete)
                ->where(function ($query) use ($ownerId) {
                    $query->where('owner_id', $ownerId)->orWhereNull('owner_id');
                })
                ->first();

            if (!$animal) {
                $this->command->warn("No se encontró la cría {$arete}; se omite su seguimiento.");
                continue;
            }

            DB::table('animals')->where('id', $animal->id)->update([
                'owner_id' => $ownerId,
                'lote_id' => $loteId,
                'updated_at' => now(),
            ]);

            $cria = DB::table('crias')
                ->where('animal_id', $animal->id)
                ->first();

            if (!$cria) {
                $this->command->warn("El animal {$arete} no tiene registro en crias.");
                continue;
            }

            DB::table('crias')->where('id', $cria->id)->update([
                'owner_id' => $ownerId,
                'updated_at' => now(),
            ]);

            $parto = DB::table('partos')
                ->join('evento_reproductivos', 'evento_reproductivos.id', '=', 'partos.evento_id')
                ->where('partos.id', $cria->parto_id)
                ->select(
                    'partos.id',
                    'partos.evento_id',
                    'partos.numero_crias',
                    'evento_reproductivos.hembra_id',
                    'evento_reproductivos.fecha',
                )
                ->first();

            if (!$parto) {
                continue;
            }

            if (!is_null($cria->peso_nacimiento)) {
                DB::table('animals')->where('id', $animal->id)->update([
                    'peso_inicial' => $cria->peso_nacimiento,
                    'fecha_peso_inicial' => $parto->fecha,
                    'updated_at' => now(),
                ]);
            }

            DB::table('partos')->where('id', $parto->id)->update([
                'owner_id' => $ownerId,
                'salio_leche' => true,
                'updated_at' => now(),
            ]);
            DB::table('evento_reproductivos')->where('id', $parto->evento_id)->update([
                'owner_id' => $ownerId,
                'user_id' => $ownerId,
                'updated_at' => now(),
            ]);
            DB::table('animals')->where('id', $parto->hembra_id)->update([
                'owner_id' => $ownerId,
                'updated_at' => now(),
            ]);

            $fechaParto = Carbon::parse($parto->fecha);

            foreach ($configuracion['pesajes'] as $dias => $peso) {
                DB::table('pesajes')->updateOrInsert(
                    [
                        'animal_id' => $animal->id,
                        'fecha' => $fechaParto->copy()->addDays($dias)->toDateString(),
                    ],
                    [
                        'owner_id' => $ownerId,
                        'peso' => $peso,
                        'notas' => in_array($dias, [75, 76], true)
                            ? 'Pesaje de destete para demostración de Control de partos.'
                            : "Seguimiento de crecimiento cercano a {$dias} días.",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }

            $partos[$parto->id]['parto'] = $parto;
            $partos[$parto->id]['estado_madre'] = $configuracion['estado_madre'];
            $partos[$parto->id]['crias'][] = [
                'cria_id' => $cria->id,
                'animal_id' => $animal->id,
                'peso_destete' => $configuracion['peso_destete'],
                'estado_destino' => $configuracion['destino'],
            ];
        }

        // Estas bajas suceden antes de los 75 días. Así queda un ejemplo
        // sin destete y un parto gemelar cuyo destete se registra como simple.
        $this->crearVentaDemostracion($ownerId);
        $this->crearMuerteDemostracion($ownerId);

        foreach ($partos as $datosParto) {
            $this->crearDestete(
                $ownerId,
                $datosParto['parto'],
                $datosParto['estado_madre'],
                $datosParto['crias'],
            );
        }

        $this->asignarRegistrosSinPropietario($ownerId);

        $this->command->info(
            "Control de partos creado únicamente para la cuenta {$email}."
        );
    }

    private function crearDestete(
        int $ownerId,
        object $parto,
        string $estadoMadre,
        array $crias
    ): void {
        $fechaDestete = Carbon::parse($parto->fecha)->addDays(75)->toDateString();
        $criasDisponibles = $this->criasDisponiblesEnFecha($crias, $fechaDestete);

        if (empty($criasDisponibles)) {
            $this->command->warn(
                "El parto {$parto->id} no tiene crías disponibles en la fecha de destete; se omite."
            );

            return;
        }

        $estadoProductivoMadre = 'mantenimiento';
        $tipoNacimiento = $this->tipoNacimiento(count($criasDisponibles));
        $desteteExistente = DB::table('destetes')
            ->where('parto_id', $parto->id)
            ->first();

        if ($desteteExistente) {
            $desteteId = $desteteExistente->id;
            DB::table('destetes')->where('id', $desteteId)->update([
                'owner_id' => $ownerId,
                'estado_madre' => $estadoMadre,
                'estado_productivo_madre' => $estadoProductivoMadre,
                'tipo_nacimiento' => $tipoNacimiento,
                'updated_at' => now(),
            ]);
        } else {
            $loteMadre = DB::table('animals')->where('id', $parto->hembra_id)->value('lote_id');

            $eventoId = DB::table('evento_reproductivos')->insertGetId([
                'owner_id' => $ownerId,
                'hembra_id' => $parto->hembra_id,
                'lote_id' => $loteMadre,
                'user_id' => $ownerId,
                'tipo_evento' => 'destete',
                'fecha' => $fechaDestete,
                'costo' => null,
                'observaciones' => 'Destete de demostración para seguimiento de Control de partos.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $desteteId = DB::table('destetes')->insertGetId([
                'owner_id' => $ownerId,
                'evento_id' => $eventoId,
                'parto_id' => $parto->id,
                'estado_madre' => $estadoMadre,
                'estado_productivo_madre' => $estadoProductivoMadre,
                'tipo_nacimiento' => $tipoNacimiento,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('animals')->where('id', $parto->hembra_id)->update([
            'estado_productivo' => $estadoProductivoMadre,
            'updated_at' => now(),
        ]);

        foreach ($criasDisponibles as $cria) {
            DB::table('destete_crias')->updateOrInsert(
                [
                    'owner_id' => $ownerId,
                    'destete_id' => $desteteId,
                    'cria_id' => $cria['cria_id'],
                ],
                [
                    'peso_destete' => $cria['peso_destete'],
                    'estado_destino' => $cria['estado_destino'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            DB::table('animals')->where('id', $cria['animal_id'])->update([
                'estado_productivo' => $cria['estado_destino'],
                'updated_at' => now(),
            ]);
        }
    }

    private function criasDisponiblesEnFecha(array $crias, string $fechaDestete): array
    {
        return array_values(array_filter($crias, function (array $cria) use ($fechaDestete): bool {
            $condicion = DB::table('crias')
                ->where('id', $cria['cria_id'])
                ->value('condicion');

            if ($condicion !== 'vivo' || empty($cria['animal_id'])) {
                return false;
            }

            $murioAntesDelDestete = DB::table('muertes')
                ->where('animal_id', $cria['animal_id'])
                ->whereDate('fecha', '<=', $fechaDestete)
                ->exists();

            if ($murioAntesDelDestete) {
                return false;
            }

            return !DB::table('ventas')
                ->where('vendible_type', Animal::class)
                ->where('vendible_id', $cria['animal_id'])
                ->where('estado_venta', 'completada')
                ->whereDate('fecha_venta', '<=', $fechaDestete)
                ->exists();
        }));
    }

    private function crearVentaDemostracion(int $ownerId): void
    {
        $animal = DB::table('animals')
            ->where('arete', 'B24-002')
            ->where('owner_id', $ownerId)
            ->first();
        $cria = $animal
            ? DB::table('crias')->where('animal_id', $animal->id)->first()
            : null;

        if (!$animal || !$cria) {
            return;
        }

        $fechaParto = DB::table('partos')
            ->join('evento_reproductivos', 'evento_reproductivos.id', '=', 'partos.evento_id')
            ->where('partos.id', $cria->parto_id)
            ->value('evento_reproductivos.fecha');
        $fechaVenta = Carbon::parse($fechaParto)->addDays(50)->toDateString();

        DB::table('ventas')->updateOrInsert(
            [
                'owner_id' => $ownerId,
                'numero_factura' => 'DEMO-VTA-B24-002',
            ],
            [
                'vendible_id' => $animal->id,
                'vendible_type' => Animal::class,
                'comprador_id' => null,
                'fecha_venta' => $fechaVenta,
                'tipo_venta' => 'animal',
                'estado_venta' => 'completada',
                'costo_total' => 0,
                'producto' => 'Ovino en pie - Arete B24-002',
                'cantidad' => 1,
                'unidad' => 'animal',
                'precio_unitario' => 2950,
                'precio_total' => 2950,
                'metodo_pago' => 'transferencia',
                'estado_pago' => 'completado',
                'condiciones_entrega' => 'Entrega en instalaciones del rancho.',
                'fecha_entrega' => $fechaVenta,
                'observaciones' => 'Vendida antes del destete por solicitud de un productor local.',
                'vendedor_id' => $ownerId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        DB::table('animals')->where('id', $animal->id)->update([
            'estado_productivo' => 'vendido',
            'updated_at' => now(),
        ]);
    }

    private function crearMuerteDemostracion(int $ownerId): void
    {
        $animal = DB::table('animals')
            ->where('arete', 'S24-001')
            ->where('owner_id', $ownerId)
            ->first();
        $cria = $animal
            ? DB::table('crias')->where('animal_id', $animal->id)->first()
            : null;

        if (!$animal || !$cria) {
            return;
        }

        $fechaParto = DB::table('partos')
            ->join('evento_reproductivos', 'evento_reproductivos.id', '=', 'partos.evento_id')
            ->where('partos.id', $cria->parto_id)
            ->value('evento_reproductivos.fecha');

        DB::table('muertes')->updateOrInsert(
            [
                'owner_id' => $ownerId,
                'animal_id' => $animal->id,
            ],
            [
                'fecha' => Carbon::parse($fechaParto)->addDays(45)->toDateString(),
                'causa' => 'Neumonía',
                'observaciones' => 'Presentó dificultad respiratoria; se aplicó tratamiento, pero no respondió favorablemente.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        DB::table('animals')->where('id', $animal->id)->update([
            'estado_productivo' => 'muerto',
            'updated_at' => now(),
        ]);
    }

    private function tipoNacimiento(int $numeroCrias): string
    {
        return match ($numeroCrias) {
            1 => 'simple',
            2 => 'gemelar',
            3 => 'triple',
            4 => 'cuadruple',
            default => "{$numeroCrias}_crias",
        };
    }

    private function asignarRegistrosSinPropietario(int $ownerId): void
    {
        DB::transaction(function () use ($ownerId) {
            /*
             * Al volver a ejecutar todos los seeders pueden existir dos filas
             * con el mismo código: la que ya pertenece a la cuenta y otra
             * recién creada con owner_id null. Antes de reclamar los registros
             * sin propietario, reutilizamos la fila de la cuenta y corregimos
             * sus referencias para no violar los índices únicos por propietario.
             */
            $this->reconciliarCodigoDuplicado(
                tabla: 'donadores_externos',
                columna: 'codigo',
                ownerId: $ownerId,
                reasignar: function (int $duplicadoId, int $existenteId): void {
                    DB::table('pajillas')
                        ->where('donador_externo_id', $duplicadoId)
                        ->update(['donador_externo_id' => $existenteId]);
                    DB::table('animals')
                        ->where('padre_externo_id', $duplicadoId)
                        ->update(['padre_externo_id' => $existenteId]);
                },
            );

            $this->reconciliarCodigoDuplicado(
                tabla: 'termos',
                columna: 'codigo',
                ownerId: $ownerId,
                reasignar: fn (int $duplicadoId, int $existenteId) =>
                    DB::table('pajillas')
                        ->where('termo_id', $duplicadoId)
                        ->update(['termo_id' => $existenteId]),
            );

            $this->reconciliarCodigoDuplicado(
                tabla: 'pajillas',
                columna: 'codigo',
                ownerId: $ownerId,
                reasignar: fn (int $duplicadoId, int $existenteId) =>
                    DB::table('servicio_reproductivos')
                        ->where('pajilla_id', $duplicadoId)
                        ->update(['pajilla_id' => $existenteId]),
            );

            $this->reconciliarCodigoDuplicado(
                tabla: 'ventas',
                columna: 'numero_factura',
                ownerId: $ownerId,
            );

            foreach (config('tenancy.tables', []) as $tabla) {
                if (!Schema::hasTable($tabla) || !Schema::hasColumn($tabla, 'owner_id')) {
                    continue;
                }

                DB::table($tabla)
                    ->whereNull('owner_id')
                    ->update(['owner_id' => $ownerId]);
            }
        });
    }

    private function reconciliarCodigoDuplicado(
        string $tabla,
        string $columna,
        int $ownerId,
        ?\Closure $reasignar = null,
    ): void {
        if (
            !Schema::hasTable($tabla)
            || !Schema::hasColumn($tabla, 'owner_id')
            || !Schema::hasColumn($tabla, $columna)
        ) {
            return;
        }

        $duplicados = DB::table($tabla)
            ->whereNull('owner_id')
            ->whereNotNull($columna)
            ->get(['id', $columna]);

        foreach ($duplicados as $duplicado) {
            $existenteId = DB::table($tabla)
                ->where('owner_id', $ownerId)
                ->where($columna, $duplicado->{$columna})
                ->value('id');

            if (!$existenteId) {
                continue;
            }

            $reasignar?->__invoke((int) $duplicado->id, (int) $existenteId);

            DB::table($tabla)
                ->where('id', $duplicado->id)
                ->whereNull('owner_id')
                ->delete();
        }
    }

}
