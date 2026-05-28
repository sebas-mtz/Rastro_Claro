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

            // Evento padre — relación 1 a 1
            $table->foreignId('evento_id')
                ->unique()
                ->constrained('evento_reproductivos')
                ->cascadeOnDelete();

            // Toro si es monta natural — FK a animals
            $table->foreignId('macho_id')
                ->nullable()
                ->constrained('animals')
                ->nullOnDelete();

            // Tipo de servicio
            $table->enum('tipo_servicio', [
                'monta_natural',
                'inseminacion_artificial',
                'iatf', // Inseminación a tiempo fijo (sincronización)
            ]);

            // Datos de pajilla — solo aplica si es IA o IATF
            $table->string('pajilla_codigo', 100)->nullable();
            $table->string('pajilla_raza', 80)->nullable();
            $table->string('pajilla_origen', 100)->nullable(); // país o empresa

            // Técnico que realizó la IA — puede ser usuario del sistema o nombre externo
            $table->foreignId('tecnico_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('tecnico_externo', 100)->nullable(); // si no es usuario del sistema

            // Número de servicio en el ciclo actual (1er intento, 2do, 3er...)
            // Útil para calcular servicios por concepción
            $table->tinyInteger('numero_servicio')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicio_reproductivos');
    }
};