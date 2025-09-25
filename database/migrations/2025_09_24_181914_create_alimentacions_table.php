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
        Schema::create('alimentacions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->nullable()->constrained('animals')->onDelete('cascade');
            $table->foreignId('lote_id')->nullable()->constrained('lotes')->onDelete('cascade');
            $table->date('fecha');
            $table->foreignId('racion_id')->nullable()->constrained('racions')->onDelete('set null');
            $table->decimal('consumo_kg', 6, 2)->nullable();
            $table->decimal('costo', 8, 2)->nullable();
            $table->foreignId('proveedor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alimentacions');
    }
};
