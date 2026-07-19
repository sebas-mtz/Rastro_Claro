<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (config('tenancy.tables') as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('owner_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        // Backfill only relationships that identify the creator unambiguously.
        // Other legacy rows stay private until tenancy:claim-legacy is run.
        $this->backfillOwner('vacunas', 'user_id');
        $this->backfillOwner('eventos_salud', 'user_id');
        $this->backfillOwner('tratamientos', 'user_id');
        $this->backfillOwner('evento_reproductivos', 'user_id');
        $this->backfillOwner('reportes', 'user_id');
        $this->backfillOwner('ventas', 'vendedor_id');
        $this->backfillOwner('tareas', 'creado_por');
    }

    public function down(): void
    {
        foreach (array_reverse(config('tenancy.tables')) as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('owner_id');
            });
        }
    }

    private function backfillOwner(string $table, string $sourceColumn): void
    {
        DB::table($table)
            ->whereNull('owner_id')
            ->whereNotNull($sourceColumn)
            ->update(['owner_id' => DB::raw($sourceColumn)]);
    }
};
