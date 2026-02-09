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
        Schema::create('saluds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained('animals')->onDelete('cascade');
            $table->date('fecha_programada');
            $table->string('diagnostico');
            $table->string('tratamiento')->nullable();
            $table->foreignId('vacuna_id')->nullable()->constrained('vacunas')->cascadeOnDelete();
            $table->string('dosis')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saluds');
    }
};
