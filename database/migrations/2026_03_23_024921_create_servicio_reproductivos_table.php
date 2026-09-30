<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicio_reproductivos', function (Blueprint $table) {
            $table->id();

            // Evento reproductivo (1 a 1)
            $table->foreignId('evento_id')
                ->unique()
                ->constrained('evento_reproductivos')
                ->cascadeOnDelete();

            // Tipo de servicio
            $table->enum('tipo_servicio', [
                'monta_natural',
                'inseminacion_artificial',
                'iatf',
            ]);

            // Solo para monta natural
            $table->foreignId('macho_id')
                ->nullable()
                ->constrained('animals')
                ->nullOnDelete();

            // Solo para IA e IATF
            $table->foreignId('pajilla_id')
                ->nullable()
                ->constrained('pajillas')
                ->nullOnDelete();

            // Técnico que realizó el servicio
            $table->foreignId('tecnico_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Si el técnico no pertenece al sistema
            $table->string('tecnico_externo', 100)->nullable();

            // Número de servicio (1°, 2°, 3°...)
            $table->tinyInteger('numero_servicio')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicio_reproductivos');
    }
};