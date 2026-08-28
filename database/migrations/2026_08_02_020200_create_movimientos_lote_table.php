<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial de movimientos de ejemplares entre lotes.
 *
 * El lote anterior nunca se pierde: cada cambio queda registrado con su fecha,
 * motivo y responsable. Esto además permitirá, más adelante, prorratear el
 * costo de alimentación de lote por el periodo real de permanencia.
 *
 * El historial arranca vacío a propósito: no se puede reconstruir hacia atrás
 * porque el sistema nunca guardó cuándo entró cada ejemplar a su lote actual.
 * Se registra de aquí en adelante.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_lote', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();

            $table->foreignId('lote_anterior_id')->nullable()->constrained('lotes')->nullOnDelete();
            $table->foreignId('lote_nuevo_id')->nullable()->constrained('lotes')->nullOnDelete();

            $table->date('fecha');
            $table->string('motivo')->nullable();
            $table->text('observaciones')->nullable();

            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['animal_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_lote');
    }
};
