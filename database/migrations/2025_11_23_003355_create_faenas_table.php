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
    Schema::create('faenas', function (Blueprint $table) {
        $table->id();
        $table->foreignId('animal_id')->constrained()->onDelete('cascade');
        $table->foreignId('lote_id')->nullable()->constrained()->onDelete('set null'); // ✅ LOTE
        $table->date('fecha');
        $table->enum('tipo_corte', ['completo', 'media', 'cortes', 'deshuesado']);
        $table->decimal('peso_canal', 8, 2);
        $table->decimal('peso_carne', 8, 2);
        $table->decimal('peso_cuero', 8, 2)->nullable();
        $table->decimal('peso_grasa', 8, 2)->nullable();
        $table->decimal('peso_plumas', 8, 2)->nullable(); // ✅ PLUMAS
        $table->decimal('peso_hueso', 8, 2)->nullable();
        $table->decimal('peso_visceras', 8, 2)->nullable();
        $table->decimal('rendimiento', 5, 2);
        $table->text('observaciones')->nullable();
        $table->decimal('costo_total', 15, 2)->default(0.00);
        $table->timestamps();
        
        // Índices para mejor performance
        $table->index('fecha');
        $table->index('lote_id');
    });
}
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faenas');
    }
};