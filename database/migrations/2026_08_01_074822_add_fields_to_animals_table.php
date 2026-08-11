<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {

            $table->string('siniiga_id')
                ->nullable()
                ->after('arete');

            $table->string('identificador')
                ->nullable()
                ->after('arete');

            $table->string('numero_registro')
                ->nullable()
                ->after('identificador');

            $table->string('grado_pureza')
                ->nullable()
                ->after('numero_registro');

            $table->string('lectura_microchip')
                ->nullable()
                ->after('grado_pureza');

            $table->string('color')
                ->nullable()
                ->after('lectura_microchip');
        
                $table->string('estado_reproductivo')
                  ->nullable()
                  ->after('color');
        });
    }


    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {

            $table->dropColumn([
                'siniiga_id',
                'identificador',
                'numero_registro',
                'grado_pureza',
                'lectura_microchip',
                'color',
                'estado_reproductivo'
            ]);

        });
    }
};