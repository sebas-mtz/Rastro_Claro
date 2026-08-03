<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tipifica los lotes según el manejo ovino (crías lactantes, reproductoras,
 * gestantes, sementales, engorda, cuarentena, enfermería, venta, descarte...).
 *
 * Queda nullable: los lotes existentes conservan su nombre y no se les asigna
 * un tipo automáticamente, porque adivinarlo desde el nombre sería inventar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotes', function (Blueprint $table) {
            $table->string('tipo')->nullable()->after('nombre');
            $table->integer('capacidad')->nullable()->after('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('lotes', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'capacidad']);
        });
    }
};
