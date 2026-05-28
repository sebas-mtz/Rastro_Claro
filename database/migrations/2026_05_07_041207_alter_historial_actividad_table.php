<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historial_actividad', function (Blueprint $table) {

            if (!Schema::hasColumn('historial_actividad', 'user_nombre')) {
                $table->string('user_nombre', 100)->nullable()->after('user_id');
            }

            if (!Schema::hasColumn('historial_actividad', 'descripcion')) {
                $table->string('descripcion', 500)->nullable()->after('accion');
            }

            if (!Schema::hasColumn('historial_actividad', 'modelo')) {
                $table->string('modelo', 80)->nullable();
            }

            if (!Schema::hasColumn('historial_actividad', 'modelo_id')) {
                $table->unsignedBigInteger('modelo_id')->nullable();
            }

            if (!Schema::hasColumn('historial_actividad', 'datos_antes')) {
                $table->json('datos_antes')->nullable();
            }

            if (!Schema::hasColumn('historial_actividad', 'datos_despues')) {
                $table->json('datos_despues')->nullable();
            }

            if (!Schema::hasColumn('historial_actividad', 'user_agent')) {
                $table->string('user_agent', 200)->nullable();
            }
        });
    }

    public function down(): void
    {
        //
    }
};