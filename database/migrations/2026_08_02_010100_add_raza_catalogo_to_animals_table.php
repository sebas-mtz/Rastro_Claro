<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sustituye la raza de texto libre por referencias al catálogo, permitiendo
 * además registrar cruzas (raza principal + segunda raza).
 *
 * La columna `raza` original NO se elimina: se conserva tal cual como respaldo
 * para no perder los textos que ya capturaste (incluidas cruzas triples).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->foreignId('raza_id')->nullable()->after('raza')->constrained('razas')->nullOnDelete();
            $table->foreignId('raza_secundaria_id')->nullable()->after('raza_id')->constrained('razas')->nullOnDelete();
            $table->boolean('es_cruza')->default(false)->after('raza_secundaria_id');
            $table->string('raza_original')->nullable()->after('es_cruza');
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('raza_id');
            $table->dropConstrainedForeignId('raza_secundaria_id');
            $table->dropColumn(['es_cruza', 'raza_original']);
        });
    }
};
