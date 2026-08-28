<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Personas que trabajan en el rancho.
 *
 * Es deliberadamente distinta de `users`: un trabajador es alguien cuya mano de
 * obra se registra y se cuesta, tenga o no acceso al sistema. La cuenta, cuando
 * existe, se enlaza por `user_id` y es opcional y única (una cuenta no puede
 * corresponder a dos trabajadores).
 *
 * Los datos personales sensibles (CURP, RFC, dirección, sueldo, contacto de
 * emergencia) viven aquí pero el controlador solo los envía al navegador cuando
 * el usuario tiene permiso; nunca viajan "por si acaso".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trabajadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();

            // ── Identidad ──────────────────────────────────────────────────
            $table->string('nombre', 120);
            $table->string('apellido_paterno', 120)->nullable();
            $table->string('apellido_materno', 120)->nullable();

            // Datos fiscales: opcionales y sensibles.
            $table->string('curp', 18)->nullable();
            $table->string('rfc', 13)->nullable();

            // ── Contacto ───────────────────────────────────────────────────
            $table->string('telefono', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('direccion', 255)->nullable();
            $table->date('fecha_nacimiento')->nullable();

            // ── Relación laboral ───────────────────────────────────────────
            $table->date('fecha_contratacion')->nullable();
            $table->foreignId('puesto_id')->nullable()->constrained('puestos_trabajador')->nullOnDelete();
            $table->string('area', 80)->nullable();
            $table->string('tipo_contratacion', 40)->nullable();

            // Dinero en decimal, nunca en punto flotante.
            $table->decimal('sueldo', 10, 2)->nullable();
            $table->decimal('costo_jornada', 10, 2)->nullable();
            $table->decimal('costo_hora', 10, 2)->nullable();

            $table->string('horario', 120)->nullable();
            $table->boolean('activo')->default(true);
            $table->date('fecha_baja')->nullable();
            $table->string('motivo_baja', 255)->nullable();

            // ── Emergencia ─────────────────────────────────────────────────
            $table->string('contacto_emergencia', 150)->nullable();
            $table->string('telefono_emergencia', 30)->nullable();

            $table->text('observaciones')->nullable();

            // ── Cuenta del sistema (opcional) ──────────────────────────────
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();

            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['owner_id', 'activo']);
            $table->index('puesto_id');
            $table->index('area');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trabajadores');
    }
};
