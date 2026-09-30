<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trabajo realizado por una persona: qué hizo, cuándo, sobre qué ejemplar o
 * lote, cuánto tiempo le llevó y cuánto costó esa mano de obra.
 *
 * El costo se calcula SIEMPRE en el backend (ManoObraService) a partir de las
 * horas o jornadas y la tarifa vigente. Las tarifas se copian a la fila en el
 * momento del registro: si mañana sube el sueldo del trabajador, el costo
 * histórico no cambia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividades_trabajador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('trabajador_id')->constrained('trabajadores')->cascadeOnDelete();

            $table->string('tipo_actividad', 60);

            // A qué se aplicó el trabajo. Los tres son opcionales: hay labores
            // generales (limpieza, mantenimiento) que no cuelgan de nadie.
            $table->foreignId('animal_id')->nullable()->constrained('animals')->nullOnDelete();
            $table->foreignId('lote_id')->nullable()->constrained('lotes')->nullOnDelete();
            $table->foreignId('faena_id')->nullable()->constrained('faenas')->nullOnDelete();

            $table->date('fecha');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();

            // ── Tiempo y pago ──────────────────────────────────────────────
            // 'hora'    → costo = horas_trabajadas × costo_hora
            // 'jornada' → costo = jornadas × costo_jornada
            $table->string('modalidad_pago', 20)->default('hora');

            $table->decimal('horas_trabajadas', 6, 2)->nullable();
            $table->decimal('jornadas', 6, 2)->nullable();

            // Tarifas congeladas al momento del registro.
            $table->decimal('costo_hora', 10, 2)->nullable();
            $table->decimal('costo_jornada', 10, 2)->nullable();

            $table->decimal('costo_total', 12, 2)->default(0);

            // ── Distribución entre ejemplares ──────────────────────────────
            $table->unsignedInteger('animales_atendidos')->default(0);
            $table->decimal('costo_por_animal', 12, 2)->nullable();
            $table->boolean('distribuir_entre_animales')->default(false);
            // Deja escrito CÓMO se repartió, para que el desglose de valuación
            // pueda explicarlo en vez de mostrar una cifra sin origen.
            $table->string('metodo_distribucion', 120)->nullable();

            $table->text('descripcion')->nullable();
            $table->text('observaciones')->nullable();

            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['owner_id', 'fecha']);
            $table->index(['trabajador_id', 'fecha']);
            $table->index('tipo_actividad');
            $table->index('animal_id');
            $table->index('lote_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividades_trabajador');
    }
};
