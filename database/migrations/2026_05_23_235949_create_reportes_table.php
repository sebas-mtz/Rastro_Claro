<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes', function (Blueprint $table) {
            $table->id();

            // Quién generó el reporte
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Identificación
            $table->string('nombre')->nullable()->comment('Nombre amigable opcional que el usuario puede asignar');
            $table->string('modulo', 50)->comment('general|animales|salud|vacunacion|tratamientos|pesajes|alimentacion|inventario|reproduccion|produccion|ventas');

            // Parámetros usados para generarlo (para poder regenerarlo)
            $table->json('filtros')->comment('Snapshot de todos los filtros aplicados');

            // Resumen ejecutivo persistido (no guardamos todos los registros, solo el resumen)
            $table->json('resumen')->nullable()->comment('Snapshot del resumen ejecutivo al momento de generación');

            // Formato en que fue exportado (si aplica)
            $table->enum('formato', ['web', 'pdf', 'xml'])->default('web');

            // Para reportes programados / compartidos
            $table->boolean('publico')->default(false)->comment('Si true, accesible sin autenticación por UUID');
            $table->uuid('uuid')->unique()->comment('Slug para compartir por URL');

            $table->timestamps();

            // Índices de consulta frecuente
            $table->index('user_id');
            $table->index('modulo');
            $table->index('formato');
            $table->index('created_at');
            $table->index(['user_id', 'modulo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes');
    }
};