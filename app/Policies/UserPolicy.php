<?php

namespace App\Policies;

use App\Models\User;

/**
 * Quién puede administrar cuentas.
 *
 * Regla base: solo el superadministrador. Encima de eso van las protecciones
 * que evitan dejar el sistema sin quien lo administre o que alguien se
 * modifique a sí mismo el nivel de acceso.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageUsers();
    }

    public function view(User $user, User $model): bool
    {
        return $user->canManageUsers();
    }

    public function create(User $user): bool
    {
        return $user->canManageUsers();
    }

    /**
     * Editar los datos generales de una cuenta (nombre, correo, puesto, plan).
     * El rol y el estado tienen sus propias comprobaciones.
     */
    public function update(User $user, User $model): bool
    {
        return $user->canManageUsers();
    }

    /**
     * Cambiar el rol de una cuenta.
     *
     * Nadie cambia su propio rol, ni siquiera el superadministrador: es la
     * forma más común de perder el acceso por accidente, y de que alguien se
     * otorgue permisos a sí mismo.
     */
    public function cambiarRol(User $user, User $model): bool
    {
        return $user->canManageUsers() && $user->id !== $model->id;
    }

    /**
     * Activar o desactivar una cuenta.
     * Tampoco sobre uno mismo: dejaría la sesión en un estado sin salida.
     */
    public function cambiarEstado(User $user, User $model): bool
    {
        return $user->canManageUsers() && $user->id !== $model->id;
    }

    /**
     * Restablecer la contraseña de otra cuenta.
     *
     * La propia se cambia desde el perfil, donde se pide la contraseña actual;
     * este camino no la pide, así que no debe apuntar a uno mismo.
     */
    public function restablecerPassword(User $user, User $model): bool
    {
        return $user->canManageUsers() && $user->id !== $model->id;
    }

    /**
     * Eliminar físicamente una cuenta.
     *
     * Nunca la propia, nunca el último superadministrador activo y nunca una
     * cuenta con datos: en ese caso se desactiva. La comprobación de datos
     * relacionados la hace el controlador, que es quien puede contarlos.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->canManageUsers()
            && $user->id !== $model->id
            && ! $model->esUltimoSuperAdminActivo();
    }

    /** Consultar la bitácora del sistema. */
    public function verAuditoria(User $user): bool
    {
        return $user->canManageUsers();
    }
}
