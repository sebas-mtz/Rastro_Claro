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
        Schema::create('inventario_insumos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('tipo');
            $table->decimal('existencias', 10, 2)->default(0);
            $table->string('unidad')->nullable();
            $table->decimal('MS', 5, 2)->nullable();
            $table->decimal('PB', 5, 2)->nullable();
            $table->decimal('EM', 5, 2)->nullable();
            $table->decimal('FDN', 5, 2)->nullable();
            $table->string('minerales')->nullable();
            $table->string('marca')->nullable();
            $table->decimal('costo_promedio', 8, 2)->nullable();
            $table->boolean('auto_rellenar')->default(false);
$table->integer('dias_rellenado')->nullable();
$table->decimal('cantidad_rellenado', 10, 2)->nullable();
$table->date('ultima_fecha_rellenado')->nullable();
$table->boolean('activo')->default(true);
$table->timestamp('desactivado_at')->nullable();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_insumos');
    }
};
