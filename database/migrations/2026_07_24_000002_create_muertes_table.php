<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muertes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->date('fecha');
            $table->string('causa');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['owner_id', 'animal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muertes');
    }
};
