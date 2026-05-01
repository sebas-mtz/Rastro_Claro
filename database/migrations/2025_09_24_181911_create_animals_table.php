<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('animals', function (Blueprint $table) {
            $table->id();
            $table->string('especie');
            $table->string('raza')->nullable();
            $table->string('arete');
            $table->string('alias')->nullable();
            $table->enum('sexo', ['M','F']);
            $table->date('fecha_nac')->nullable();
            $table->decimal('peso', 6, 2)->nullable();
            $table->decimal('BCS', 3, 1)->nullable();
            $table->string('estado_productivo')->nullable();
            $table->foreignId('lote_id')->nullable()->constrained('lotes')->onDelete('set null');
            $table->foreignId('madre_id')
          ->nullable()
          ->constrained('animals')
          ->nullOnDelete();

    $table->foreignId('padre_id')
          ->nullable()
          ->constrained('animals')
          ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('animals');
    }
};