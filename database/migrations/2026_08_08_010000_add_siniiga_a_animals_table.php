<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Identificación oficial SINIIGA y tecnología del arete electrónico.
 *
 * Un ejemplar puede llevar hasta tres identificadores distintos y conviene no
 * confundirlos:
 *
 *   · `arete`            numeración interna del rancho (la que ya se usaba)
 *   · `siniiga_numero`   número impreso en el arete visual oficial
 *   · `microchip_codigo` código ISO 11784 que devuelve el lector electrónico
 *
 * `tecnologia_rfid` guarda si el arete es HDX o FDX-B. El sistema no necesita
 * ese dato para leerlo —de eso se encarga el lector— pero sí para saber qué
 * equipo hace falta en campo cuando el rebaño mezcla aretes de las dos.
 *
 * `pais_codigo` es el prefijo del código ISO, ya separado, para poder filtrar
 * y contar sin partir la cadena en cada consulta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->string('siniiga_numero', 20)->nullable()->unique()->after('arete');
            $table->string('tecnologia_rfid', 10)->nullable()->after('tipo_identificador');
            $table->string('pais_codigo', 3)->nullable()->after('tecnologia_rfid');

            $table->index('pais_codigo');
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropIndex(['pais_codigo']);
            $table->dropColumn(['siniiga_numero', 'tecnologia_rfid', 'pais_codigo']);
        });
    }
};
