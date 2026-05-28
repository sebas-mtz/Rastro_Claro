<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partos', function (Blueprint $table) {
            $table->id();

            // Evento padre — relación 1 a 1
            $table->foreignId('evento_id')
                ->unique()
                ->constrained('evento_reproductivos')
                ->cascadeOnDelete();

            // Servicio que originó este parto — permite cerrar el ciclo completo
            // servicio → diagnóstico positivo → parto
            $table->foreignId('servicio_evento_id')
                ->nullable()
                ->constrained('evento_reproductivos')
                ->nullOnDelete();

            // Tipo y condición del parto
            $table->enum('tipo_parto', [
                'normal',
                'distocico',  // parto difícil
                'cesarea',
            ]);

            $table->boolean('asistencia_requerida')->default(false);
            $table->boolean('complicaciones')->default(false);
            $table->text('detalle_complicaciones')->nullable();

            // Cuántas crías nacieron — cada una tiene su fila en offspring
            $table->tinyInteger('numero_crias')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partos');
    }
};