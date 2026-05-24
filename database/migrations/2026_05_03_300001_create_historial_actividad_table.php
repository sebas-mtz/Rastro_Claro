<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Evitar error si la tabla ya existe
        if (!Schema::hasTable('historial_actividad')) {

            Schema::create('historial_actividad', function (Blueprint $table) {

                $table->id();

                // Quién
                $table->unsignedBigInteger('user_id')->nullable();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();

                $table->string('user_nombre', 100)->nullable();

                // Qué
                $table->string('modulo', 60);
                $table->string('accion', 40);
                $table->string('descripcion', 500);

                // Sobre qué
                $table->string('modelo', 80)->nullable();
                $table->unsignedBigInteger('modelo_id')->nullable();

                // Datos
                $table->json('datos_antes')->nullable();
                $table->json('datos_despues')->nullable();

                // Contexto
                $table->string('ip', 45)->nullable();
                $table->string('user_agent', 200)->nullable();

                $table->timestamp('created_at')->useCurrent();

                // Índices
                $table->index(['user_id', 'created_at']);
                $table->index(['modulo', 'created_at']);
                $table->index(['modelo', 'modelo_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_actividad');
    }
};
