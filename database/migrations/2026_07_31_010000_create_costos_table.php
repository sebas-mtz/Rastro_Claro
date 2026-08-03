<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('costos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('concepto');
            $table->text('descripcion')->nullable();
            $table->string('categoria');
            $table->enum('tipo_costo', ['directo', 'indirecto'])->default('directo');
            $table->decimal('monto', 12, 2);
            $table->decimal('cantidad', 10, 2)->nullable();
            $table->string('unidad_medida')->nullable();
            $table->date('fecha');

            $table->foreignId('animal_id')->nullable()->constrained('animals')->nullOnDelete();
            $table->foreignId('lote_id')->nullable()->constrained('lotes')->nullOnDelete();
            $table->foreignId('faena_id')->nullable()->constrained('faenas')->nullOnDelete();
            $table->foreignId('sacrificio_id')->nullable()->constrained('sacrificios')->nullOnDelete();

            $table->string('proveedor')->nullable();
            $table->string('numero_comprobante')->nullable();
            $table->text('observaciones')->nullable();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->timestamps();

            $table->index('categoria');
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('costos');
    }
};
