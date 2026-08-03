<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Completa el registro de pesaje con la información que pide el manejo ovino.
 * La unidad queda en 'kg' por defecto para los 2,007 pesajes ya capturados,
 * que se registraron en kilogramos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pesajes', function (Blueprint $table) {
            $table->string('unidad', 10)->default('kg')->after('peso');
            $table->decimal('condicion_corporal', 2, 1)->nullable()->after('unidad');
            $table->string('metodo')->nullable()->after('condicion_corporal');   // bascula, cinta, estimacion
            $table->string('responsable')->nullable()->after('metodo');
        });
    }

    public function down(): void
    {
        Schema::table('pesajes', function (Blueprint $table) {
            $table->dropColumn(['unidad', 'condicion_corporal', 'metodo', 'responsable']);
        });
    }
};
