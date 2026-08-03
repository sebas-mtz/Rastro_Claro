<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca de permanencia en el rebaño.
 *
 * Todos los ejemplares existentes quedan como activos (default true): la baja
 * solo se apaga cuando se registra una salida explícita.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('etapa_vida_confirmada_at');
            $table->date('fecha_baja')->nullable()->after('activo');

            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropIndex(['activo']);
            $table->dropColumn(['activo', 'fecha_baja']);
        });
    }
};
