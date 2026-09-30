<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->date('fecha');
            $table->string('tipo_salida'); // ver Baja::TIPOS para los 7 valores válidos
            $table->string('causa')->nullable();
            $table->text('diagnostico')->nullable();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('precio_salida', 10, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->string('documento')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['animal_id', 'tipo_salida']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bajas');
    }
};