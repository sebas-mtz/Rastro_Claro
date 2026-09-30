<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cotización de un animal. Todos los montos son decimal para evitar los
 * errores de precisión de los tipos flotantes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_valuations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();

            // Buckets de costo (el desglose fino vive en animal_valuation_detalles)
            $table->decimal('costo_gestacion', 14, 2)->default(0);
            $table->decimal('costo_inicial', 14, 2)->default(0);
            $table->decimal('costo_sanitario', 14, 2)->default(0);
            $table->decimal('costo_alimentacion', 14, 2)->default(0);
            $table->decimal('costo_registro', 14, 2)->default(0);
            $table->decimal('costo_mano_obra', 14, 2)->default(0);
            $table->decimal('costo_transporte', 14, 2)->default(0);
            $table->decimal('otros_costos', 14, 2)->default(0);
            $table->decimal('costo_total_produccion', 14, 2)->default(0);

            // Margen genético guardado como porcentaje (50.00 = 50 %)
            $table->decimal('porcentaje_margen_genetico', 6, 2)->default(0);
            $table->decimal('valor_margen_genetico', 14, 2)->default(0);

            $table->string('estado_reproductivo_valuacion')->nullable();
            $table->decimal('plus_reproductivo', 14, 2)->default(0);

            $table->decimal('ajuste_manual', 14, 2)->default(0);
            $table->text('motivo_ajuste')->nullable();

            $table->decimal('precio_estimado', 14, 2)->default(0);
            $table->decimal('precio_publicado', 14, 2)->nullable();

            // borrador | activa | confirmada | cerrada
            $table->string('estado')->default('activa');

            // Datos de la venta real, cuando se confirma
            $table->decimal('precio_real_venta', 14, 2)->nullable();
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();

            $table->timestamp('calculado_en')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['animal_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_valuations');
    }
};
