import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import {
    DollarSign, PlusCircle, Search, Edit, Trash2, Download, FileText,
    TrendingUp, TrendingDown, Filter,
} from 'lucide-react';
import CostoModal from './CostoModal';
import { formatMXN } from '@/utils/currency';

const CATEGORIA_LABEL = {
    alimentacion: 'Alimentación',
    medicamentos: 'Medicamentos',
    vacunas: 'Vacunas',
    consultas_veterinarias: 'Consultas veterinarias',
    mano_obra: 'Mano de obra',
    transporte: 'Transporte',
    compra_animales: 'Compra de animales',
    mantenimiento: 'Mantenimiento',
    insumos: 'Insumos',
    faenas: 'Faenas',
    sacrificios: 'Sacrificios',
    servicios: 'Servicios',
    administrativos: 'Costos administrativos',
    otros: 'Otros gastos',
};

export default function CostosIndex({ auth, costos, totales, comparacion, filtros, categorias, animales, lotes, faenas, sacrificios }) {
    const [showModal, setShowModal] = useState(false);
    const [costoEditando, setCostoEditando] = useState(null);
    const [concepto, setConcepto] = useState(filtros?.concepto || '');
    const [filtrosLocales, setFiltrosLocales] = useState({
        categoria: filtros?.categoria || '',
        fecha_desde: filtros?.fecha_desde || '',
        fecha_hasta: filtros?.fecha_hasta || '',
        animal_id: filtros?.animal_id || '',
        lote_id: filtros?.lote_id || '',
        tipo_costo: filtros?.tipo_costo || '',
    });

    const costosArray = costos?.data || [];

    const aplicarFiltros = (cambios) => {
        const nuevos = { ...filtrosLocales, ...cambios, concepto };
        setFiltrosLocales((prev) => ({ ...prev, ...cambios }));
        router.get(route('costos.index'), nuevos, { preserveState: true, replace: true, preserveScroll: true });
    };

    const buscarPorConcepto = () => {
        router.get(route('costos.index'), { ...filtrosLocales, concepto }, { preserveState: true, replace: true, preserveScroll: true });
    };

    const limpiarFiltros = () => {
        setConcepto('');
        setFiltrosLocales({ categoria: '', fecha_desde: '', fecha_hasta: '', animal_id: '', lote_id: '', tipo_costo: '' });
        router.get(route('costos.index'), {}, { preserveState: true, replace: true });
    };

    const handleDelete = (id) => {
        if (confirm('¿Seguro que deseas eliminar este costo? Esta acción no se puede deshacer.')) {
            router.delete(route('costos.destroy', id));
        }
    };

    const abrirNuevo = () => {
        setCostoEditando(null);
        setShowModal(true);
    };

    const abrirEditar = (costo) => {
        setCostoEditando(costo);
        setShowModal(true);
    };

    const totalPorCategoria = totales?.total_por_categoria || {};

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800">Módulo de Costos</h2>}
        >
            <Head title="Costos" />

            <div className="py-8 px-6 max-w-7xl mx-auto">
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-800">Control de Costos</h1>
                        <p className="text-gray-600">Alimentación, salud, mano de obra, transporte y más.</p>
                    </div>
                    <div className="flex gap-2">
                        <a
                            href={route('costos.exportar.csv') + '?' + new URLSearchParams({ ...filtrosLocales, concepto }).toString()}
                            className="border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-medium px-3 py-2 rounded-lg flex items-center gap-2 transition text-sm"
                        >
                            <Download className="w-4 h-4" /> CSV
                        </a>
                        <a
                            href={route('costos.exportar.pdf') + '?' + new URLSearchParams({ ...filtrosLocales, concepto }).toString()}
                            className="border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-medium px-3 py-2 rounded-lg flex items-center gap-2 transition text-sm"
                        >
                            <FileText className="w-4 h-4" /> PDF
                        </a>
                        <button
                            onClick={abrirNuevo}
                            className="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
                        >
                            <PlusCircle className="w-5 h-5" /> Nuevo Costo
                        </button>
                    </div>
                </div>

                {/* Totales */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div className="bg-white p-4 rounded-lg shadow border">
                        <p className="text-sm text-gray-600">Total de costos</p>
                        <p className="text-2xl font-bold text-gray-800">{formatMXN(totales?.total_general)}</p>
                        <p className="text-xs text-gray-500">{totales?.cantidad_registros ?? 0} registros</p>
                    </div>
                    <div className="bg-white p-4 rounded-lg shadow border">
                        <p className="text-sm text-gray-600">Ingresos por ventas</p>
                        <p className="text-2xl font-bold text-green-700">{formatMXN(comparacion?.ingresos)}</p>
                    </div>
                    <div className="bg-white p-4 rounded-lg shadow border">
                        <p className="text-sm text-gray-600">{comparacion?.estado === 'utilidad' ? 'Utilidad' : 'Pérdida'}</p>
                        <p className={`text-2xl font-bold flex items-center gap-1 ${comparacion?.estado === 'utilidad' ? 'text-green-700' : 'text-red-700'}`}>
                            {comparacion?.estado === 'utilidad' ? <TrendingUp className="w-5 h-5" /> : <TrendingDown className="w-5 h-5" />}
                            {formatMXN(comparacion?.utilidad)}
                        </p>
                    </div>
                    <div className="bg-white p-4 rounded-lg shadow border">
                        <p className="text-sm text-gray-600">Costo promedio por animal</p>
                        <p className="text-2xl font-bold text-gray-800">{formatMXN(totales?.costo_promedio_por_animal)}</p>
                    </div>
                </div>

                {/* Total por categoría */}
                {Object.keys(totalPorCategoria).length > 0 && (
                    <div className="bg-white rounded-lg shadow border p-4 mb-6">
                        <h3 className="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                            <Filter className="w-4 h-4" /> Total por categoría
                        </h3>
                        <div className="flex flex-wrap gap-2">
                            {Object.entries(totalPorCategoria).map(([cat, monto]) => (
                                <span key={cat} className="bg-gray-100 text-gray-700 text-xs font-medium px-3 py-1.5 rounded-full">
                                    {CATEGORIA_LABEL[cat] || cat}: {formatMXN(monto)}
                                </span>
                            ))}
                        </div>
                    </div>
                )}

                {/* Filtros */}
                <div className="bg-white rounded-lg shadow border p-4 mb-6 space-y-3">
                    <div className="flex flex-col sm:flex-row gap-3">
                        <div className="flex items-center bg-white border border-gray-300 rounded-lg px-3 py-2 flex-1">
                            <Search className="w-5 h-5 text-gray-500 mr-2" />
                            <input
                                type="text"
                                placeholder="Buscar por concepto..."
                                value={concepto}
                                onChange={(e) => setConcepto(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && buscarPorConcepto()}
                                className="w-full outline-none text-gray-700"
                            />
                        </div>
                        <button
                            onClick={buscarPorConcepto}
                            className="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium"
                        >
                            Buscar
                        </button>
                        <button
                            onClick={limpiarFiltros}
                            className="text-gray-500 hover:text-gray-700 px-4 py-2 rounded-lg text-sm"
                        >
                            Limpiar filtros
                        </button>
                    </div>

                    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                        <select
                            value={filtrosLocales.categoria}
                            onChange={(e) => aplicarFiltros({ categoria: e.target.value })}
                            className="border border-gray-300 rounded-lg px-2 py-2 text-sm text-gray-700"
                        >
                            <option value="">Todas las categorías</option>
                            {categorias.map((c) => (
                                <option key={c} value={c}>{CATEGORIA_LABEL[c] || c}</option>
                            ))}
                        </select>

                        <select
                            value={filtrosLocales.tipo_costo}
                            onChange={(e) => aplicarFiltros({ tipo_costo: e.target.value })}
                            className="border border-gray-300 rounded-lg px-2 py-2 text-sm text-gray-700"
                        >
                            <option value="">Directo e indirecto</option>
                            <option value="directo">Directo</option>
                            <option value="indirecto">Indirecto</option>
                        </select>

                        <select
                            value={filtrosLocales.animal_id}
                            onChange={(e) => aplicarFiltros({ animal_id: e.target.value })}
                            className="border border-gray-300 rounded-lg px-2 py-2 text-sm text-gray-700"
                        >
                            <option value="">Todos los ejemplares</option>
                            {animales.map((a) => (
                                <option key={a.id} value={a.id}>{a.alias || a.arete}</option>
                            ))}
                        </select>

                        <select
                            value={filtrosLocales.lote_id}
                            onChange={(e) => aplicarFiltros({ lote_id: e.target.value })}
                            className="border border-gray-300 rounded-lg px-2 py-2 text-sm text-gray-700"
                        >
                            <option value="">Todos los lotes</option>
                            {lotes.map((l) => (
                                <option key={l.id} value={l.id}>{l.nombre}</option>
                            ))}
                        </select>

                        <input
                            type="date"
                            value={filtrosLocales.fecha_desde}
                            onChange={(e) => aplicarFiltros({ fecha_desde: e.target.value })}
                            className="border border-gray-300 rounded-lg px-2 py-2 text-sm text-gray-700"
                        />
                        <input
                            type="date"
                            value={filtrosLocales.fecha_hasta}
                            onChange={(e) => aplicarFiltros({ fecha_hasta: e.target.value })}
                            className="border border-gray-300 rounded-lg px-2 py-2 text-sm text-gray-700"
                        />
                    </div>
                </div>

                {/* Tabla */}
                <div className="bg-white rounded-lg shadow overflow-hidden">
                    {costosArray.length === 0 ? (
                        <div className="text-center py-8">
                            <DollarSign className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                            <p className="text-gray-500 text-lg">No hay costos registrados con estos filtros.</p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Concepto</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Relacionado</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Registrado por</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {costosArray.map((costo) => (
                                        <tr key={costo.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {new Date(costo.fecha).toLocaleDateString('es-MX')}
                                            </td>
                                            <td className="px-6 py-4">
                                                <p className="text-sm font-medium text-gray-900">{costo.concepto}</p>
                                                {costo.proveedor && <p className="text-xs text-gray-500">{costo.proveedor}</p>}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    {CATEGORIA_LABEL[costo.categoria] || costo.categoria}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {costo.animal?.alias || costo.animal?.arete || costo.lote?.nombre || '—'}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                                {formatMXN(costo.monto)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {costo.usuario?.name || '—'}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div className="flex items-center gap-2">
                                                    <button
                                                        onClick={() => abrirEditar(costo)}
                                                        className="text-green-600 hover:text-green-900 p-1 rounded transition"
                                                    >
                                                        <Edit className="w-4 h-4" />
                                                    </button>
                                                    <button
                                                        onClick={() => handleDelete(costo.id)}
                                                        className="text-red-600 hover:text-red-900 p-1 rounded transition"
                                                    >
                                                        <Trash2 className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {costos?.links && costos.links.length > 3 && (
                    <div className="mt-6 flex justify-center">
                        <nav className="flex items-center flex-wrap gap-2">
                            {costos.links.map((link, index) => (
                                <Link
                                    key={index}
                                    href={link.url || '#'}
                                    className={`px-3 py-1 rounded-lg text-sm font-medium ${
                                        link.active
                                            ? 'bg-green-600 text-white'
                                            : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                                    } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </nav>
                    </div>
                )}
            </div>

            <CostoModal
                show={showModal}
                onClose={() => setShowModal(false)}
                costo={costoEditando}
                animales={animales || []}
                lotes={lotes || []}
                faenas={faenas || []}
                sacrificios={sacrificios || []}
            />
        </AuthenticatedLayout>
    );
}
