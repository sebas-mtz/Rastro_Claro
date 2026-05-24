<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crias', function (Blueprint $table) {
            $table->id();
        
            // ✅ RELACIÓN CORRECTA
            $table->foreignId('parto_id')
                ->constrained('partos')
                ->cascadeOnDelete();
        
            $table->foreignId('animal_id')
                ->nullable()
                ->unique()
                ->constrained('animals')
                ->nullOnDelete();
        
            $table->enum('sexo', ['macho', 'hembra']);
        
            $table->decimal('peso_nacimiento', 5, 2)->nullable();
        
            $table->enum('condicion', [
                'vivo',
                'nacido_muerto',
                'murio_al_nacer',
            ]);
        
            $table->string('arete_temporal', 50)->nullable();
        
            $table->text('observaciones')->nullable();
        
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crias');
    }
};