<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cómo se comporta el lector de aretes de cada rancho.
 *
 * El sistema se instala en explotaciones con equipos que nadie del lado del
 * desarrollo va a ver. La mayoría funciona sin tocar nada —el código se
 * limpia solo—, pero algunos lectores anteponen caracteres al número o usan
 * una velocidad de puerto distinta. Antes eso obligaba a modificar el código;
 * ahora se resuelve desde la interfaz, rancho por rancho.
 *
 * Una fila por cuenta. Un rancho con varios lectores los configura igual,
 * porque después de aplicar las reglas todos entregan lo mismo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_lectores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->unique()->constrained('users')->cascadeOnDelete();

            // ── Limpieza de la lectura ────────────────────────────────────
            // Texto fijo que el lector añade antes o después del código.
            $table->string('prefijo_descartar', 20)->nullable();
            $table->string('sufijo_descartar', 20)->nullable();

            // Descarta todo lo que no sea dígito. Apagado por omisión: hay
            // identificadores legítimos con letras (aretes internos, alias,
            // microchips anteriores a la norma) que se perderían.
            $table->boolean('solo_digitos')->default(false);

            // Longitud que debe tener el código ya limpio. Vacío = la de la
            // norma ISO 11784, que son 15 dígitos.
            $table->unsignedTinyInteger('longitud_esperada')->nullable();

            // ── Conexión ──────────────────────────────────────────────────
            $table->string('tipo_conexion', 20)->default('teclado');
            $table->unsignedInteger('baud_rate')->default(9600);

            // Marca y modelo del equipo. Es un dato de soporte: cuando algo
            // falla, saber qué lector es ahorra media conversación.
            $table->string('modelo_lector', 120)->nullable();
            $table->text('notas')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_lectores');
    }
};
