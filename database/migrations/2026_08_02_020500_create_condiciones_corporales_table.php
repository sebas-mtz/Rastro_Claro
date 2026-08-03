<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial de condición corporal (CC) del ejemplar.
 *
 * Escala 1 a 5 con medios puntos, la habitual en ovinos:
 *   1.0 Muy delgada  ·  2.0 Delgada  ·  3.0 Óptima  ·  4.0 Gorda  ·  5.0 Obesa
 *
 * Cuando la CC se captura junto con un pesaje, la fila queda ligada a ese
 * pesaje por origen_tipo/origen_id para no duplicar el registro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condiciones_corporales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();

            $table->date('fecha');
            $table->decimal('calificacion', 2, 1);   // 1.0 – 5.0
            $table->string('etapa_reproductiva')->nullable();
            $table->string('responsable')->nullable();
            $table->text('observaciones')->nullable();
            $table->text('recomendacion')->nullable();

            $table->string('origen_tipo')->nullable();
            $table->unsignedBigInteger('origen_id')->nullable();

            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['animal_id', 'fecha']);
            $table->index(['origen_tipo', 'origen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condiciones_corporales');
    }
};
