<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destetes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('evento_id')->constrained('evento_reproductivos')->cascadeOnDelete();
            $table->foreignId('parto_id')->constrained('partos')->cascadeOnDelete();
            $table->enum('estado_madre', ['bueno', 'regular', 'malo']);
            $table->string('tipo_nacimiento', 20);
            $table->timestamps();

            $table->unique(['owner_id', 'evento_id']);
            $table->unique(['owner_id', 'parto_id']);
        });

        Schema::create('destete_crias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('destete_id')->constrained('destetes')->cascadeOnDelete();
            $table->foreignId('cria_id')->constrained('crias')->cascadeOnDelete();
            $table->decimal('peso_destete', 8, 2)->nullable();
            $table->string('estado_destino', 100);
            $table->timestamps();

            $table->unique(['owner_id', 'destete_id', 'cria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destete_crias');
        Schema::dropIfExists('destetes');
    }
};
