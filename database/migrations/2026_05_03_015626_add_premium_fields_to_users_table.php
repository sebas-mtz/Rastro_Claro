<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Agrega campos necesarios para el flujo Premium sin Cashier.
 * Compatible con MySQL (producción) y SQLite (testing).
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        // Renombrar 'rol' → 'role' — solo aplica en MySQL (producción).
        // En SQLite se omite porque la tabla se crea ya con 'role'.
        if ($driver === 'mysql') {
            if (Schema::hasColumn('users', 'rol') && !Schema::hasColumn('users', 'role')) {
                DB::statement("ALTER TABLE users CHANGE rol role VARCHAR(50) NOT NULL DEFAULT 'trabajador'");
            }

            if (Schema::hasColumn('users', 'role')) {
                DB::statement("ALTER TABLE users MODIFY role VARCHAR(50) NOT NULL DEFAULT 'trabajador'");
            }
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'stripe_checkout_session_id')) {
                $table->string('stripe_checkout_session_id')->nullable()->after('plan');
            }

            if (!Schema::hasColumn('users', 'premium_activated_at')) {
                $table->timestamp('premium_activated_at')->nullable()->after('stripe_checkout_session_id');
            }

            if (!Schema::hasColumn('users', 'premium_expires_at')) {
                $table->timestamp('premium_expires_at')->nullable()->after('premium_activated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'stripe_checkout_session_id')) {
                $table->dropColumn('stripe_checkout_session_id');
            }
            if (Schema::hasColumn('users', 'premium_activated_at')) {
                $table->dropColumn('premium_activated_at');
            }
            if (Schema::hasColumn('users', 'premium_expires_at')) {
                $table->dropColumn('premium_expires_at');
            }
        });
    }
};
