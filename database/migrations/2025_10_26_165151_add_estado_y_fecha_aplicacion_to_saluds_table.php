<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('saluds', function (Blueprint $table) {
            if (!Schema::hasColumn('saluds', 'estado')) {
                $table->string('estado')->default('pendiente')->after('observaciones'); // pendiente|aplicada|vencida
            }
            if (!Schema::hasColumn('saluds', 'fecha_aplicacion')) {
                $table->date('fecha_aplicacion')->nullable()->after('fecha_programada');
            }
        });
    }
    public function down(): void {
        Schema::table('saluds', function (Blueprint $table) {
            if (Schema::hasColumn('saluds', 'fecha_aplicacion')) $table->dropColumn('fecha_aplicacion');
            if (Schema::hasColumn('saluds', 'estado')) $table->dropColumn('estado');
        });
    }
};
