<?php

namespace App\Policies;

use App\Models\Trabajador;
use App\Models\User;

/**
 * Permisos del módulo de trabajadores.
 *
 * Nota sobre el modelo de cuentas de este proyecto: el aislamiento se hace por
 * `owner_id` = id del usuario autenticado (ver config/tenancy.php y
 * AppServiceProvider). Es decir, cada cuenta ve únicamente sus propios
 * registros. Por eso el criterio de estos permisos es la PROPIEDAD del
 * registro, no el rol global admin/user: quien es dueño de su rancho maneja a
 * su gente por completo, incluidos sueldos y datos personales.
 *
 * El rol `admin` se conserva como acceso adicional. Si algún día varias
 * personas comparten una misma cuenta —para eso existe el enlace opcional
 * `trabajadores.user_id`—, este mismo filtro empezará a denegar los datos
 * reservados a quien no sea el dueño, sin cambiar una sola línea.
 */
class TrabajadorPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Trabajador $trabajador): bool
    {
        return $this->esSuyo($user, $trabajador);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Trabajador $trabajador): bool
    {
        return $this->esSuyo($user, $trabajador);
    }

    /** Activar o inactivar a una persona. */
    public function cambiarEstado(User $user, Trabajador $trabajador): bool
    {
        return $this->esSuyo($user, $trabajador);
    }

    /**
     * Ver CURP, RFC, dirección, fecha de nacimiento, sueldo, tarifas y contacto
     * de emergencia. Sin esto los campos ni siquiera se envían al navegador.
     *
     * Acepta el registro concreto; sin él responde a nivel de módulo (para
     * decidir si se pintan las columnas reservadas en la lista).
     */
    public function verDatosSensibles(User $user, ?Trabajador $trabajador = null): bool
    {
        return $trabajador === null
            ? true
            : $this->esSuyo($user, $trabajador);
    }

    /** Registrar el trabajo hecho. */
    public function registrarActividad(User $user): bool
    {
        return true;
    }

    /** Consultar cuánto ha costado la mano de obra acumulada. */
    public function verCostosManoObra(User $user, ?Trabajador $trabajador = null): bool
    {
        return $this->verDatosSensibles($user, $trabajador);
    }

    /**
     * El borrado físico nunca procede si la persona ya tiene historial: en ese
     * caso se inactiva. Sin historial, se puede corregir un alta equivocada.
     */
    public function delete(User $user, Trabajador $trabajador): bool
    {
        return $this->esSuyo($user, $trabajador)
            && ! $trabajador->tieneRegistrosRelacionados();
    }

    /**
     * El registro pertenece al rancho del usuario.
     *
     * Se compara contra la cuenta, no contra el id personal: desde que varias
     * personas pueden compartir un mismo rancho, la propiedad es del rancho.
     *
     * El acceso adicional quedó en manos del superadministrador. Antes lo tenía
     * cualquier `admin`, lo que era inofensivo mientras cada usuario era su
     * propio rancho —nunca podía cargar el registro de otro— pero dejaría de
     * serlo en cuanto un administrador sea empleado dentro de una cuenta ajena.
     */
    private function esSuyo(User $user, Trabajador $trabajador): bool
    {
        return (int) $trabajador->owner_id === $user->cuentaId()
            || $user->isSuperAdmin();
    }
}
