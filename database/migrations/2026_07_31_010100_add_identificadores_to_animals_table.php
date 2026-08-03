<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->string('microchip_codigo')->nullable()->unique()->after('arete');
            $table->string('tipo_identificador')->nullable()->after('microchip_codigo');
            $table->date('fecha_colocacion_microchip')->nullable()->after('tipo_identificador');
            $table->string('estado_microchip')->nullable()->after('fecha_colocacion_microchip');
            $table->text('observaciones_microchip')->nullable()->after('estado_microchip');
            $table->string('qr_token')->nullable()->unique()->after('observaciones_microchip');
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropColumn([
                'microchip_codigo',
                'tipo_identificador',
                'fecha_colocacion_microchip',
                'estado_microchip',
                'observaciones_microchip',
                'qr_token',
            ]);
        });
    }
};
