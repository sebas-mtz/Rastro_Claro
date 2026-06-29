<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->foreignId('padre_externo_id')
                ->nullable()
                ->after('padre_id')
                ->constrained('donadores_externos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropForeign(['padre_externo_id']);
            $table->dropColumn('padre_externo_id');
        });
    }
};