<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostico_gestacions', function (Blueprint $table) {
            $table->id();

            // Evento padre — relación 1 a 1
            $table->foreignId('evento_id')
                ->unique()
                ->constrained('evento_reproductivos')
                ->cascadeOnDelete();

            // A qué servicio responde este diagnóstico
            // Permite calcular tasa de concepción por Semental, técnico, tipo de servicio
            $table->foreignId('servicio_evento_id')
                ->nullable()
                ->constrained('evento_reproductivos')
                ->nullOnDelete();

            // Cómo se hizo el diagnóstico
            $table->enum('metodo', [
                'tacto_rectal',
                'ultrasonido',
                'laboratorio',
            ]);

            // Resultado del diagnóstico
            $table->enum('resultado', [
                'positivo',      // → vaca queda como gestante
                'negativo',      // → vaca regresa a vacía
                'repetir',       // → pendiente, repetir diagnóstico
            ])->index();

            // Días de gestación estimados — solo disponible por ultrasonido
            $table->tinyInteger('dias_gestacion_estimados')->nullable();

            // Fecha probable de parto calculada desde este diagnóstico
            // 283 días desde la fecha del servicio vinculado
            $table->date('fecha_probable_parto')->nullable();

            // Veterinario que realizó el diagnóstico
            $table->foreignId('veterinario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('veterinario_externo', 100)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostico_gestacions');
    }
};