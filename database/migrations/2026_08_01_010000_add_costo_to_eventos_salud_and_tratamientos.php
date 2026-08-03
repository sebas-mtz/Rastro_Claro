<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El módulo de salud registraba el evento pero nunca su costo, así que el
 * costo sanitario de una valuación siempre habría sido cero. Se agrega el
 * monto en el punto natural de captura (donde se registra la vacuna o el
 * tratamiento). La deduplicación contra la tabla `costos` se resuelve con
 * el morph de origen que agrega la migración siguiente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventos_salud', function (Blueprint $table) {
            $table->decimal('costo', 10, 2)->nullable()->after('dosis');
        });

        Schema::table('tratamientos', function (Blueprint $table) {
            $table->decimal('costo', 10, 2)->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('eventos_salud', function (Blueprint $table) {
            $table->dropColumn('costo');
        });

        Schema::table('tratamientos', function (Blueprint $table) {
            $table->dropColumn('costo');
        });
    }
};
