<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bitácora de las acciones críticas del sistema.
 *
 * Deliberadamente SIN owner_id: la auditoría no es de una cuenta, es del
 * sistema. Un superadministrador debe poder revisar quién cambió qué aunque
 * el registro afecte a otra cuenta.
 *
 * Tampoco lleva `updated_at` ni borrado suave: una bitácora que se puede
 * editar o borrar desde la aplicación no sirve como bitácora. No existe ruta
 * de modificación ni de eliminación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();

            // Quién hizo el cambio. nullOnDelete para no perder el registro
            // histórico si la cuenta desaparece.
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('usuario_nombre')->nullable();   // copia, sobrevive al borrado

            // A quién o a qué afectó.
            $table->foreignId('afectado_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('afectado_nombre')->nullable();

            $table->string('accion', 60);
            $table->string('descripcion', 500)->nullable();

            // Valores antes y después, en JSON para admitir cualquier forma.
            $table->json('valor_anterior')->nullable();
            $table->json('valor_nuevo')->nullable();

            // Registro relacionado cuando la acción no es sobre un usuario
            // (una configuración, una fórmula, un catálogo).
            $table->string('entidad_tipo')->nullable();
            $table->unsignedBigInteger('entidad_id')->nullable();

            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index(['accion', 'created_at']);
            $table->index('usuario_id');
            $table->index('afectado_id');
            $table->index(['entidad_tipo', 'entidad_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
