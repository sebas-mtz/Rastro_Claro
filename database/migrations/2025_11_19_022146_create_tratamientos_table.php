<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tratamientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->foreignId('lote_id')
            ->nullable()
            ->constrained('lotes')
            ->nullOnDelete();
            $table->string('nombre');                 // "Antibiótico mastitis"
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();    // fecha prevista de fin
            $table->string('estado')->default('activo'); // activo | completado
// En tratamientos: vincular a la consulta que lo originó
$table->foreignId('salud_id')->nullable()->constrained('eventos_salud')->nullOnDelete();
$table->string('responsable')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tratamientos');
    }
};
