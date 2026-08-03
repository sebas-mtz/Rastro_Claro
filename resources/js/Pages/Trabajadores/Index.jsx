import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import {
    HardHat, PlusCircle, Search, Eye, Pencil, UserCheck, UserX, ClipboardList,
} from 'lucide-react';
import TrabajadorModal from './TrabajadorModal';
import { formatMXN } from '@/utils/currency';

function fmtFecha(f) {
    return f ? new Date(f).toLocaleDateString('es-MX') : '—';
}

export default function TrabajadoresIndex({
    auth,
    trabajadores,
    puestos = [],
    areas = [],
    tiposContratacion = {},
    filtros = {},
    permisos = {},
    usuariosDisponibles = [],
    resumen = {},
}) {
    const [modal, setModal] = useState({ show: false, trabajador: null });
    const [locales, setLocales] = useState({
        buscar: filtros.buscar || '',
        puesto_id: filtros.puesto_id || '',
        area: filtros.area || '',
        estado: filtros.estado || '',
    });

    const filas = trabajadores?.data || [];

    const aplicar = (cambios) => {
        const nuevos = { ...locales, ...cambios };
        setLocales(nuevos);
        router.get(route('trabajadores.index'), nuevos, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const limpiar = () => {
        setLocales({ buscar: '', puesto_id: '', area: '', estado: '' });
        router.get(route('trabajadores.index'), {}, { preserveState: true, replace: true });
    };

    const cambiarEstado = (trabajador) => {
        const inactivando = trabajador.activo;

        const mensaje = inactivando
            ? `¿Inactivar a ${trabajador.nombre_completo}? Su historial de actividades y costos se conserva, pero no podrá asignársele trabajo nuevo.`
            : `¿Reactivar a ${trabajador.nombre_completo}?`;

        if (!confirm(mensaje)) return;

        router.patch(
            route('trabajadores.estado', trabajador.id),
            { activo: !trabajador.activo },
            { preserveScroll: true },
        );
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800">Trabajadores</h2>}
        >
            <Head title="Trabajadores" />

            <div className="py-8 px-4 sm:px-6 max-w-7xl mx-auto space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-800">Trabajadores</h1>
                        <p className="text-gray-600">
                            Personal del rancho, su puesto y la mano de obra que aportan al rebaño.
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Link
                            href={route('actividades-trabajador.index')}
                            className="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
                        >
                            <ClipboardList className="w-5 h-5" /> Actividades
                        </Link>

                        {permisos.crear && (
                            <button
                                onClick={() => setModal({ show: true, trabajador: null })}
                                className="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
                            >
                                <PlusCircle className="w-5 h-5" /> Nuevo trabajador
                            </button>
                        )}
                    </div>
                </div>

                {/* Resumen */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <p className="text-xs font-medium text-slate-500">Personal registrado</p>
                        <p className="mt-2 text-2xl font-semibold text-slate-900">{resumen.total ?? 0}</p>
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <p className="text-xs font-medium text-slate-500">Activos</p>
                        <p className="mt-2 text-2xl font-semibold text-emerald-700">{resumen.activos ?? 0}</p>
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <p className="text-xs font-medium text-slate-500">Inactivos</p>
                        <p className="mt-2 text-2xl font-semibold text-slate-500">{resumen.inactivos ?? 0}</p>
                    </div>
                </div>

                {/* Filtros */}
                <div className="bg-white rounded-2xl border border-slate-200 p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <div className="relative lg:col-span-2">
                        <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input
                            type="search"
                            value={locales.buscar}
                            onChange={(e) => setLocales({ ...locales, buscar: e.target.value })}
                            onKeyDown={(e) => e.key === 'Enter' && aplicar({ buscar: locales.buscar })}
                            onBlur={() => aplicar({ buscar: locales.buscar })}
                            placeholder="Buscar por nombre o teléfono…"
                            className="w-full border border-slate-300 rounded-lg pl-9 pr-3 py-2 text-sm text-slate-700"
                            aria-label="Buscar trabajador"
                        />
                    </div>

                    <select
                        value={locales.puesto_id}
                        onChange={(e) => aplicar({ puesto_id: e.target.value })}
                        className="border border-slate-300 rounded-lg px-2 py-2 text-sm text-slate-700"
                        aria-label="Puesto"
                    >
                        <option value="">Todos los puestos</option>
                        {puestos.map((p) => (
                            <option key={p.id} value={p.id}>{p.nombre}</option>
                        ))}
                    </select>

                    <select
                        value={locales.area}
                        onChange={(e) => aplicar({ area: e.target.value })}
                        className="border border-slate-300 rounded-lg px-2 py-2 text-sm text-slate-700"
                        aria-label="Área"
                    >
                        <option value="">Todas las áreas</option>
                        {areas.map((a) => (
                            <option key={a} value={a}>{a}</option>
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

                    <button
                        onClick={limpiar}
                        className="text-sm text-slate-500 hover:text-slate-800 underline justify-self-start"
                    >
                        Limpiar filtros
                    </button>
                </div>

                {/* Tabla */}
                <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    {filas.length === 0 ? (
                        <div className="text-center py-12">
                            <HardHat className="w-14 h-14 text-slate-200 mx-auto mb-3" />
                            <p className="text-slate-500">No hay trabajadores con estos filtros.</p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-slate-200">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Nombre</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Puesto</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Área</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Teléfono</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Contratación</th>
                                        {permisos.verSensibles && (
                                            <th className="px-5 py-3 text-right text-xs font-medium text-slate-500 uppercase">Costo</th>
                                        )}
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Estado</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-slate-100">
                                    {filas.map((t) => (
                                        <tr key={t.id} className={'hover:bg-slate-50 ' + (t.activo ? '' : 'opacity-60')}>
                                            <td className="px-5 py-3 text-sm">
                                                <Link
                                                    href={route('trabajadores.show', t.id)}
                                                    className="text-emerald-700 hover:underline font-medium"
                                                >
                                                    {t.nombre_completo}
                                                </Link>
                                                {t.usuario && (
                                                    <span className="block text-xs text-slate-400">
                                                        Cuenta: {t.usuario.email}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-5 py-3 whitespace-nowrap text-sm text-slate-700">
                                                {t.puesto?.nombre || '—'}
                                            </td>
                                            <td className="px-5 py-3 whitespace-nowrap text-sm text-slate-600">
                                                {t.area || '—'}
                                            </td>
                                            <td className="px-5 py-3 whitespace-nowrap text-sm text-slate-600">
                                                {t.telefono || '—'}
                                            </td>
                                            <td className="px-5 py-3 whitespace-nowrap text-sm text-slate-600">
                                                {fmtFecha(t.fecha_contratacion)}
                                            </td>

                                            {/* Solo para quien tiene permiso: el backend ni siquiera
                                                envía estos campos a los demás. */}
                                            {permisos.verSensibles && (
                                                <td className="px-5 py-3 whitespace-nowrap text-sm text-right tabular-nums text-slate-800">
                                                    {t.costo_hora != null ? (
                                                        <span>{formatMXN(t.costo_hora)}<span className="text-xs text-slate-400"> /h</span></span>
                                                    ) : t.costo_jornada != null ? (
                                                        <span>{formatMXN(t.costo_jornada)}<span className="text-xs text-slate-400"> /jornada</span></span>
                                                    ) : (
                                                        <span className="text-slate-400">—</span>
                                                    )}
                                                </td>
                                            )}

                                            <td className="px-5 py-3 whitespace-nowrap">
                                                <span className={
                                                    'inline-flex px-2 py-1 text-xs font-semibold rounded-full ' +
                                                    (t.activo ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600')
                                                }>
                                                    {t.activo ? 'Activo' : 'Inactivo'}
                                                </span>
                                            </td>

                                            <td className="px-5 py-3 whitespace-nowrap text-sm">
                                                <div className="flex items-center gap-3">
                                                    <Link
                                                        href={route('trabajadores.show', t.id)}
                                                        title="Ver ficha"
                                                        className="text-slate-500 hover:text-slate-800"
                                                    >
                                                        <Eye className="w-4 h-4" />
                                                    </Link>

                                                    {permisos.editar && (
                                                        <button
                                                            onClick={() => setModal({ show: true, trabajador: t })}
                                                            title="Editar"
                                                            className="text-blue-600 hover:text-blue-800"
                                                        >
                                                            <Pencil className="w-4 h-4" />
                                                        </button>
                                                    )}

                                                    {permisos.cambiarEstado && (
                                                        <button
                                                            onClick={() => cambiarEstado(t)}
                                                            title={t.activo ? 'Inactivar' : 'Reactivar'}
                                                            className={t.activo ? 'text-amber-600 hover:text-amber-800' : 'text-emerald-600 hover:text-emerald-800'}
                                                        >
                                                            {t.activo ? <UserX className="w-4 h-4" /> : <UserCheck className="w-4 h-4" />}
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

                {trabajadores?.links && trabajadores.links.length > 3 && (
                    <div className="flex justify-center">
                        <nav className="flex flex-wrap items-center gap-2">
                            {trabajadores.links.map((link, i) => (
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
            </div>

            <TrabajadorModal
                show={modal.show}
                trabajador={modal.trabajador}
                puestos={puestos}
                areas={areas}
                tiposContratacion={tiposContratacion}
                usuariosDisponibles={usuariosDisponibles}
                puedeVerSensibles={permisos.verSensibles}
                onClose={() => setModal({ show: false, trabajador: null })}
            />
        </AuthenticatedLayout>
    );
}
