<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La tabla users se creó con la columna `rol` (enum admin/veterinario/cuidador)
 * pero todo el código PHP lee y escribe `role`: User::$fillable, User::isAdmin(),
 * el middleware CheckRole, HandleInertiaRequests y Admin\UserController.
 *
 * El efecto era que isAdmin() nunca devolvía true y el middleware `role:admin`
 * denegaba el acceso a /admin/usuarios a todas las cuentas.
 *
 * Se agrega la columna `role` y se rellena desde `rol`. La columna original se
 * conserva intacta para no romper nada que todavía la lea ni perder el detalle
 * de veterinario/cuidador.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'role')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->after('email');
        });

        if (! Schema::hasColumn('users', 'rol')) {
            return;
        }

        // Solo la distinción que el código realmente usa: admin vs. el resto.
        DB::table('users')->where('rol', 'admin')->update(['role' => 'admin']);
        DB::table('users')->where('rol', '!=', 'admin')->update(['role' => 'user']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
};
