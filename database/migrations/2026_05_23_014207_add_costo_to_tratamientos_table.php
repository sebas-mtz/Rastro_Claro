<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tratamientos', function (Blueprint $table) {
            // Costo del tratamiento — necesario para importar al módulo de Costos
            if (!Schema::hasColumn('tratamientos', 'costo')) {
                $table->decimal('costo', 10, 2)->default(0)->after('notas');
            }
            // Etapa productiva del animal al momento del tratamiento
            if (!Schema::hasColumn('tratamientos', 'etapa_animal')) {
                $table->string('etapa_animal')->nullable()->after('costo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tratamientos', function (Blueprint $table) {
            if (Schema::hasColumn('tratamientos', 'costo')) {
                $table->dropColumn('costo');
            }
            if (Schema::hasColumn('tratamientos', 'etapa_animal')) {
                $table->dropColumn('etapa_animal');
            }
        });
    }
};
