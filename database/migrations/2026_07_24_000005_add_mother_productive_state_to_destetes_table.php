<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('destetes', function (Blueprint $table) {
            $table->string('estado_productivo_madre', 100)
                ->nullable()
                ->after('estado_madre');
        });
    }

    public function down(): void
    {
        Schema::table('destetes', function (Blueprint $table) {
            $table->dropColumn('estado_productivo_madre');
        });
    }
};
