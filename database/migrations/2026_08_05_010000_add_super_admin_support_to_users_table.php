<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Prepara la tabla users para la jerarquía de tres roles.
 *
 * La columna `role` ya existe y es un string sin restricción de valores, así
 * que admitir 'super_admin' no requiere alterarla: la validación vive en
 * User::ROLES y en las reglas de los formularios.
 *
 * Lo que sí se hace aquí:
 *
 * 1. `last_login_at`, para poder mostrar el último acceso de cada cuenta.
 * 2. Normalizar el valor 'user' a 'worker', que es el nombre del rol operativo
 *    en la jerarquía nueva. Es un cambio de nombre, no de nivel de acceso:
 *    ninguna cuenta gana ni pierde permisos. El modelo sigue reconociendo
 *    'user' como alias, por si quedara alguna fila sin normalizar.
 *
 * NO se toca la columna `rol` (el enum original admin/veterinario/cuidador),
 * que se conserva intacta desde la corrección anterior.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'last_login_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('last_login_at')->nullable()->after('activo');
            });
        }

        // El rol operativo pasa a llamarse 'worker'. Mismo nivel de acceso.
        DB::table('users')->where('role', 'user')->update(['role' => 'worker']);
        DB::table('users')->whereNull('role')->update(['role' => 'worker']);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'worker')->update(['role' => 'user']);

        if (Schema::hasColumn('users', 'last_login_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('last_login_at');
            });
        }
    }
};
