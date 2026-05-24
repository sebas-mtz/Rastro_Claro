<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            
            // Relaciones con las 4 tablas (poliórfica)
            $table->nullableMorphs('vendible');

            $table->foreignId('comprador_id')->nullable()->constrained('compradores')->onDelete('set null');
            
            // Información de la venta
            $table->date('fecha_venta');
            $table->enum('tipo_venta', ['animal', 'lote', 'produccion', 'subproducto_faena']);
            $table->enum('estado_venta', ['pendiente', 'completada', 'cancelada'])->default('pendiente');
            $table->decimal('costo_total', 15, 2)->default(0.00);
            
            // Detalles del producto vendido
            $table->string('producto'); // Ej: "Carne de res", "Leche", "Lote completo", "Cuero"
            $table->decimal('cantidad', 10, 2);
            $table->string('unidad'); // kg, litros, unidades, etc.
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('precio_total', 10, 2);
            
            // Información de pago y envío
            $table->enum('metodo_pago', ['efectivo', 'transferencia', 'tarjeta', 'cheque']);
            $table->enum('estado_pago', ['pendiente', 'parcial', 'completado'])->default('pendiente');
            $table->text('condiciones_entrega')->nullable();
            $table->date('fecha_entrega')->nullable();
            
            // Tracking
            $table->text('observaciones')->nullable();
            $table->string('numero_factura')->nullable()->unique();
            $table->foreignId('vendedor_id')->constrained('users')->onDelete('cascade');
            
            $table->timestamps();
            
            // Índices para mejor performance
            $table->index('fecha_venta');
            $table->index('tipo_venta');
            $table->index('estado_venta');
            $table->index('estado_pago');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};