<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Retira el plan Premium del sistema.
 *
 * El plan solo servía para abrir el módulo de Predicciones, que se eliminó.
 * Sin él, la columna quedaba prometiendo una distinción que no existía: dos
 * planes con exactamente las mismas funciones.
 *
 * Antes de aplicarla se comprobó que ninguna cuenta era Premium, así que no se
 * pierde ningún acceso. El `down()` restituye la columna con los valores que
 * había, por si algún día se retoma la idea.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'plan')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('plan');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'plan')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('plan')->nullable()->after('activo');
        });

        DB::table('users')->update(['plan' => 'normal']);
    }
};
