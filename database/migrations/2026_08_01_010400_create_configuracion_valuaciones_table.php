<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Valores del plus reproductivo por estado. No se dejan fijos en el código:
 * cada cuenta puede ajustarlos desde la interfaz.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_valuaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('clave');
            $table->decimal('valor', 12, 2)->default(0);
            $table->string('descripcion')->nullable();

            $table->timestamps();

            $table->unique(['owner_id', 'clave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_valuaciones');
    }
};
