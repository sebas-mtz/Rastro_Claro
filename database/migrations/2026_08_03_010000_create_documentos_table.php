<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentos y evidencias adjuntables a cualquier registro del sistema
 * (ejemplar, baja, evento de salud, parto, venta...).
 *
 * Los archivos se guardan en el disco privado: no quedan accesibles por URL
 * directa, solo a través del controlador, que verifica que el documento
 * pertenezca a la cuenta que lo solicita.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();

            // A qué registro pertenece el documento
            $table->string('documentable_type');
            $table->unsignedBigInteger('documentable_id');

            $table->string('tipo');            // certificado_pureza, registro_asociacion, etc.
            $table->string('nombre');          // nombre visible
            $table->string('ruta');            // ruta en el disco privado
            $table->string('nombre_original')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('tamano')->nullable();   // bytes
            $table->date('fecha_documento')->nullable();
            $table->text('observaciones')->nullable();

            $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['documentable_type', 'documentable_id']);
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
