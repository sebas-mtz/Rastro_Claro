<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('costos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained('animals')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('fecha');
            $table->enum('etapa', [
                'lactante',
                'destete',
                'desarrollo_crecimiento',
                'primer_empadre_monta',
                'gestacion',
                'lactacion',
                'periodo_seco',
                'adulta_mantenimiento',
            ]);
            $table->enum('categoria', ['alimentacion', 'salud', 'manejo', 'otros']);
            $table->string('concepto');
            $table->decimal('costo', 10, 2);
            $table->text('observaciones')->nullable();
            // Para evitar duplicados de gastos médicos importados desde Salud
            $table->string('origen')->default('manual'); // 'manual' | 'salud'
            $table->unsignedBigInteger('origen_id')->nullable(); // ID del EventoSalud o Tratamiento
            $table->timestamps();

            // Índice para prevenir duplicados importados de Salud
            $table->unique(['origen', 'origen_id'], 'unique_origen_importado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('costos');
    }
};
