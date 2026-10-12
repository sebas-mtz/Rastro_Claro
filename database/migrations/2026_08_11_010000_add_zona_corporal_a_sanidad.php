<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * En qué parte del cuerpo se hizo la atención sanitaria.
 *
 * Hasta ahora esa información, si acaso, quedaba dentro del texto del
 * diagnóstico («cojera pata trasera derecha»). Con un campo propio se puede
 * dibujar sobre la figura del ejemplar y consultar después.
 *
 * Es opcional a propósito: hay atenciones que no van a ninguna parte concreta
 * —una desparasitación, unas vitaminas— y obligar a elegir una zona llevaría a
 * que la gente ponga cualquier cosa con tal de guardar.
 *
 * Cuando se llena, un observador crea la marca corporal correspondiente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventos_salud', function (Blueprint $table) {
            $table->string('zona_corporal', 40)->nullable()->after('observaciones');
        });

        Schema::table('tratamientos', function (Blueprint $table) {
            $table->string('zona_corporal', 40)->nullable()->after('notas');
        });
    }

    public function down(): void
    {
        Schema::table('eventos_salud', function (Blueprint $table) {
            $table->dropColumn('zona_corporal');
        });

        Schema::table('tratamientos', function (Blueprint $table) {
            $table->dropColumn('zona_corporal');
        });
    }
};
