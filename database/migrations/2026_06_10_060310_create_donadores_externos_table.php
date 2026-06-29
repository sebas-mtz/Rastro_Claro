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
        Schema::create('donadores_externos', function (Blueprint $table) {
            $table->id();
        
            $table->string('codigo')->unique();
            $table->string('nombre');
        
            $table->string('raza')->nullable();
            $table->string('proveedor')->nullable();
            $table->string('registro_genealogico')->nullable();
            $table->string('pais_origen')->nullable();
        
            $table->text('observaciones')->nullable();
        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donadores_externos');
    }
};
