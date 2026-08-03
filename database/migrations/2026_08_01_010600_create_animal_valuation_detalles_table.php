<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cada movimiento que compone un bucket de la cotización. Guarda de qué
 * registro salió (origen_tipo/origen_id) para que el usuario pueda abrir
 * el detalle y ver exactamente por qué se sumó ese monto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_valuation_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('valuation_id')->constrained('animal_valuations')->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();

            $table->string('categoria');
            $table->string('concepto');
            $table->text('descripcion')->nullable();
            $table->date('fecha')->nullable();

            $table->decimal('cantidad', 12, 2)->nullable();
            $table->string('unidad')->nullable();
            $table->decimal('costo_unitario', 12, 2)->nullable();
            $table->decimal('costo_total', 14, 2)->default(0);

            $table->string('origen_tipo')->nullable();
            $table->unsignedBigInteger('origen_id')->nullable();

            $table->boolean('es_automatico')->default(true);

            // Explica cómo se repartió un costo compartido (gestación entre crías,
            // alimento de lote entre animales). Se muestra tal cual en la interfaz.
            $table->string('metodo_distribucion')->nullable();

            $table->text('observaciones')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['valuation_id', 'categoria']);
            $table->index(['origen_tipo', 'origen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_valuation_detalles');
    }
};
