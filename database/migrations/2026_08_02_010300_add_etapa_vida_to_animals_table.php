<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Etapa de vida del ejemplar ovino.
 *
 * No se calcula sola: el sistema puede sugerirla, pero solo se guarda cuando
 * el usuario la confirma. `etapa_vida_confirmada_at` deja constancia de cuándo
 * se aceptó, para distinguir lo confirmado de lo que sigue sin definir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->string('etapa_vida')->nullable()->after('estado_productivo');
            $table->timestamp('etapa_vida_confirmada_at')->nullable()->after('etapa_vida');
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropColumn(['etapa_vida', 'etapa_vida_confirmada_at']);
        });
    }
};
