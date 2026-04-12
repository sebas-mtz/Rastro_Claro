<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alimentacions', function (Blueprint $table) {
            $table->id();

            // 📅 CUÁNDO
            $table->date('fecha');
            $table->time('hora')->nullable(); // 🔥 importante para programación

            // 📦 DATOS DE CONSUMO
            $table->decimal('cantidad', 10, 2);
            $table->string('unidad', 20);

            // 🐄 DESTINO (solo uno)
            $table->foreignId('animal_id')
                ->nullable()
                ->constrained('animals')
                ->nullOnDelete();

            $table->foreignId('lote_id')
                ->nullable()
                ->constrained('lotes')
                ->nullOnDelete();

            // 🍽️ RACIÓN
            $table->foreignId('racion_id')
                ->nullable()
                ->constrained('racions')
                ->nullOnDelete();

            // 🔁 PROGRAMACIÓN (si fue automática)
            $table->foreignId('programacion_alimentacion_id')
                ->nullable()
                ->constrained('programacion_alimentacions')
                ->nullOnDelete();

            $table->boolean('generado_automaticamente')
                ->default(false);
                $table->json('snapshot_composicion')->nullable();
 
                // Guarda los valores nutrimentales calculados en el momento del consumo.
                // Estructura: { MS, PB, EM, FDN }
                $table->json('snapshot_nutricion')->nullable();
            // 📝 EXTRA
            $table->string('tipo')->nullable(); // puedes dejarlo opcional
            $table->text('notas')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alimentacions');
    }
};