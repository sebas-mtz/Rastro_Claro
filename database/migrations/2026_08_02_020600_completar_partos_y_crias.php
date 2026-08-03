<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Completa partos y crías con lo que exige el manejo ovino y con los datos
 * necesarios para calcular prolificidad, fertilidad y mortalidad.
 *
 * En los 42 partos existentes se rellenan crias_vivas/crias_muertas contando
 * las crías ya registradas, en lugar de dejarlos en cero: es información que
 * el sistema ya tiene, no un valor inventado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partos', function (Blueprint $table) {
            $table->time('hora')->nullable()->after('evento_id');
            $table->boolean('asistido')->default(false)->after('tipo_parto');
            $table->unsignedTinyInteger('crias_vivas')->nullable()->after('numero_crias');
            $table->unsignedTinyInteger('crias_muertas')->nullable()->after('crias_vivas');
            $table->unsignedTinyInteger('abortos')->default(0)->after('crias_muertas');
            $table->decimal('costo_atencion', 12, 2)->nullable()->after('abortos');
            $table->foreignId('veterinario_id')->nullable()->after('costo_atencion')->constrained('users')->nullOnDelete();
            $table->foreignId('responsable_id')->nullable()->after('veterinario_id')->constrained('users')->nullOnDelete();
            $table->text('observaciones')->nullable()->after('responsable_id');
        });

        Schema::table('crias', function (Blueprint $table) {
            $table->string('tipo_nacimiento')->nullable()->after('sexo');   // unico, doble, triple, cuadruple, otro
            $table->boolean('calostro_aplicado')->default(false)->after('peso_nacimiento');
            $table->date('fecha_calostro')->nullable()->after('calostro_aplicado');
            $table->foreignId('madre_nodriza_id')->nullable()->after('fecha_calostro')->constrained('animals')->nullOnDelete();
            $table->date('fecha_destete')->nullable()->after('madre_nodriza_id');
            $table->decimal('peso_destete', 6, 2)->nullable()->after('fecha_destete');
            $table->string('estado_actual')->nullable()->after('peso_destete');
            $table->string('causa_baja')->nullable()->after('estado_actual');
        });

        // Backfill honesto: se cuenta lo que ya está registrado en `crias`.
        $conteos = DB::table('crias')
            ->selectRaw('parto_id, SUM(condicion = "vivo") as vivas, SUM(condicion != "vivo") as muertas')
            ->groupBy('parto_id')
            ->get();

        foreach ($conteos as $conteo) {
            DB::table('partos')->where('id', $conteo->parto_id)->update([
                'crias_vivas' => (int) $conteo->vivas,
                'crias_muertas' => (int) $conteo->muertas,
            ]);
        }

        // Tipo de nacimiento derivado del número de crías del parto.
        foreach (DB::table('partos')->get(['id', 'numero_crias']) as $parto) {
            $tipo = match ((int) $parto->numero_crias) {
                1 => 'unico',
                2 => 'doble',
                3 => 'triple',
                4 => 'cuadruple',
                default => 'otro',
            };

            DB::table('crias')->where('parto_id', $parto->id)->update(['tipo_nacimiento' => $tipo]);
        }
    }

    public function down(): void
    {
        Schema::table('partos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('veterinario_id');
            $table->dropConstrainedForeignId('responsable_id');
            $table->dropColumn(['hora', 'asistido', 'crias_vivas', 'crias_muertas', 'abortos', 'costo_atencion', 'observaciones']);
        });

        Schema::table('crias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('madre_nodriza_id');
            $table->dropColumn([
                'tipo_nacimiento', 'calostro_aplicado', 'fecha_calostro',
                'fecha_destete', 'peso_destete', 'estado_actual', 'causa_baja',
            ]);
        });
    }
};
