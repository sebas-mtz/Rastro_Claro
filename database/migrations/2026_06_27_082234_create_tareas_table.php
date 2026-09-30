<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tareas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asignado_a')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('creado_por')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('titulo', 150);
            $table->text('descripcion')->nullable();

            $table->dateTime('fecha_recordatorio');

            $table->enum('estado', [
                'pendiente',
                'completada',
                'suspendida',
            ])->default('pendiente');

            $table->dateTime('completada_en')->nullable();
            $table->dateTime('suspendida_en')->nullable();

            // Evita enviar varias veces la misma notificación.
            $table->dateTime('notificada_en')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['asignado_a', 'estado']);
            $table->index(['fecha_recordatorio', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tareas');
    }
};