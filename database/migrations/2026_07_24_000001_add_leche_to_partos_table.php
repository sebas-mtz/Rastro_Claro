<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partos', function (Blueprint $table) {

            $table->boolean('salio_leche')
                ->default(false)
                ->after('numero_crias');

            $table->text('observaciones_leche')
                ->nullable()
                ->after('salio_leche');

            $table->boolean('facilidad_materna')
                ->default(false)
                ->after('observaciones_leche');

            $table->text('observaciones_maternas')
                ->nullable()
                ->after('facilidad_materna');

        });
    }

    public function down(): void
    {
        Schema::table('partos', function (Blueprint $table) {
            $table->dropColumn([
                'salio_leche',
                'observaciones_leche',
                'facilidad_materna',
                'observaciones_maternas',
            ]);
        });
    }
};