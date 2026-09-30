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
        Schema::create('pajillas', function (Blueprint $table) {
            $table->id();
        
            $table->foreignId('termo_id')
                ->constrained('termos')
                ->cascadeOnDelete();
        
            $table->foreignId('animal_id')
                ->nullable()
                ->constrained('animals')
                ->nullOnDelete();
        
            $table->string('codigo')->unique();
            $table->string('lote')->nullable();
            $table->decimal('capacidad_pajilla', 8, 2);
            $table->date('fecha_ingreso')->nullable();
            $table->date('fecha_colecta')->nullable();
            $table->date('fecha_utilizacion')->nullable();
        $table->unsignedInteger('canastilla_numero')->nullable();
            $table->enum('estado', [
                'disponible',
                'utilizada',
                'dañada',
            ])->default('disponible');
        
            $table->text('observaciones')->nullable();
        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pajillas');
    }
};
