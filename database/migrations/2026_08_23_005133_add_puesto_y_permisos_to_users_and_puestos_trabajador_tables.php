<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('puestos_trabajador', function (Blueprint $table) {
            $table->json('permisos')
                ->nullable()
                ->after('descripcion');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('puesto_id')
                ->nullable()
                ->constrained('puestos_trabajador')
                ->nullOnDelete();

            $table->json('permisos_extra')
                ->nullable()
                ->after('puesto_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('permisos_extra');
            $table->dropConstrainedForeignId('puesto_id');
        });

        Schema::table('puestos_trabajador', function (Blueprint $table) {
            $table->dropColumn('permisos');
        });
    }
};