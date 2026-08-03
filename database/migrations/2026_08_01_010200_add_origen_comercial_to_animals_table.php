<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distingue si el animal nació en la unidad productiva o fue comprado.
 * La valuación usa este dato para no aplicar a la vez costo de adquisición
 * y costo estimado de nacimiento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->string('tipo_origen')->nullable()->after('qr_token');
            $table->date('fecha_adquisicion')->nullable()->after('tipo_origen');
            $table->string('proveedor_origen')->nullable()->after('fecha_adquisicion');
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropColumn(['tipo_origen', 'fecha_adquisicion', 'proveedor_origen']);
        });
    }
};
