<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Especializa el módulo de sanidad para ovinos.
 *
 * El periodo de retiro es el dato de seguridad alimentaria más importante que
 * faltaba: indica cuántos días después de aplicar un medicamento no se puede
 * destinar el ejemplar (o su leche) al consumo.
 *
 * La columna `tipo` ya era string libre, así que los tipos ovinos nuevos no
 * requieren cambiarla: se validan desde el modelo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventos_salud', function (Blueprint $table) {
            $table->unsignedSmallInteger('periodo_retiro_dias')->nullable()->after('costo');
            $table->date('fecha_fin_retiro')->nullable()->after('periodo_retiro_dias');
            $table->string('producto')->nullable()->after('fecha_fin_retiro');
            $table->string('via_administracion')->nullable()->after('producto');
        });
    }

    public function down(): void
    {
        Schema::table('eventos_salud', function (Blueprint $table) {
            $table->dropColumn([
                'periodo_retiro_dias',
                'fecha_fin_retiro',
                'producto',
                'via_administracion',
            ]);
        });
    }
};
