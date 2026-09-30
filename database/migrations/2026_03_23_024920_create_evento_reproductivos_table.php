<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evento_reproductivos', function (Blueprint $table) {
            $table->id();

            // Hembra a la que pertenece el evento — siempre requerida
            $table->foreignId('hembra_id')
                ->constrained('animals')
                ->cascadeOnDelete();

            // Lote al momento del evento — opcional, puede cambiar
            $table->foreignId('lote_id')
                ->nullable()
                ->constrained('lotes')
                ->nullOnDelete();

            // Usuario que registró el evento
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Tipo de evento — determina qué tabla de detalle tiene datos
            $table->enum('tipo_evento', [
                'celo',          // → sin tabla de detalle, solo fecha y observaciones
                'servicio',      // → reproductive_services
                'diagnostico',   // → pregnancy_diagnoses
                'parto',         // → births + offspring
                'aborto',        // → sin tabla de detalle
                'destete',       // → sin tabla de detalle
            ])->index();

            $table->date('fecha')->index();

            // Costo del evento (inseminación, veterinario, etc.)
            $table->decimal('costo', 8, 2)->nullable();

            $table->text('observaciones')->nullable();

            $table->timestamps();

            // Índice compuesto para historial por vaca ordenado por fecha
            $table->index(['hembra_id', 'fecha']);
            $table->index(['hembra_id', 'tipo_evento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evento_reproductivos');
    }
};