<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->scopeUniqueCode('donadores_externos', 'codigo');
        $this->scopeUniqueCode('termos', 'codigo');
        $this->scopeUniqueCode('pajillas', 'codigo');
        $this->scopeUniqueCode('ventas', 'numero_factura');
    }

    public function down(): void
    {
        $this->restoreGlobalUniqueCode('donadores_externos', 'codigo');
        $this->restoreGlobalUniqueCode('termos', 'codigo');
        $this->restoreGlobalUniqueCode('pajillas', 'codigo');
        $this->restoreGlobalUniqueCode('ventas', 'numero_factura');
    }

    private function scopeUniqueCode(string $tableName, string $column): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($column) {
            $table->dropUnique([$column]);
            $table->unique(['owner_id', $column]);
        });
    }

    private function restoreGlobalUniqueCode(string $tableName, string $column): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($column) {
            $table->dropUnique(['owner_id', $column]);
            $table->unique($column);
        });
    }
};
