<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crias', function (Blueprint $table) {
            $table->id();

            // Parto al que pertenece esta cría
            $table->foreignId('cria_id')
                ->constrained('crias')
                ->cascadeOnDelete();

            // Animal creado en el sistema para esta cría
            // Se llena automáticamente al registrar el parto si nace viva
            // Queda null si nació muerta
            $table->foreignId('animal_id')
                ->nullable()
                ->unique() // un animal solo puede ser cría de un parto
                ->constrained('animals')
                ->nullOnDelete();

            $table->enum('sexo', ['macho', 'hembra']);

            $table->decimal('peso_nacimiento', 5, 2)->nullable(); // kg

            $table->enum('condicion', [
                'vivo',
                'nacido_muerto',
                'murio_al_nacer', // nació vivo pero murió en las primeras horas
            ]);

            // Arete temporal si aún no tiene definitivo al momento del registro
            $table->string('arete_temporal', 50)->nullable();

            $table->text('observaciones')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crias');
    }
};