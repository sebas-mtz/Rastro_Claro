<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Puesto del trabajador dentro del manejo ovino.
 *
 * Es independiente del `role` (admin/user), que controla permisos: el puesto
 * describe la función en el rancho y permite relacionar la mano de obra con
 * las actividades reales del rebaño.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('puesto')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('puesto');
        });
    }
};
