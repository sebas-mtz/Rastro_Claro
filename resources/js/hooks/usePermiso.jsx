import { usePage } from '@inertiajs/react';

/**
 * Hook de permisos para el frontend.
 *
 * Uso:
 *   const { puede, user, isAdmin } = usePermiso();
 *
 *   puede('animales', 'crear')  → true | false
 *   isAdmin                     → true | false
 *   user.roleLabel              → 'Administrador' | 'Encargado' | ...
 */
export function usePermiso() {
    const { auth } = usePage().props;
    const user = auth?.user;
    const permisos = user?.permisos ?? [];

    /**
     * Verifica si el usuario tiene permiso para una acción en un módulo.
     * @param {string} modulo   - 'animales', 'salud', 'costos', etc.
     * @param {string} accion   - 'ver', 'crear', 'editar', 'eliminar'
     */
    function puede(modulo, accion) {
        if (!user || !user.activo) return false;
        return permisos.includes(`${modulo}.${accion}`);
    }

    return {
        user,
        puede,
        isAdmin:      user?.isAdmin     ?? false,
        isEncargado:  user?.isEncargado ?? false,
        estaActivo:   user?.activo      ?? false,
        roleLabel:    user?.roleLabel   ?? 'Sin rol',
    };
}

/**
 * Componente de guardia: solo renderiza children si el usuario tiene permiso.
 *
 * Uso:
 *   <SiPuede modulo="animales" accion="crear">
 *       <button>Crear borrega</button>
 *   </SiPuede>
 *
 *   <SiPuede modulo="costos" accion="ver" fallback={<p>Sin acceso</p>}>
 *       <CostosModulo />
 *   </SiPuede>
 */
export function SiPuede({ modulo, accion, children, fallback = null }) {
    const { puede } = usePermiso();
    return puede(modulo, accion) ? children : fallback;
}

/**
 * Componente que muestra un badge del rol del usuario.
 */
export function RolBadge({ role }) {
    const config = {
        administrador: { label: 'Administrador', cls: 'bg-red-100 text-red-800' },
        encargado:     { label: 'Encargado',     cls: 'bg-orange-100 text-orange-800' },
        trabajador:    { label: 'Trabajador',    cls: 'bg-blue-100 text-blue-800' },
        solo_lectura:  { label: 'Solo lectura',  cls: 'bg-gray-100 text-gray-600' },
    };
    const c = config[role] ?? { label: role ?? '—', cls: 'bg-gray-100 text-gray-500' };
    return (
        <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${c.cls}`}>
            {c.label}
        </span>
    );
}
