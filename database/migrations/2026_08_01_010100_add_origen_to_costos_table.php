<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite que una fila de `costos` declare de qué registro proviene
 * (un EventoSalud, un Tratamiento, una Alimentacion...). El servicio de
 * valuación usa este par para no contar dos veces el mismo gasto cuando
 * existe tanto en el módulo de origen como en el módulo de costos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('costos', function (Blueprint $table) {
            $table->string('origen_tipo')->nullable()->after('sacrificio_id');
            $table->unsignedBigInteger('origen_id')->nullable()->after('origen_tipo');

            $table->index(['origen_tipo', 'origen_id']);
        });
    }

    public function down(): void
    {
        Schema::table('costos', function (Blueprint $table) {
            $table->dropIndex(['origen_tipo', 'origen_id']);
            $table->dropColumn(['origen_tipo', 'origen_id']);
        });
    }
};
