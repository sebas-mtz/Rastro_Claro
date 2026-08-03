<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Separa dos conceptos que hasta ahora eran el mismo: la PERSONA y el RANCHO.
 *
 * Todo el aislamiento del sistema se apoya en `owner_id`, y ese valor siempre
 * se tomaba de Auth::id(). Es decir, cada usuario era su propio rancho y no
 * había forma de que dos personas trabajaran sobre los mismos animales.
 *
 * `cuenta_id` responde a "¿a qué rancho pertenece esta persona?":
 *
 *   · El dueño de un rancho apunta a sí mismo   (cuenta_id = id)
 *   · Un empleado apunta a su patrón            (cuenta_id = id del dueño)
 *
 * Al crear la columna se rellena con el propio id de cada usuario, así que el
 * día de la migración NO cambia ninguna conducta: cuenta_id == id para todos y
 * cada quien sigue viendo exactamente lo que veía. A partir de ahí, sumar a
 * alguien a un rancho es cambiar un solo campo.
 *
 * Es autorreferente y nullable a propósito: `nullOnDelete` evitaría dejar
 * filas apuntando a una cuenta borrada, y el modelo trata el null como
 * "soy mi propio rancho".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'cuenta_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('cuenta_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('cuenta_id');
        });

        // Cada usuario existente queda como dueño de su propio rancho.
        DB::table('users')->update(['cuenta_id' => DB::raw('id')]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'cuenta_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cuenta_id');
        });
    }
};
