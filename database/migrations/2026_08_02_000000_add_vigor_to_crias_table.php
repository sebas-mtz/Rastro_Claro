<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crias', function (Blueprint $table) {
            $table->string('vigor', 1)->nullable()->after('condicion');
        });
    }

    public function down(): void
    {
        Schema::table('crias', function (Blueprint $table) {
            $table->dropColumn('vigor');
        });
    }
};
