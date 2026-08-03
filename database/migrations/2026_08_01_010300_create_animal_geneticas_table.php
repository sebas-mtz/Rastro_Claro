<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos de registro y calidad genética del animal (1:1). Solo información
 * descriptiva y el porcentaje de margen: el dinero del registro vive en la
 * tabla `costos` con la categoría registro_genetico, para que exista una
 * sola fuente de verdad para montos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_geneticas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('animal_id')->unique()->constrained('animals')->cascadeOnDelete();

            $table->decimal('porcentaje_pureza', 5, 2)->nullable();
            $table->string('numero_registro')->nullable();
            $table->string('asociacion')->nullable();
            $table->string('certificado_pureza')->nullable();
            $table->string('linea_genetica')->nullable();
            $table->string('calidad_fenotipica')->nullable();
            $table->string('aplomos')->nullable();
            $table->text('caracteristicas_destacadas')->nullable();
            $table->text('premios')->nullable();

            // Margen sugerido para este animal. Se guarda como porcentaje (50.00 = 50 %),
            // nunca como decimal ambiguo; la división entre 100 ocurre en el servicio.
            $table->decimal('porcentaje_margen_genetico', 6, 2)->default(0);

            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_geneticas');
    }
};
