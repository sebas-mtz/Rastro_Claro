import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { ClipboardList, PlusCircle, ArrowLeft, Trash2, Users } from 'lucide-react';
import ActividadModal from './ActividadModal';
import { formatMXN } from '@/utils/currency';

function fmtFecha(f) {
    return f ? new Date(f).toLocaleDateString('es-MX') : '—';
}

function nombreDe(t) {
    if (!t) return '—';
    return [t.nombre, t.apellido_paterno, t.apellido_materno].filter(Boolean).join(' ');
}

export default function ActividadesTrabajador({
    auth,
    actividades,
    trabajadores = [],
    tiposActividad = {},
    modalidadesPago = {},
    animales = [],
    lotes = [],
    filtros = {},
    permisos = {},
    totales,
}) {
    const [showModal, setShowModal] = useState(false);
    const [locales, setLocales] = useState({
        trabajador_id: filtros.trabajador_id || '',
        tipo_actividad: filtros.tipo_actividad || '',
        desde: filtros.desde || '',
        hasta: filtros.hasta || '',
    });

    const filas = actividades?.data || [];

    const aplicar = (cambios) => {
        const nuevos = { ...locales, ...cambios };
        setLocales(nuevos);
        router.get(route('actividades-trabajador.index'), nuevos, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const eliminar = (id) => {
        if (confirm('¿Eliminar esta actividad? También se retirará el costo de mano de obra que generó.')) {
            router.delete(route('actividades-trabajador.destroy', id), { preserveScroll: true });
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800">Actividades y mano de obra</h2>}
        >
            <Head title="Actividades de trabajadores" />

            <div className="py-8 px-4 sm:px-6 max-w-7xl mx-auto space-y-6">
                <Link
                    href={route('trabajadores.index')}
                    className="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1"
                >
                    <ArrowLeft className="w-4 h-4" /> Volver a trabajadores
                </Link>

                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-800">Actividades y mano de obra</h1>
                        <p className="text-gray-600">
                            Trabajo realizado sobre el rebaño. Cada registro genera su costo automáticamente.
                        </p>
                    </div>

                    {permisos.registrarActividad && (
                        <button
                            onClick={() => setShowModal(true)}
                            // Sin gente registrada el formulario no tendría a
                            // quién asignar el trabajo.
                            disabled={trabajadores.length === 0}
                            title={trabajadores.length === 0 ? 'Primero registra a tu gente en Trabajadores' : undefined}
                            className="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-emerald-600"
                        >
                            <PlusCircle className="w-5 h-5" /> Registrar actividad
                        </button>
                    )}
                </div>

                {totales && (
                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div className="rounded-2xl border border-slate-200 bg-white p-4">
                            <p className="text-xs font-medium text-slate-500">Actividades</p>
                            <p className="mt-2 text-2xl font-semibold text-slate-900">{totales.actividades}</p>
                        </div>
                        <div className="rounded-2xl border border-slate-200 bg-white p-4">
                            <p className="text-xs font-medium text-slate-500">Horas</p>
                            <p className="mt-2 text-2xl font-semibold text-slate-900 tabular-nums">{totales.horas}</p>
                        </div>
                        <div className="rounded-2xl border border-slate-200 bg-white p-4">
                            <p className="text-xs font-medium text-slate-500">Jornadas</p>
                            <p className="mt-2 text-2xl font-semibold text-slate-900 tabular-nums">{totales.jornadas}</p>
                        </div>
                        <div className="rounded-2xl border border-slate-200 bg-white p-4">
                            <p className="text-xs font-medium text-slate-500">Costo de mano de obra</p>
                            <p className="mt-2 text-2xl font-semibold text-emerald-700 tabular-nums">
                                {formatMXN(totales.costo)}
                            </p>
                        </div>
                    </div>
                )}

                {/* Filtros */}
                <div className="bg-white rounded-2xl border border-slate-200 p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <select
                        value={locales.trabajador_id}
                        onChange={(e) => aplicar({ trabajador_id: e.target.value })}
                        className="border border-slate-300 rounded-lg px-2 py-2 text-sm text-slate-700"
                        aria-label="Trabajador"
                    >
                        <option value="">Todos los trabajadores</option>
                        {trabajadores.map((t) => (
                            <option key={t.id} value={t.id}>{nombreDe(t)}</option>
                        ))}
                    </select>

                    <select
                        value={locales.tipo_actividad}
                        onChange={(e) => aplicar({ tipo_actividad: e.target.value })}
                        className="border border-slate-300 rounded-lg px-2 py-2 text-sm text-slate-700"
                        aria-label="Tipo de actividad"
                    >
                        <option value="">Todas las actividades</option>
                        {Object.entries(tiposActividad).map(([valor, etiqueta]) => (
                            <option key={valor} value={valor}>{etiqueta}</option>
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

                    <button
                        onClick={() => {
                            setLocales({ trabajador_id: '', tipo_actividad: '', desde: '', hasta: '' });
                            router.get(route('actividades-trabajador.index'), {}, { preserveState: true, replace: true });
                        }}
                        className="text-sm text-slate-500 hover:text-slate-800 underline justify-self-start"
                    >
                        Limpiar filtros
                    </button>
                </div>

                {/* Tabla */}
                <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    {filas.length === 0 ? (
                        <div className="text-center py-12 px-4">
                            <ClipboardList className="w-14 h-14 text-slate-200 mx-auto mb-3" />

                            {/* Sin gente registrada no se puede registrar trabajo.
                                Antes decía solo "no hay actividades", que hacía
                                parecer un módulo vacío cuando en realidad falta
                                el paso previo. */}
                            {trabajadores.length === 0 ? (
                                <>
                                    <p className="text-slate-700 font-medium">
                                        Todavía no hay personas registradas en el rancho.
                                    </p>
                                    <p className="text-slate-500 text-sm mt-1 max-w-md mx-auto">
                                        Una actividad siempre es de alguien. Da de alta primero a tu
                                        gente y después podrás registrar su trabajo y el costo de la
                                        mano de obra.
                                    </p>
                                    <Link
                                        href={route('trabajadores.index')}
                                        className="mt-5 inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium"
                                    >
                                        <Users className="w-4 h-4" /> Registrar un trabajador
                                    </Link>
                                </>
                            ) : (
                                <p className="text-slate-500">No hay actividades registradas con estos filtros.</p>
                            )}
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-slate-200">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Fecha</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Trabajador</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Actividad</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Destino</th>
                                        <th className="px-5 py-3 text-right text-xs font-medium text-slate-500 uppercase">Tiempo</th>
                                        {permisos.verCostos && (
                                            <th className="px-5 py-3 text-right text-xs font-medium text-slate-500 uppercase">Costo</th>
                                        )}
                                        <th className="px-5 py-3" />
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-slate-100">
                                    {filas.map((a) => (
                                        <tr key={a.id} className="hover:bg-slate-50">
                                            <td className="px-5 py-3 whitespace-nowrap text-sm text-slate-700">
                                                {fmtFecha(a.fecha)}
                                            </td>
                                            <td className="px-5 py-3 text-sm">
                                                <Link
                                                    href={route('trabajadores.show', a.trabajador_id)}
                                                    className="text-emerald-700 hover:underline"
                                                >
                                                    {nombreDe(a.trabajador)}
                                                </Link>
                                                {a.trabajador?.puesto && (
                                                    <span className="block text-xs text-slate-400">
                                                        {a.trabajador.puesto.nombre}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-700">
                                                {a.tipo_legible}
                                                {a.descripcion && (
                                                    <span className="block text-xs text-slate-400">{a.descripcion}</span>
                                                )}
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-600">
                                                {a.animal?.arete || a.lote?.nombre || (
                                                    <span className="text-slate-400">General</span>
                                                )}
                                                {a.animales_atendidos > 1 && (
                                                    <span className="block text-xs text-slate-400">
                                                        Repartido entre {a.animales_atendidos}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-5 py-3 whitespace-nowrap text-sm text-right text-slate-700 tabular-nums">
                                                {a.modalidad_pago === 'jornada'
                                                    ? `${a.jornadas ?? 0} jornada(s)`
                                                    : `${a.horas_trabajadas ?? 0} h`}
                                            </td>
                                            {permisos.verCostos && (
                                                <td className="px-5 py-3 whitespace-nowrap text-sm text-right font-medium text-slate-800 tabular-nums">
                                                    {formatMXN(a.costo_total)}
                                                </td>
                                            )}
                                            <td className="px-5 py-3 whitespace-nowrap text-right">
                                                <button
                                                    onClick={() => eliminar(a.id)}
                                                    title="Eliminar actividad"
                                                    className="text-red-500 hover:text-red-700"
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {actividades?.links && actividades.links.length > 3 && (
                    <div className="flex justify-center">
                        <nav className="flex flex-wrap items-center gap-2">
                            {actividades.links.map((link, i) => (
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

            <ActividadModal
                show={showModal}
                onClose={() => setShowModal(false)}
                trabajadores={trabajadores}
                tiposActividad={tiposActividad}
                modalidadesPago={modalidadesPago}
                animales={animales}
                lotes={lotes}
                puedeVerCostos={permisos.verCostos}
            />
        </AuthenticatedLayout>
    );
}
