<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de salida del rebaño.
 *
 * Un ejemplar dado de baja deja de contar como activo, pero su historial
 * completo (pesajes, sanidad, costos, valuación, genealogía) se conserva.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();

            $table->date('fecha');
            $table->string('tipo_salida');   // venta, fallecimiento, sacrificio, descarte_reproductivo,
                                             // robo, extravio, donacion, traslado, otra
            $table->string('causa')->nullable();
            $table->text('diagnostico')->nullable();

            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('precio_salida', 14, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->string('documento')->nullable();   // ruta del comprobante o evidencia

            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['animal_id', 'fecha']);
            $table->index('tipo_salida');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bajas');
    }
};
