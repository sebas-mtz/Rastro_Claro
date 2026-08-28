import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { LogOut, PlusCircle, Undo2, FileText, Users, HeartCrack } from 'lucide-react';
import BajaModal from './BajaModal';
import { formatMXN } from '@/utils/currency';

function fmtFecha(f) {
    return f ? new Date(f).toLocaleDateString('es-MX') : '—';
}

export default function BajasIndex({
    auth, bajas, indicadores, tipos = {}, tiposConPrecio = [], filtros = {}, animalesActivos = [],
}) {
    const [showModal, setShowModal] = useState(false);
    const [locales, setLocales] = useState({
        tipo_salida: filtros.tipo_salida || '',
        desde: filtros.desde || '',
        hasta: filtros.hasta || '',
    });

    const filas = bajas?.data || [];

    const aplicar = (cambios) => {
        const nuevos = { ...locales, ...cambios };
        setLocales(nuevos);
        router.get(route('bajas.index'), nuevos, { preserveState: true, preserveScroll: true, replace: true });
    };

    const revertir = (id) => {
        if (confirm('¿Revertir esta baja? El ejemplar volverá a contar como activo en el rebaño.')) {
            router.delete(route('bajas.destroy', id), { preserveScroll: true });
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800">Bajas del rebaño</h2>}
        >
            <Head title="Bajas del rebaño" />

            <div className="py-8 px-4 sm:px-6 max-w-6xl mx-auto space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-800">Salidas del rebaño</h1>
                        <p className="text-gray-600">
                            Ventas, fallecimientos, descartes y traslados. El historial de cada ejemplar se conserva.
                        </p>
                    </div>
                    <button
                        onClick={() => setShowModal(true)}
                        className="bg-red-600 hover:bg-red-700 text-white font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
                    >
                        <PlusCircle className="w-5 h-5" /> Registrar salida
                    </button>
                </div>

                {/* Indicadores */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <p className="text-xs font-medium text-slate-500 flex items-center gap-1">
                            <Users className="w-3.5 h-3.5" /> Ejemplares activos
                        </p>
                        <p className="mt-2 text-2xl font-semibold text-slate-900">{indicadores?.activos ?? 0}</p>
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <p className="text-xs font-medium text-slate-500">Salidas en el periodo</p>
                        <p className="mt-2 text-2xl font-semibold text-slate-900">{indicadores?.bajas_periodo ?? 0}</p>
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <p className="text-xs font-medium text-slate-500 flex items-center gap-1">
                            <HeartCrack className="w-3.5 h-3.5" /> Mortalidad
                        </p>
                        <p className="mt-2 text-2xl font-semibold text-red-600">
                            {indicadores?.porcentaje_mortalidad != null
                                ? `${indicadores.porcentaje_mortalidad} %`
                                : '—'}
                        </p>
                        <p className="text-xs text-slate-400 mt-0.5">
                            {indicadores?.fallecimientos ?? 0} fallecimiento(s)
                        </p>
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <p className="text-xs font-medium text-slate-500">Ingresos por salidas</p>
                        <p className="mt-2 text-2xl font-semibold text-emerald-700">
                            {formatMXN(indicadores?.ingresos_por_salidas)}
                        </p>
                    </div>
                </div>

                {/* Filtros */}
                <div className="bg-white rounded-2xl border border-slate-200 p-4 grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <select
                        value={locales.tipo_salida}
                        onChange={(e) => aplicar({ tipo_salida: e.target.value })}
                        className="border border-slate-300 rounded-lg px-2 py-2 text-sm text-slate-700"
                        aria-label="Tipo de salida"
                    >
                        <option value="">Todos los tipos</option>
                        {Object.entries(tipos).map(([valor, etiqueta]) => (
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
                        onClick={() => { setLocales({ tipo_salida: '', desde: '', hasta: '' }); router.get(route('bajas.index'), {}, { preserveState: true, replace: true }); }}
                        className="text-sm text-slate-500 hover:text-slate-800 underline"
                    >
                        Limpiar filtros
                    </button>
                </div>

                {/* Tabla */}
                <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    {filas.length === 0 ? (
                        <div className="text-center py-10">
                            <LogOut className="w-14 h-14 text-slate-200 mx-auto mb-3" />
                            <p className="text-slate-500">No hay salidas registradas con estos filtros.</p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-slate-200">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Fecha</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Ejemplar</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Tipo</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Causa</th>
                                        <th className="px-5 py-3 text-right text-xs font-medium text-slate-500 uppercase">Precio</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Evidencia</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-slate-100">
                                    {filas.map((baja) => (
                                        <tr key={baja.id} className="hover:bg-slate-50">
                                            <td className="px-5 py-3 whitespace-nowrap text-sm text-slate-700">
                                                {fmtFecha(baja.fecha)}
                                            </td>
                                            <td className="px-5 py-3 whitespace-nowrap text-sm">
                                                {baja.animal ? (
                                                    <Link
                                                        href={`/animales/${baja.animal.id}`}
                                                        className="text-emerald-700 hover:underline font-medium"
                                                    >
                                                        {baja.animal.arete}
                                                    </Link>
                                                ) : '—'}
                                                {baja.animal?.alias && (
                                                    <span className="block text-xs text-slate-400">{baja.animal.alias}</span>
                                                )}
                                            </td>
                                            <td className="px-5 py-3 whitespace-nowrap">
                                                <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-slate-100 text-slate-700">
                                                    {tipos[baja.tipo_salida] || baja.tipo_salida}
                                                </span>
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-600">
                                                {baja.causa || '—'}
                                                {baja.diagnostico && (
                                                    <span className="block text-xs text-slate-400">{baja.diagnostico}</span>
                                                )}
                                            </td>
                                            <td className="px-5 py-3 whitespace-nowrap text-sm text-right font-medium text-slate-800 tabular-nums">
                                                {baja.precio_salida != null ? formatMXN(baja.precio_salida) : '—'}
                                            </td>
                                            <td className="px-5 py-3 whitespace-nowrap text-sm">
                                                {baja.documento ? (
                                                    <a
                                                        href={`/storage/${baja.documento}`}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="text-blue-600 hover:underline flex items-center gap-1"
                                                    >
                                                        <FileText className="w-4 h-4" /> Ver
                                                    </a>
                                                ) : <span className="text-slate-400">—</span>}
                                            </td>
                                            <td className="px-5 py-3 whitespace-nowrap text-sm">
                                                <button
                                                    onClick={() => revertir(baja.id)}
                                                    title="Revertir baja"
                                                    className="text-amber-600 hover:text-amber-800 flex items-center gap-1"
                                                >
                                                    <Undo2 className="w-4 h-4" /> Revertir
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {bajas?.links && bajas.links.length > 3 && (
                    <div className="flex justify-center">
                        <nav className="flex flex-wrap items-center gap-2">
                            {bajas.links.map((link, i) => (
                                <Link
                                    key={i}
                                    href={link.url || '#'}
                                    className={`px-3 py-1 rounded-lg text-sm font-medium ${
                                        link.active
                                            ? 'bg-red-600 text-white'
                                            : 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50'
                                    } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </nav>
                    </div>
                )}
            </div>

            <BajaModal
                show={showModal}
                onClose={() => setShowModal(false)}
                animalesActivos={animalesActivos}
                tipos={tipos}
                tiposConPrecio={tiposConPrecio}
            />
        </AuthenticatedLayout>
    );
}
