<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compradores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('tipo')->default('particular'); // particular, empresa, distribuidor
            $table->string('rut_ci')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->text('direccion')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            
            $table->index('nombre');
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compradores');
    }
};