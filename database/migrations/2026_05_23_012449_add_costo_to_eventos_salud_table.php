<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventos_salud', function (Blueprint $table) {
            if (!Schema::hasColumn('eventos_salud', 'estado')) {
                $table->string('estado')->default('pendiente');
            }

            if (!Schema::hasColumn('eventos_salud', 'costo')) {
                $table->decimal('costo', 10, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('eventos_salud', function (Blueprint $table) {
            if (Schema::hasColumn('eventos_salud', 'costo')) {
                $table->dropColumn('costo');
            }

            if (Schema::hasColumn('eventos_salud', 'estado')) {
                $table->dropColumn('estado');
            }
        });
    }
};
