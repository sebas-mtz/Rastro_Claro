import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { History, ArrowLeft, Lock } from 'lucide-react';

function fmtFechaHora(f) {
    return f ? new Date(f).toLocaleString('es-MX', { dateStyle: 'short', timeStyle: 'medium' }) : '—';
}

/** Muestra un valor JSON de forma legible, sin volcar llaves ni comillas. */
function Valores({ datos }) {
    if (!datos || Object.keys(datos).length === 0) {
        return <span className="text-slate-300">—</span>;
    }

    return (
        <ul className="space-y-0.5">
            {Object.entries(datos).map(([clave, valor]) => (
                <li key={clave} className="text-xs">
                    <span className="text-slate-400">{clave}:</span>{' '}
                    <span className="text-slate-700">
                        {typeof valor === 'boolean' ? (valor ? 'sí' : 'no') : String(valor ?? '—')}
                    </span>
                </li>
            ))}
        </ul>
    );
}

export default function AuditoriaIndex({
    auth,
    movimientos,
    acciones = {},
    usuarios = [],
    filtros = {},
}) {
    const [locales, setLocales] = useState({
        accion: filtros.accion || '',
        usuario_id: filtros.usuario_id || '',
        desde: filtros.desde || '',
        hasta: filtros.hasta || '',
    });

    const filas = movimientos?.data || [];

    const aplicar = (cambios) => {
        const nuevos = { ...locales, ...cambios };
        setLocales(nuevos);
        router.get(route('admin.auditoria.index'), nuevos, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800">Bitácora del sistema</h2>}
        >
            <Head title="Bitácora" />

            <div className="py-8 px-4 sm:px-6 max-w-7xl mx-auto space-y-6">
                <Link
                    href={route('admin.usuarios.index')}
                    className="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1"
                >
                    <ArrowLeft className="w-4 h-4" /> Volver a usuarios
                </Link>

                <div>
                    <h1 className="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <History className="w-6 h-6 text-slate-400" /> Bitácora del sistema
                    </h1>
                    <p className="text-gray-600">
                        Quién hizo cada cambio crítico, cuándo y desde dónde.
                    </p>
                </div>

                <p className="text-sm text-slate-600 bg-slate-50 border border-slate-200 rounded-lg p-3 flex items-start gap-2">
                    <Lock className="w-4 h-4 mt-0.5 shrink-0 text-slate-400" />
                    <span>
                        Esta bitácora es de solo lectura. No existe ninguna opción para editarla ni
                        borrarla desde la aplicación, a propósito: un registro que puede alterarse no
                        sirve como evidencia.
                    </span>
                </p>

                {/* Filtros */}
                <div className="bg-white rounded-2xl border border-slate-200 p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <select
                        value={locales.accion}
                        onChange={(e) => aplicar({ accion: e.target.value })}
                        className="border border-slate-300 rounded-lg px-2 py-2 text-sm text-slate-700 lg:col-span-2"
                        aria-label="Tipo de acción"
                    >
                        <option value="">Todas las acciones</option>
                        {Object.entries(acciones).map(([valor, etiqueta]) => (
                            <option key={valor} value={valor}>{etiqueta}</option>
                        ))}
                    </select>

                    <select
                        value={locales.usuario_id}
                        onChange={(e) => aplicar({ usuario_id: e.target.value })}
                        className="border border-slate-300 rounded-lg px-2 py-2 text-sm text-slate-700"
                        aria-label="Autor"
                    >
                        <option value="">Cualquier autor</option>
                        {usuarios.map((u) => (
                            <option key={u.id} value={u.id}>{u.name}</option>
                        ))}
                    </select>

                    <input
                        type="date"
                        value={locales.desde}
                        onChange={(e) => aplicar({ desde: e.target.value })}
                        className="border border-slate-300 rounded-lg px-2 py-2 text-sm text-slate-700"
                        aria-label="Desde"
                    />
                    <input
                        type="date"
                        value={locales.hasta}
                        onChange={(e) => aplicar({ hasta: e.target.value })}
                        className="border border-slate-300 rounded-lg px-2 py-2 text-sm text-slate-700"
                        aria-label="Hasta"
                    />
                </div>

                {/* Tabla */}
                <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    {filas.length === 0 ? (
                        <div className="text-center py-12">
                            <History className="w-14 h-14 text-slate-200 mx-auto mb-3" />
                            <p className="text-slate-500">No hay movimientos con estos filtros.</p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-slate-200">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Fecha</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Acción</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Autor</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Afectado</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Antes</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Después</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">IP</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-slate-100">
                                    {filas.map((m) => (
                                        <tr key={m.id} className="hover:bg-slate-50 align-top">
                                            <td className="px-5 py-3 whitespace-nowrap text-sm text-slate-600 tabular-nums">
                                                {fmtFechaHora(m.created_at)}
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-800">
                                                {m.accion_legible}
                                                {m.descripcion && (
                                                    <span className="block text-xs text-slate-400 max-w-xs">
                                                        {m.descripcion}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-600">
                                                {m.usuario?.name || m.usuario_nombre || (
                                                    <span className="text-slate-400">sistema</span>
                                                )}
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-600">
                                                {m.afectado?.name || m.afectado_nombre || (
                                                    <span className="text-slate-400">—</span>
                                                )}
                                            </td>
                                            <td className="px-5 py-3"><Valores datos={m.valor_anterior} /></td>
                                            <td className="px-5 py-3"><Valores datos={m.valor_nuevo} /></td>
                                            <td className="px-5 py-3 whitespace-nowrap text-xs text-slate-400 tabular-nums">
                                                {m.ip || '—'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {movimientos?.links && movimientos.links.length > 3 && (
                    <div className="flex justify-center">
                        <nav className="flex flex-wrap items-center gap-2">
                            {movimientos.links.map((link, i) => (
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
        </AuthenticatedLayout>
    );
}
