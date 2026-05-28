<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programacion_alimentacions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('racion_id')
                ->constrained('racions')
                ->cascadeOnDelete();

            $table->foreignId('animal_id')
                ->nullable()
                ->constrained('animals')
                ->nullOnDelete();

            $table->foreignId('lote_id')
                ->nullable()
                ->constrained('lotes')
                ->nullOnDelete();

            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();

            $table->time('hora');

            $table->decimal('cantidad', 10, 2);
            $table->string('unidad', 20);

            $table->enum('frecuencia', ['una_vez', 'diaria'])->default('diaria');

            $table->boolean('activa')->default(true);

            $table->text('notas')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programacion_alimentacions');
    }
};