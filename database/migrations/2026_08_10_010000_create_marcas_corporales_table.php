<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marcas sobre el cuerpo del ejemplar.
 *
 * Sirven para señalar DÓNDE pasó algo: en qué parte tiene la lesión, dónde se
 * le aplicó la vacuna, en qué oreja trae el arete. Hasta ahora esa información
 * vivía en un campo de texto («cojera pata trasera derecha») y no se podía
 * consultar ni ver de un vistazo.
 *
 * La zona se guarda como clave anatómica, no como coordenada. La posición en
 * la figura es cosa de la interfaz: si mañana cambia el modelo 3D, las marcas
 * siguen valiendo porque «pata trasera derecha» no depende del dibujo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marcas_corporales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();

            $table->string('zona', 40);
            $table->string('tipo', 30);
            $table->text('descripcion')->nullable();
            $table->date('fecha');
            $table->string('estado', 20)->default('activa');
            $table->date('fecha_resolucion')->nullable();

            // Registro del que proviene la marca, cuando nace de un evento de
            // salud o un tratamiento en vez de capturarse a mano.
            $table->string('origen_tipo')->nullable();
            $table->unsignedBigInteger('origen_id')->nullable();

            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['animal_id', 'estado']);
            $table->index(['origen_tipo', 'origen_id']);
            $table->index('zona');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marcas_corporales');
    }
};
