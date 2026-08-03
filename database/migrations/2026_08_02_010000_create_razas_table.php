<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo configurable de razas ovinas.
 *
 * Antes las razas vivían escritas duro dentro de AnimalController; ahora se
 * administran desde base de datos y cada cuenta puede agregar las suyas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('razas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('nombre');
            $table->string('origen')->nullable();
            $table->string('aptitud')->nullable();   // carne, lana, leche, doble propósito, pelo
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->unique(['owner_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('razas');
    }
};
