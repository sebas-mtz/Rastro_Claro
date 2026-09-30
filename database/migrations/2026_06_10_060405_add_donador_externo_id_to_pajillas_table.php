<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {Schema::table('pajillas', function (Blueprint $table) {
        $table->foreignId('donador_externo_id')
            ->nullable()
            ->after('animal_id')
            ->constrained('donadores_externos')
            ->nullOnDelete();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    Schema::table('pajillas', function (Blueprint $table) {
        $table->dropForeign(['donador_externo_id']);
        $table->dropColumn('donador_externo_id');
    });
}
};
