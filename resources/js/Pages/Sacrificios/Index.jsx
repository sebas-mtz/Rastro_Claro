import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Scissors, PlusCircle, Search, AlertTriangle, Calculator, Scale, Edit, Trash2, Eye } from 'lucide-react';
import SacrificioModal from './SacrificioModal';
import { ArrowLeft } from 'lucide-react';


export default function SacrificiosIndex({ auth, sacrificios, estadisticas, animales, lotes }) {
    const [showModal, setShowModal] = useState(false);
    const [busqueda, setBusqueda] = useState('');

    // ✅ CORRECCIÓN: Usar sacrificios.data para el array
    const sacrificiosArray = sacrificios?.data || [];
    const sacrificiosFiltrados = sacrificiosArray.filter(sacrificio =>
        sacrificio.motivo?.toLowerCase().includes(busqueda.toLowerCase()) ||
        sacrificio.animal_alias?.toLowerCase().includes(busqueda.toLowerCase()) ||
        sacrificio.animal_arete?.toLowerCase().includes(busqueda.toLowerCase())
    );

    const handleDelete = (id) => {
        if (confirm("¿Seguro que deseas eliminar este registro de sacrificio?")) {
            router.delete(route("sacrificios.destroy", id));
        }
    };

    const getMotivoColor = (motivo) => {
        switch (motivo) {
            case 'descarte': return 'bg-yellow-100 text-yellow-800';
            case 'enfermedad': return 'bg-red-100 text-red-800';
            case 'accidente': return 'bg-blue-100 text-blue-800';
            case 'autoconsumo': return 'bg-green-100 text-green-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    const getMotivoLabel = (motivo) => {
        switch (motivo) {
            case 'descarte': return 'Descarte';
            case 'enfermedad': return 'Enfermedad';
            case 'accidente': return 'Accidente';
            case 'autoconsumo': return 'Auto-consumo';
            default: return motivo;
        }
    };

    // ✅ Usar estadísticas del controller en lugar de calcularlas en el frontend
    const stats = estadisticas || {
        total_sacrificios: 0,
        rendimiento_promedio: 0,
        por_motivo: {
            descarte: 0,
            enfermedad: 0,
            accidente: 0,
            autoconsumo: 0
        },
        subproductos_totales: {
            cuero: 0,
            grasa: 0,
            visceras: 0,
            plumas: 0
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800">Módulo de Sacrificios</h2>}
        >
            <Head title="Sacrificios" />

            <div className="py-8 px-6 max-w-7xl mx-auto">
                {/* Header */}
                <div className="flex justify-between items-center mb-6">
    
    {/* Botón Volver */}
    <a
        href="/animales"
        className="flex items-center text-sm text-red-700 hover:text-red-800 transition"
    >
        <ArrowLeft className="w-4 h-4 mr-1" /> Volver
    </a>

    {/* Título */}
    <div className="text-center flex-1">
        <h1 className="text-2xl font-bold text-gray-800">Registro de Sacrificios</h1>
        <p className="text-gray-600">Control de sacrificios y subproductos</p>
    </div>

    {/* Botón nuevo */}
    <button
        onClick={() => setShowModal(true)}
        className="bg-red-600 hover:bg-red-700 text-white font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
    >
        <PlusCircle className="w-5 h-5" />
        Nuevo Sacrificio
    </button>

</div>


                {/* Estadísticas - Usando datos del controller */}
                <div className="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                    <div className="bg-white p-4 rounded-lg shadow border">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Total</p>
                                <p className="text-2xl font-bold text-gray-800">{stats.total_sacrificios}</p>
                            </div>
                            <Scissors className="w-8 h-8 text-red-500" />
                        </div>
                    </div>
                    <div className="bg-white p-4 rounded-lg shadow border">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Descarte</p>
                                <p className="text-2xl font-bold text-gray-800">{stats.por_motivo?.descarte || 0}</p>
                            </div>
                            <AlertTriangle className="w-8 h-8 text-yellow-500" />
                        </div>
                    </div>
                    <div className="bg-white p-4 rounded-lg shadow border">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Enfermedad</p>
                                <p className="text-2xl font-bold text-gray-800">{stats.por_motivo?.enfermedad || 0}</p>
                            </div>
                            <AlertTriangle className="w-8 h-8 text-orange-500" />
                        </div>
                    </div>
                    <div className="bg-white p-4 rounded-lg shadow border">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Accidente</p>
                                <p className="text-2xl font-bold text-gray-800">{stats.por_motivo?.accidente || 0}</p>
                            </div>
                            <AlertTriangle className="w-8 h-8 text-blue-500" />
                        </div>
                    </div>
                    <div className="bg-white p-4 rounded-lg shadow border">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Auto-consumo</p>
                                <p className="text-2xl font-bold text-gray-800">{stats.por_motivo?.autoconsumo || 0}</p>
                            </div>
                            <Calculator className="w-8 h-8 text-green-500" />
                        </div>
                    </div>
                </div>

                {/* Búsqueda y Filtros */}
                <div className="flex flex-col sm:flex-row gap-3 mb-6">
                    <div className="flex items-center bg-white border border-gray-300 rounded-lg px-3 py-2 w-full">
                        <Search className="w-5 h-5 text-gray-500 mr-2" />
                        <input
                            type="text"
                            placeholder="Buscar por motivo, animal o arete..."
                            value={busqueda}
                            onChange={(e) => setBusqueda(e.target.value)}
                            className="w-full outline-none text-gray-700"
                        />
                    </div>
                    <div className="text-sm text-gray-600 flex items-center">
                        Mostrando {sacrificiosFiltrados.length} de {sacrificiosArray.length} sacrificios
                    </div>
                </div>

                {/* Lista de Sacrificios */}
                <div className="bg-white rounded-lg shadow overflow-hidden">
                    {sacrificiosFiltrados.length === 0 ? (
                        <div className="text-center py-8">
                            <Scissors className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                            <p className="text-gray-500 text-lg">
                                {sacrificiosArray.length === 0 
                                    ? "No hay sacrificios registrados" 
                                    : "No se encontraron sacrificios con ese criterio de búsqueda"
                                }
                            </p>
                            {sacrificiosArray.length === 0 && (
                                <button
                                    onClick={() => setShowModal(true)}
                                    className="mt-4 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg"
                                >
                                    Registrar primer sacrificio
                                </button>
                            )}
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Animal</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Motivo</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Peso Vivo</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Peso Canal</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rendimiento</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subproductos</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {sacrificiosFiltrados.map((sacrificio) => (
                                        <tr key={sacrificio.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {new Date(sacrificio.fecha).toLocaleDateString()}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div>
                                                    <p className="text-sm font-medium text-gray-900">
                                                        {sacrificio.animal_alias || 'Sin alias'}
                                                    </p>
                                                    <p className="text-sm text-gray-500">
                                                        {sacrificio.animal_arete || 'Sin arete'}
                                                    </p>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getMotivoColor(sacrificio.motivo)}`}>
                                                    {getMotivoLabel(sacrificio.motivo)}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div className="flex items-center">
                                                    <Scale className="w-4 h-4 mr-1 text-gray-400" />
                                                    {sacrificio.peso_vivo} kg
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div className="flex items-center">
                                                    <Scale className="w-4 h-4 mr-1 text-gray-400" />
                                                    {sacrificio.peso_canal} kg
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <span className="font-semibold text-blue-600">
                                                    {sacrificio.rendimiento}%
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex flex-wrap gap-1">
                                                    {sacrificio.cuero && (
                                                        <span className="bg-amber-100 text-amber-800 px-2 py-1 rounded text-xs">
                                                            Cuero
                                                        </span>
                                                    )}
                                                    {sacrificio.grasa && (
                                                        <span className="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">
                                                            Grasa
                                                        </span>
                                                    )}
                                                    {sacrificio.visceras && (
                                                        <span className="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">
                                                            Vísceras
                                                        </span>
                                                    )}
                                                    {sacrificio.plumas && (
                                                        <span className="bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs">
                                                            Plumas
                                                        </span>
                                                    )}
                                                    {!sacrificio.cuero && !sacrificio.grasa && !sacrificio.visceras && !sacrificio.plumas && (
                                                        <span className="text-gray-400 text-xs">Sin subproductos</span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div className="flex items-center gap-2">
                                                    <Link
                                                        href={route('sacrificios.show', sacrificio.id)}
                                                        className="text-blue-600 hover:text-blue-900 p-1 rounded transition"
                                                    >
                                                        <Eye className="w-4 h-4" />
                                                    </Link>
                                                    <Link
                                                        href={route('sacrificios.edit', sacrificio.id)}
                                                        className="text-green-600 hover:text-green-900 p-1 rounded transition"
                                                    >
                                                        <Edit className="w-4 h-4" />
                                                    </Link>
                                                    <button 
                                                        onClick={() => handleDelete(sacrificio.id)}
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

                {/* Paginación */}
                {sacrificios.links && sacrificios.links.length > 3 && (
                    <div className="mt-6 flex justify-center">
                        <nav className="flex items-center space-x-2">
                            {sacrificios.links.map((link, index) => (
                                <Link
                                    key={index}
                                    href={link.url || '#'}
                                    className={`px-3 py-1 rounded-lg text-sm font-medium ${
                                        link.active
                                            ? 'bg-red-600 text-white'
                                            : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                                    } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </nav>
                    </div>
                )}

                {/* Resumen de Rendimientos */}
                {sacrificiosFiltrados.length > 0 && (
                    <div className="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="bg-white p-4 rounded-lg shadow border">
                            <h3 className="text-lg font-semibold text-gray-800 mb-2">Rendimiento Promedio</h3>
                            <p className="text-2xl font-bold text-blue-600">
                            {Number(stats.rendimiento_promedio ?? 0).toFixed(1)}%
                            </p>
                        </div>
                        <div className="bg-white p-4 rounded-lg shadow border">
                            <h3 className="text-lg font-semibold text-gray-800 mb-2">Subproductos Totales</h3>
                            <p className="text-2xl font-bold text-green-600">
                                {Object.values(stats.subproductos_totales || {}).reduce((a, b) => a + b, 0)}
                            </p>
                            <div className="text-sm text-gray-600 mt-1">
                                Cuero: {stats.subproductos_totales?.cuero || 0} • 
                                Grasa: {stats.subproductos_totales?.grasa || 0} • 
                                Vísceras: {stats.subproductos_totales?.visceras || 0}
                                {stats.subproductos_totales?.plumas > 0 && ` • Plumas: ${stats.subproductos_totales.plumas}`}
                            </div>
                        </div>
                        <div className="bg-white p-4 rounded-lg shadow border">
                            <h3 className="text-lg font-semibold text-gray-800 mb-2">Distribución por Motivo</h3>
                            <div className="space-y-1">
                                {Object.entries(stats.por_motivo || {}).map(([motivo, cantidad]) => (
                                    <div key={motivo} className="flex justify-between text-sm">
                                        <span className="capitalize">{motivo}:</span>
                                        <span className="font-medium">{cantidad}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                )}
            </div>

            <SacrificioModal
                show={showModal}
                onClose={() => setShowModal(false)}
                animales={animales || []}
                lotes={lotes || []}
            />
        </AuthenticatedLayout>
    );
}