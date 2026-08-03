<?php

use App\Models\PuestoTrabajador;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Une los dos catálogos de puestos y les da permisos.
 *
 * Hasta ahora el puesto vivía en dos sitios: una constante de PHP para las
 * CUENTAS del sistema (users.puesto) y la tabla `puestos_trabajador` para las
 * PERSONAS del rancho. Dos listas con claves distintas que podían decir cosas
 * diferentes sobre el mismo oficio.
 *
 * Queda un solo catálogo, en la base de datos, editable. Y cada puesto lleva
 * ahora qué módulos puede tocar quien lo ocupa.
 *
 * Nada se pierde:
 *   · `users.puesto` (el texto original) se conserva intacta, igual que se
 *     hizo con `rol` y con `raza_original`.
 *   · Los puestos que solo existían en la lista de las cuentas se crean en el
 *     catálogo, pero únicamente si alguien los tiene asignados.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Columnas nuevas ────────────────────────────────────────────
        if (! Schema::hasColumn('puestos_trabajador', 'permisos')) {
            Schema::table('puestos_trabajador', function (Blueprint $table) {
                $table->json('permisos')->nullable()->after('descripcion');
            });
        }

        if (! Schema::hasColumn('users', 'puesto_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('puesto_id')
                    ->nullable()
                    ->after('puesto')
                    ->constrained('puestos_trabajador')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('users', 'permisos_extra')) {
            Schema::table('users', function (Blueprint $table) {
                // Excepciones sobre lo que da el puesto:
                // {"conceder": {"costos": ["ver"]}, "revocar": {"salud": ["eliminar"]}}
                $table->json('permisos_extra')->nullable()->after('puesto_id');
            });
        }

        // ── 2. Catálogo por rancho ────────────────────────────────────────
        // Un rancho es una cuenta que se apunta a sí misma. Los empleados no
        // tienen catálogo propio: usan el de su patrón.
        $ranchos = DB::table('users')
            ->select('id')
            ->where(fn ($q) => $q->whereColumn('cuenta_id', 'id')->orWhereNull('cuenta_id'))
            ->pluck('id');

        // Puestos heredados que alguien tiene realmente asignados.
        $enUso = DB::table('users')->whereNotNull('puesto')->distinct()->pluck('puesto')->all();

        $heredadosNecesarios = array_filter(
            PuestoTrabajador::HEREDADOS,
            fn ($p) => in_array($p['clave'], $enUso, true)
        );

        $aSembrar = array_merge(PuestoTrabajador::BASE, $heredadosNecesarios);

        foreach ($ranchos as $ranchoId) {
            foreach ($aSembrar as $puesto) {
                $existente = DB::table('puestos_trabajador')
                    ->where('owner_id', $ranchoId)
                    ->where('clave', $puesto['clave'])
                    ->first();

                $permisos = json_encode(PuestoTrabajador::permisosPorDefecto($puesto['clave']));

                if ($existente) {
                    // Ya existía: solo se le ponen permisos si no tenía.
                    // No se pisa nada que el rancho haya editado.
                    if ($existente->permisos === null) {
                        DB::table('puestos_trabajador')
                            ->where('id', $existente->id)
                            ->update(['permisos' => $permisos]);
                    }

                    continue;
                }

                DB::table('puestos_trabajador')->insert([
                    'owner_id' => $ranchoId,
                    'clave' => $puesto['clave'],
                    'nombre' => $puesto['nombre'],
                    'area' => $puesto['area'],
                    'permisos' => $permisos,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // ── 3. Enlazar cada cuenta con su puesto del catálogo ──────────────
        $usuarios = DB::table('users')
            ->select('id', 'cuenta_id', 'puesto')
            ->whereNotNull('puesto')
            ->whereNull('puesto_id')
            ->get();

        foreach ($usuarios as $usuario) {
            $ranchoId = $usuario->cuenta_id ?: $usuario->id;

            $puestoId = DB::table('puestos_trabajador')
                ->where('owner_id', $ranchoId)
                ->where('clave', $usuario->puesto)
                ->value('id');

            if ($puestoId) {
                DB::table('users')->where('id', $usuario->id)->update(['puesto_id' => $puestoId]);
            }
            // Si no hay correspondencia, `users.puesto` conserva el texto
            // original y el superadministrador puede reasignarlo a mano.
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'permisos_extra')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('permisos_extra');
            });
        }

        if (Schema::hasColumn('users', 'puesto_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('puesto_id');
            });
        }

        if (Schema::hasColumn('puestos_trabajador', 'permisos')) {
            Schema::table('puestos_trabajador', function (Blueprint $table) {
                $table->dropColumn('permisos');
            });
        }
    }
};
