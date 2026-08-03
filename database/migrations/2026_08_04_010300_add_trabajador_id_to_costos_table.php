<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enlaza un costo con la persona cuya mano de obra lo generó.
 *
 * El vínculo con el registro original ya lo da el morph origen_tipo/origen_id,
 * pero esa columna no se puede filtrar ni agrupar sin resolver la relación.
 * Con `trabajador_id` el módulo de costos puede responder directamente
 * "cuánto ha costado la mano de obra de esta persona" con un solo índice.
 *
 * Aditiva y nullable: los costos que no son de mano de obra la dejan vacía.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('costos', 'trabajador_id')) {
            return;
        }

        Schema::table('costos', function (Blueprint $table) {
            $table->foreignId('trabajador_id')
                ->nullable()
                ->after('sacrificio_id')
                ->constrained('trabajadores')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('costos', 'trabajador_id')) {
            return;
        }

        Schema::table('costos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('trabajador_id');
        });
    }
};
