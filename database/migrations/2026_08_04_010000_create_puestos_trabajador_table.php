<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo configurable de puestos del rancho ovino.
 *
 * Sigue el mismo patrón que el catálogo de razas: los puestos base se siembran
 * pero cada cuenta puede agregar los suyos, en vez de quedar encerrados en una
 * constante de PHP.
 *
 * Nota: la constante User::PUESTOS sigue existiendo y describe la función de
 * las CUENTAS del sistema. Este catálogo describe el puesto de las PERSONAS
 * contratadas, que pueden no tener cuenta. El seeder usa las mismas claves
 * para que ambos hablen el mismo idioma.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('puestos_trabajador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('clave', 60);
            $table->string('nombre', 120);
            $table->string('area', 80)->nullable();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);

            $table->timestamps();

            // Un mismo puesto no se repite dentro de una cuenta.
            $table->unique(['owner_id', 'clave']);
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puestos_trabajador');
    }
};
