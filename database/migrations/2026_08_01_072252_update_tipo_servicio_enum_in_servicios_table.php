<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('servicio_reproductivos', function (Blueprint $table) {
        $table->enum('tipo_servicio', [
            'monta_natural',
            'monta_controlada',
            'inseminacion_artificial',
            'iatf',
            'transferencia_embriones',
            'fiv',
        ])->change();
    });
}

public function down(): void
{
    Schema::table('servicio_reproductivos', function (Blueprint $table) {
        $table->enum('tipo_servicio', [
            'monta_natural',
            'inseminacion_artificial',
            'iatf',
        ])->change();
    });
}
};
