import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import {
    Users, PlusCircle, Search, Pencil, KeyRound, UserCheck, UserX, ShieldCheck,
    ShieldAlert, History, Trash2,
} from 'lucide-react';
import UserModal from './UserModal';
import PasswordModal from './PasswordModal';

function fmtFecha(f) {
    return f ? new Date(f).toLocaleDateString('es-MX') : '—';
}

function fmtFechaHora(f) {
    return f ? new Date(f).toLocaleString('es-MX', { dateStyle: 'short', timeStyle: 'short' }) : 'Nunca';
}

const ESTILO_ROL = {
    super_admin: 'bg-amber-100 text-amber-800',
    admin: 'bg-blue-100 text-blue-800',
    worker: 'bg-slate-100 text-slate-700',
};

export default function UsuariosIndex({
    auth,
    usuarios,
    roles = {},
    ranchos = [],
    puestosPorRancho = {},
    filtros = {},
    resumen = {},
    trabajadoresSinCuenta = [],
    auditoriaReciente = [],
}) {
    const [modal, setModal] = useState({ show: false, usuario: null });
    const [modalPassword, setModalPassword] = useState({ show: false, usuario: null });
    const [locales, setLocales] = useState({
        buscar: filtros.buscar || '',
        role: filtros.role || '',
        estado: filtros.estado || '',
    });

    const filas = usuarios?.data || [];

    const aplicar = (cambios) => {
        const nuevos = { ...locales, ...cambios };
        setLocales(nuevos);
        router.get(route('admin.usuarios.index'), nuevos, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const cambiarEstado = (usuario) => {
        const desactivando = usuario.activo;

        // Confirmación explícita antes de quitarle el acceso a alguien.
        const mensaje = desactivando
            ? `¿Desactivar la cuenta de ${usuario.name}?\n\nNo podrá iniciar sesión. Sus registros y su historial se conservan.`
            : `¿Reactivar la cuenta de ${usuario.name}?`;

        if (!confirm(mensaje)) return;

        router.patch(
            route('admin.usuarios.estado', usuario.id),
            { activo: !usuario.activo },
            { preserveScroll: true },
        );
    };

    const eliminar = (usuario) => {
        if (!confirm(`¿Eliminar la cuenta de ${usuario.name}? Solo procede si no tiene registros.`)) return;

        router.delete(route('admin.usuarios.destroy', usuario.id), { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800">Usuarios del sistema</h2>}
        >
            <Head title="Usuarios" />

            <div className="py-8 px-4 sm:px-6 max-w-7xl mx-auto space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-800">Usuarios del sistema</h1>
                        <p className="text-gray-600">
                            Cuentas que pueden iniciar sesión, su nivel de acceso y su estado.
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Link
                            href={route('admin.permisos.index')}
                            className="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
                        >
                            <ShieldCheck className="w-5 h-5" /> Permisos
                        </Link>
                        <Link
                            href={route('admin.auditoria.index')}
                            className="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
                        >
                            <History className="w-5 h-5" /> Bitácora
                        </Link>
                        <button
                            onClick={() => setModal({ show: true, usuario: null })}
                            className="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
                        >
                            <PlusCircle className="w-5 h-5" /> Nuevo usuario
                        </button>
                    </div>
                </div>

                <p className="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-start gap-2">
                    <ShieldAlert className="w-4 h-4 mt-0.5 shrink-0" />
                    <span>
                        Esta sección está reservada al superadministrador. Los administradores y
                        trabajadores reciben un error 403 aunque escriban la dirección a mano.
                    </span>
                </p>

                {/* Resumen */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <p className="text-xs font-medium text-slate-500">Cuentas</p>
                        <p className="mt-2 text-2xl font-semibold text-slate-900">{resumen.total ?? 0}</p>
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <p className="text-xs font-medium text-slate-500 flex items-center gap-1">
                            <ShieldCheck className="w-3.5 h-3.5" /> Superadministradores
                        </p>
                        <p className="mt-2 text-2xl font-semibold text-amber-600">{resumen.super_admins ?? 0}</p>
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <p className="text-xs font-medium text-slate-500">Administradores</p>
                        <p className="mt-2 text-2xl font-semibold text-blue-600">{resumen.admins ?? 0}</p>
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <p className="text-xs font-medium text-slate-500">Inactivos</p>
                        <p className="mt-2 text-2xl font-semibold text-slate-500">{resumen.inactivos ?? 0}</p>
                    </div>
                </div>

                {/* Filtros */}
                <div className="bg-white rounded-2xl border border-slate-200 p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div className="relative lg:col-span-2">
                        <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input
                            type="search"
                            value={locales.buscar}
                            onChange={(e) => setLocales({ ...locales, buscar: e.target.value })}
                            onKeyDown={(e) => e.key === 'Enter' && aplicar({ buscar: locales.buscar })}
                            onBlur={() => aplicar({ buscar: locales.buscar })}
                            placeholder="Buscar por nombre o correo…"
                            className="w-full border border-slate-300 rounded-lg pl-9 pr-3 py-2 text-sm text-slate-700"
                            aria-label="Buscar usuario"
                        />
                    </div>

                    <select
                        value={locales.role}
                        onChange={(e) => aplicar({ role: e.target.value })}
                        className="border border-slate-300 rounded-lg px-2 py-2 text-sm text-slate-700"
                        aria-label="Rol"
                    >
                        <option value="">Todos los roles</option>
                        {Object.entries(roles).map(([valor, etiqueta]) => (
                            <option key={valor} value={valor}>{etiqueta}</option>
                        ))}
                    </select>

                    <select
                        value={locales.estado}
                        onChange={(e) => aplicar({ estado: e.target.value })}
                        className="border border-slate-300 rounded-lg px-2 py-2 text-sm text-slate-700"
                        aria-label="Estado"
                    >
                        <option value="">Activos e inactivos</option>
                        <option value="activo">Solo activos</option>
                        <option value="inactivo">Solo inactivos</option>
                    </select>
                </div>

                {/* Tabla */}
                <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    {filas.length === 0 ? (
                        <div className="text-center py-12">
                            <Users className="w-14 h-14 text-slate-200 mx-auto mb-3" />
                            <p className="text-slate-500">No hay usuarios con estos filtros.</p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-slate-200">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Nombre</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Correo</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Rol</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Rancho</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Trabajador</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Estado</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Creado</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Último acceso</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-slate-100">
                                    {filas.map((u) => (
                                        <tr key={u.id} className={'hover:bg-slate-50 ' + (u.activo ? '' : 'opacity-60')}>
                                            <td className="px-5 py-3 text-sm font-medium text-slate-800">
                                                {u.name}
                                                {u.es_uno_mismo && (
                                                    <span className="ml-2 text-xs font-normal text-slate-400">(tú)</span>
                                                )}
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-600">{u.email}</td>
                                            <td className="px-5 py-3 whitespace-nowrap">
                                                <span className={
                                                    'inline-flex px-2 py-1 text-xs font-semibold rounded-full ' +
                                                    (ESTILO_ROL[u.role] || ESTILO_ROL.worker)
                                                }>
                                                    {u.rol_legible}
                                                </span>
                                                {u.es_ultimo_super_admin && (
                                                    <span className="block text-xs text-amber-600 mt-0.5">
                                                        Único activo
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-600">
                                                {u.es_dueno ? (
                                                    <span className="text-xs text-slate-500">El suyo</span>
                                                ) : (
                                                    <span className="text-xs">{u.rancho ?? '—'}</span>
                                                )}
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-600">
                                                {u.trabajador ? (
                                                    <Link
                                                        href={route('trabajadores.show', u.trabajador.id)}
                                                        className="text-emerald-700 hover:underline"
                                                    >
                                                        {u.trabajador.nombre_completo}
                                                    </Link>
                                                ) : (
                                                    <span className="text-slate-400">—</span>
                                                )}
                                            </td>
                                            <td className="px-5 py-3 whitespace-nowrap">
                                                <span className={
                                                    'inline-flex px-2 py-1 text-xs font-semibold rounded-full ' +
                                                    (u.activo ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600')
                                                }>
                                                    {u.activo ? 'Activo' : 'Inactivo'}
                                                </span>
                                            </td>
                                            <td className="px-5 py-3 whitespace-nowrap text-sm text-slate-600">
                                                {fmtFecha(u.created_at)}
                                            </td>
                                            <td className="px-5 py-3 whitespace-nowrap text-sm text-slate-600">
                                                {fmtFechaHora(u.last_login_at)}
                                            </td>
                                            <td className="px-5 py-3 whitespace-nowrap text-sm">
                                                <div className="flex items-center gap-3">
                                                    {u.permisos?.editar && (
                                                        <button
                                                            onClick={() => setModal({ show: true, usuario: u })}
                                                            title="Editar"
                                                            className="text-blue-600 hover:text-blue-800"
                                                        >
                                                            <Pencil className="w-4 h-4" />
                                                        </button>
                                                    )}

                                                    {u.permisos?.restablecerPassword && (
                                                        <button
                                                            onClick={() => setModalPassword({ show: true, usuario: u })}
                                                            title="Restablecer contraseña"
                                                            className="text-amber-600 hover:text-amber-800"
                                                        >
                                                            <KeyRound className="w-4 h-4" />
                                                        </button>
                                                    )}

                                                    {u.permisos?.cambiarEstado && !u.es_ultimo_super_admin && (
                                                        <button
                                                            onClick={() => cambiarEstado(u)}
                                                            title={u.activo ? 'Desactivar' : 'Reactivar'}
                                                            className={u.activo ? 'text-slate-500 hover:text-slate-800' : 'text-emerald-600 hover:text-emerald-800'}
                                                        >
                                                            {u.activo ? <UserX className="w-4 h-4" /> : <UserCheck className="w-4 h-4" />}
                                                        </button>
                                                    )}

                                                    {u.permisos?.eliminar && (
                                                        <button
                                                            onClick={() => eliminar(u)}
                                                            title="Eliminar cuenta"
                                                            className="text-red-500 hover:text-red-700"
                                                        >
                                                            <Trash2 className="w-4 h-4" />
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {usuarios?.links && usuarios.links.length > 3 && (
                    <div className="flex justify-center">
                        <nav className="flex flex-wrap items-center gap-2">
                            {usuarios.links.map((link, i) => (
                                <Link
                                    key={i}
                                    href={link.url || '#'}
                                    className={`px-3 py-1 rounded-lg text-sm font-medium ${
                                        link.active
                                            ? 'bg-emerald-600 text-white'
                                            : 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50'
                                    } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </nav>
                    </div>
                )}

                {/* Últimos movimientos */}
                {auditoriaReciente.length > 0 && (
                    <div className="bg-white rounded-2xl border border-slate-200 p-5">
                        <div className="flex items-center justify-between mb-4">
                            <h2 className="font-semibold text-slate-800 flex items-center gap-2">
                                <History className="w-4 h-4 text-slate-400" /> Últimos movimientos
                            </h2>
                            <Link
                                href={route('admin.auditoria.index')}
                                className="text-sm text-emerald-700 hover:underline"
                            >
                                Ver bitácora completa
                            </Link>
                        </div>
                        <ol className="space-y-2">
                            {auditoriaReciente.map((m) => (
                                <li key={m.id} className="flex flex-wrap gap-2 text-sm border-b border-slate-100 pb-2 last:border-0">
                                    <span className="text-slate-400 tabular-nums whitespace-nowrap">
                                        {fmtFechaHora(m.created_at)}
                                    </span>
                                    <span className="text-slate-700">{m.accion_legible}</span>
                                    {m.afectado_nombre && (
                                        <span className="text-slate-500">· {m.afectado_nombre}</span>
                                    )}
                                    <span className="text-slate-400">
                                        por {m.usuario?.name || m.usuario_nombre || 'sistema'}
                                    </span>
                                </li>
                            ))}
                        </ol>
                    </div>
                )}
            </div>

            <UserModal
                show={modal.show}
                usuario={modal.usuario}
                roles={roles}
                ranchos={ranchos}
                puestosPorRancho={puestosPorRancho}
                trabajadoresSinCuenta={trabajadoresSinCuenta}
                onClose={() => setModal({ show: false, usuario: null })}
            />

            <PasswordModal
                show={modalPassword.show}
                usuario={modalPassword.usuario}
                onClose={() => setModalPassword({ show: false, usuario: null })}
            />
        </AuthenticatedLayout>
    );
}
