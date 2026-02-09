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
        Schema::create('racions', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->decimal('MS', 5, 2)->nullable();
            $table->decimal('PB', 5, 2)->nullable();
            $table->decimal('EM', 5, 2)->nullable();
            $table->decimal('FDN', 5, 2)->nullable();
            $table->string('minerales')->nullable();
            $table->decimal('precio_kg', 8, 2)->nullable();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('racions');
    }
};
