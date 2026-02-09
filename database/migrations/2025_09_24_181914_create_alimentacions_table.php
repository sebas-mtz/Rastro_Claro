// database/migrations/2025_01_01_000000_create_alimentaciones_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
   public function up(): void
{
    Schema::create('alimentacions', function (Blueprint $table) {
        $table->id();
        $table->date('fecha');
        $table->string('tipo');
        $table->decimal('cantidad', 10, 2);
        $table->string('unidad', 20);
        $table->foreignId('animal_id')->nullable()->constrained('animals')->nullOnDelete();
        $table->foreignId('lote_id')->nullable()->constrained('lotes')->nullOnDelete();
        $table->text('notas')->nullable();
        $table->timestamps();
    });
}

};
