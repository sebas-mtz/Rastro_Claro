<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bitácora de precios. Cada cambio se agrega como un movimiento nuevo:
 * el precio anterior nunca se sobrescribe y la interfaz no expone borrado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_valuation_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('valuation_id')->constrained('animal_valuations')->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();

            $table->decimal('precio_anterior', 14, 2)->nullable();
            $table->decimal('precio_nuevo', 14, 2)->default(0);
            $table->decimal('diferencia', 14, 2)->default(0);

            $table->text('motivo')->nullable();

            // creacion | recalculo | nuevo_gasto | cambio_margen | cambio_reproductivo
            // | ajuste_manual | confirmacion_venta
            $table->string('tipo_movimiento');

            // Registro que disparó el cambio (una vacuna, un costo, una alimentación...)
            $table->string('referencia_tipo')->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->string('concepto')->nullable();
            $table->decimal('valor_movimiento', 14, 2)->nullable();

            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();

            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();

            $table->timestamps();

            $table->index(['animal_id', 'created_at']);
            $table->index('tipo_movimiento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_valuation_historial');
    }
};
