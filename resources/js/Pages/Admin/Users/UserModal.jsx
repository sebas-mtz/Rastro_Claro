import React, { useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { X, ShieldAlert } from 'lucide-react';

const VACIO = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'worker',
    cuenta_id: '',
    puesto_id: '',
    activo: true,
    trabajador_id: '',
};

function desdeUsuario(u) {
    if (!u) return { ...VACIO };

    return {
        ...VACIO,
        name: u.name ?? '',
        email: u.email ?? '',
        role: u.role ?? 'worker',
        // Un dueño se representa con el campo vacío: "es su propio rancho".
        cuenta_id: u.es_dueno ? '' : (u.cuenta_id ?? ''),
        puesto_id: u.puesto_id ?? '',
        activo: !!u.activo,
        trabajador_id: u.trabajador?.id ?? '',
    };
}

function nombreDe(t) {
    return [t.nombre, t.apellido_paterno, t.apellido_materno].filter(Boolean).join(' ');
}

export default function UserModal({
    show,
    usuario = null,
    roles = {},
    ranchos = [],
    puestosPorRancho = {},
    trabajadoresSinCuenta = [],
    onClose,
}) {
    const editando = Boolean(usuario);

    const { data, setData, post, put, processing, errors, reset, clearErrors } =
        useForm(desdeUsuario(usuario));

    useEffect(() => {
        if (show) {
            setData(desdeUsuario(usuario));
            clearErrors();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [show, usuario?.id]);

    const cerrar = () => {
        reset();
        clearErrors();
        onClose();
    };

    const enviar = (e) => {
        e.preventDefault();

        const opciones = { preserveScroll: true, onSuccess: () => cerrar() };

        if (editando) {
            put(route('admin.usuarios.update', usuario.id), opciones);
        } else {
            post(route('admin.usuarios.store'), opciones);
        }
    };

    // El rol propio no se puede cambiar: el backend lo rechaza y aquí se
    // desactiva el control para que no parezca posible.
    const rolBloqueado = editando && !usuario?.permisos?.cambiarRol;

    // Solo quien trabaja en el rancho de otro tiene puesto: el dueño no lo
    // necesita porque su acceso no depende de un catálogo.
    const esEmpleado = Boolean(data.cuenta_id);
    const puestosDelRancho = esEmpleado ? (puestosPorRancho[data.cuenta_id] ?? []) : [];

    if (!show) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-start justify-center bg-black/50 overflow-y-auto p-4">
            <div className="bg-white rounded-2xl shadow-xl w-full max-w-2xl my-8">
                <div className="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                    <h3 className="text-lg font-semibold text-slate-800">
                        {editando ? 'Editar usuario' : 'Nuevo usuario'}
                    </h3>
                    <button onClick={cerrar} className="text-slate-400 hover:text-slate-700" aria-label="Cerrar">
                        <X className="w-5 h-5" />
                    </button>
                </div>

                <form onSubmit={enviar} className="px-6 py-5 space-y-4">
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Nombre *</label>
                            <input
                                type="text"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                                required
                                maxLength={255}
                            />
                            {errors.name && <p className="text-xs text-red-600 mt-1">{errors.name}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Correo *</label>
                            <input
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                                required
                            />
                            {errors.email && <p className="text-xs text-red-600 mt-1">{errors.email}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Rol *</label>
                            <select
                                value={data.role}
                                onChange={(e) => setData('role', e.target.value)}
                                disabled={rolBloqueado}
                                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm disabled:bg-slate-100 disabled:text-slate-500"
                            >
                                {Object.entries(roles).map(([valor, etiqueta]) => (
                                    <option key={valor} value={valor}>{etiqueta}</option>
                                ))}
                            </select>
                            {rolBloqueado && (
                                <p className="text-xs text-slate-500 mt-1">
                                    No puedes cambiar tu propio rol.
                                </p>
                            )}
                            {errors.role && <p className="text-xs text-red-600 mt-1">{errors.role}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">
                                Trabaja en el rancho de
                            </label>
                            <select
                                value={data.cuenta_id}
                                onChange={(e) => {
                                    // El puesto sale del catálogo del rancho:
                                    // al cambiar de rancho deja de ser válido.
                                    setData((previo) => ({
                                        ...previo,
                                        cuenta_id: e.target.value,
                                        puesto_id: '',
                                    }));
                                }}
                                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                            >
                                <option value="">Es dueño de su propio rancho</option>
                                {ranchos
                                    .filter((r) => !editando || r.id !== usuario.id)
                                    .map((r) => (
                                        <option key={r.id} value={r.id}>
                                            {r.name}{r.personas > 0 ? ` (${r.personas})` : ''}
                                        </option>
                                    ))}
                            </select>
                            <p className="text-xs text-slate-400 mt-1">
                                {esEmpleado
                                    ? 'Verá el rebaño de ese rancho, según los permisos de su puesto.'
                                    : 'Tendrá su propio rebaño, separado del resto.'}
                            </p>
                            {errors.cuenta_id && <p className="text-xs text-red-600 mt-1">{errors.cuenta_id}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">
                                Puesto
                            </label>
                            <select
                                value={data.puesto_id}
                                onChange={(e) => setData('puesto_id', e.target.value)}
                                disabled={!esEmpleado}
                                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm disabled:bg-slate-100 disabled:text-slate-500"
                            >
                                <option value="">Sin puesto</option>
                                {puestosDelRancho.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.nombre}{p.area ? ` — ${p.area}` : ''}
                                    </option>
                                ))}
                            </select>
                            <p className="text-xs text-slate-400 mt-1">
                                {!esEmpleado
                                    ? 'El dueño de un rancho no necesita puesto: tiene acceso completo.'
                                    : 'De aquí salen los módulos a los que entra.'}
                            </p>
                            {errors.puesto_id && <p className="text-xs text-red-600 mt-1">{errors.puesto_id}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">
                                Trabajador relacionado
                            </label>
                            <select
                                value={data.trabajador_id}
                                onChange={(e) => setData('trabajador_id', e.target.value)}
                                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                            >
                                <option value="">Ninguno</option>
                                {editando && usuario?.trabajador && (
                                    <option value={usuario.trabajador.id}>
                                        {usuario.trabajador.nombre_completo}
                                    </option>
                                )}
                                {trabajadoresSinCuenta.map((t) => (
                                    <option key={t.id} value={t.id}>{nombreDe(t)}</option>
                                ))}
                            </select>
                            <p className="text-xs text-slate-400 mt-1">
                                Enlace opcional con una persona ya registrada en Trabajadores.
                            </p>
                            {errors.trabajador_id && <p className="text-xs text-red-600 mt-1">{errors.trabajador_id}</p>}
                        </div>
                    </div>

                    {/* La contraseña solo se captura al crear. Para cambiarla
                        después existe la acción de restablecer, que tampoco
                        muestra la anterior porque no puede leerse. */}
                    {!editando && (
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Contraseña *</label>
                                <input
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                                    required
                                    autoComplete="new-password"
                                />
                                {errors.password && <p className="text-xs text-red-600 mt-1">{errors.password}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Confirmar *</label>
                                <input
                                    type="password"
                                    value={data.password_confirmation}
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                                    required
                                    autoComplete="new-password"
                                />
                            </div>
                            <label className="sm:col-span-2 flex items-center gap-2 text-sm text-slate-700">
                                <input
                                    type="checkbox"
                                    checked={data.activo}
                                    onChange={(e) => setData('activo', e.target.checked)}
                                />
                                Cuenta activa desde el inicio
                            </label>
                        </div>
                    )}

                    {data.role === 'super_admin' && (
                        <p className="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-start gap-2">
                            <ShieldAlert className="w-4 h-4 mt-0.5 shrink-0" />
                            <span>
                                El superadministrador puede administrar cuentas, roles, configuración
                                del sistema y fórmulas económicas. Otórgalo solo a quien deba tener
                                control total.
                            </span>
                        </p>
                    )}

                    <div className="flex justify-end gap-3 pt-2 border-t border-slate-200">
                        <button
                            type="button"
                            onClick={cerrar}
                            className="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium disabled:opacity-60"
                        >
                            {processing ? 'Guardando…' : editando ? 'Guardar cambios' : 'Crear usuario'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
