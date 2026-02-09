<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reproduccions', function (Blueprint $table) {
            $table->id();

            // Animal hembra (madre) - obligatorio
            $table->foreignId('hembra_id')
                ->constrained('animals')
                ->cascadeOnDelete();

            // Animal macho (padre) - opcional
            $table->foreignId('macho_id')
                ->nullable()
                ->constrained('animals')
                ->nullOnDelete();

            // Lote opcional
            $table->foreignId('lote_id')
                ->nullable()
                ->constrained('lotes')
                ->nullOnDelete();

            // Usuario que registró el evento
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Tipo de evento reproductivo (MVP)
            $table->enum('tipo_evento', [
                'celo',
                'monta',
                'inseminacion',
                'diagnostico_gestacion',
                'aborto',
                'parto',
                'destete',
                'otro',
            ])->index();

            // Fecha del evento
            $table->date('fecha')->index();

            // Estado/resultado del evento
            $table->enum('estado', ['pendiente', 'confirmado', 'fallido', 'cancelado'])
                ->default('pendiente')
                ->index();

            // Método / detalles
            $table->string('metodo', 50)->nullable();
            $table->string('semen_codigo', 100)->nullable();
            $table->string('diagnostico', 150)->nullable();
            $table->decimal('costo', 10, 2)->default(0);

            // Datos específicos de parto
            $table->unsignedInteger('numero_crias')->nullable();
            $table->decimal('peso_total_crias', 10, 2)->nullable();
            $table->boolean('complicaciones')->default(false);
            $table->text('detalle_complicaciones')->nullable();

            // Futuro: IDs de crías creadas
            $table->json('crias_ids')->nullable();

            $table->text('observaciones')->nullable();

            $table->timestamps();

            // Índices para reportes
            $table->index(['hembra_id', 'fecha']);
            $table->index(['macho_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reproduccions');
    }
};
