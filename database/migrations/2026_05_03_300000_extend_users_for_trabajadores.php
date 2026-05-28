<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extiende users para el sistema de trabajadores completo.
 * Compatible con la tabla existente (usa hasColumn para evitar errores).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'telefono'))
                $table->string('telefono', 20)->nullable()->after('email');

            // Si 'role' ya existe (agregado en migración anterior), no duplicar
            if (!Schema::hasColumn('users', 'role'))
                $table->string('role')->default('trabajador')->after('telefono');

            if (!Schema::hasColumn('users', 'creado_por')) {
                $table->unsignedBigInteger('creado_por')->nullable()->after('role');
                $table->foreign('creado_por')->references('id')->on('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'ultimo_login'))
                $table->timestamp('ultimo_login')->nullable()->after('creado_por');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumnIfExists('telefono');
            $table->dropColumnIfExists('creado_por');
            $table->dropColumnIfExists('ultimo_login');
        });
    }
};
