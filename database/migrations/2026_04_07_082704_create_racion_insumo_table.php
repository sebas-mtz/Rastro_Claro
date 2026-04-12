<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('racion_insumo', function (Blueprint $table) {
            $table->id();

            $table->foreignId('racion_id')
                ->constrained('racions')
                ->cascadeOnDelete();

            $table->foreignId('inventario_insumo_id') // nombre correcto para que coincida con el modelo
                ->constrained('inventario_insumos')
                ->cascadeOnDelete();

            $table->decimal('cantidad', 10, 2); // cantidad del insumo en la ración

            $table->timestamps();

            // evita duplicados (misma ración + mismo insumo)
            $table->unique(['racion_id', 'inventario_insumo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('racion_insumo');
    }
};

