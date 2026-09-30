<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sacrificios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained()->onDelete('cascade');
            $table->foreignId('lote_id')->nullable()->constrained()->onDelete('set null'); // ✅ LOTE
            $table->date('fecha');
            $table->enum('motivo', ['descarte', 'enfermedad', 'accidente', 'autoconsumo']);
            $table->decimal('peso_vivo', 8, 2);
            $table->decimal('peso_canal', 8, 2);
            $table->decimal('rendimiento', 5, 2);
            $table->boolean('cuero')->default(false);
            $table->boolean('grasa')->default(false);
            $table->boolean('visceras')->default(false);
            $table->boolean('plumas')->default(false); // ✅ PLUMAS
            $table->text('observaciones')->nullable();
            $table->timestamps();
            
            $table->index(['fecha', 'motivo']);
            $table->index('lote_id');
    
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sacrificios');
    }
};