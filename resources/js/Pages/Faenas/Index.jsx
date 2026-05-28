import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Drumstick, PlusCircle, Search, Calculator, Scale, Package, Edit, Trash2, Eye } from 'lucide-react';
import FaenaModal from './FaenaModal';
import { ArrowLeft } from 'lucide-react';

export default function FaenasIndex({ auth, faenas, estadisticas, animales, lotes }) {
    const [showModal, setShowModal] = useState(false);
    const [busqueda, setBusqueda] = useState('');

    // ✅ CORRECCIÓN: Usar faenas.data para el array
    const faenasArray = faenas?.data || [];
    const faenasFiltradas = faenasArray.filter(faena =>
        faena.animal_alias?.toLowerCase().includes(busqueda.toLowerCase()) ||
        faena.animal_arete?.toLowerCase().includes(busqueda.toLowerCase()) ||
        faena.tipo_corte?.toLowerCase().includes(busqueda.toLowerCase())
    );

    const handleDelete = (id) => {
        if (confirm("¿Seguro que deseas eliminar este registro de faena?")) {
            router.delete(route('faenas.destroy', id));
        }
    };

    const getTipoCorteTexto = (tipo) => {
        const tipos = {
            'completo': 'Faena Completa',
            'media': 'Media Res',
            'cortes': 'Cortes Específicos',
            'deshuesado': 'Deshuesado'
        };
        return tipos[tipo] || tipo;
    };

    const getBadgeColor = (tipo) => {
        const colores = {
            'completo': 'bg-yellow-100 text-yellow-800',
            'media': 'bg-orange-100 text-orange-800',
            'cortes': 'bg-blue-100 text-blue-800',
            'deshuesado': 'bg-purple-100 text-purple-800'
        };
        return colores[tipo] || 'bg-gray-100 text-gray-800';
    };

    // ✅ Usar estadísticas del controller
    const stats = estadisticas || {
        total_faenas: 0,
        total_carne: 0,
        total_cuero: 0,
        total_grasa: 0,
        total_plumas: 0,
        rendimiento_promedio: 0,
        faenas_este_mes: 0
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800">Módulo de Faenas</h2>}
        >
            <Head title="Faenas" />

            <div className="py-8 px-6 max-w-7xl mx-auto">
                {/* Header */}
                <div className="flex justify-between items-center mb-6">

    {/* Botón volver */}
    <div>
        <a
            href="/animales"
            className="flex items-center text-sm text-green-700 hover:text-green-800 transition"
        >
            <ArrowLeft className="w-4 h-4 mr-1" /> Volver
        </a>
    </div>

    {/* Título a la derecha */}
    <div className="text-right">
        <h1 className="text-2xl font-bold text-gray-800">Registro de Faenas</h1>
        <p className="text-gray-600">Procesamiento y cortes de carne</p>
    </div>

    {/* Botón nueva faena */}
    <button
        onClick={() => setShowModal(true)}
        className="bg-yellow-600 hover:bg-yellow-700 text-white font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
    >
        <PlusCircle className="w-5 h-5" />
        Nueva Faena
    </button>

</div>

                {/* Estadísticas - Usando datos del controller */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div className="bg-white p-4 rounded-lg shadow border">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Total Faenas</p>
                                <p className="text-2xl font-bold text-gray-800">{stats.total_faenas}</p>
                            </div>
                            <Drumstick className="w-8 h-8 text-yellow-500" />
                        </div>
                    </div>
                    <div className="bg-white p-4 rounded-lg shadow border">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Carne (kg)</p>
                                <p className="text-2xl font-bold text-gray-800">{stats.total_carne?.toFixed(1) || '0'}</p>
                                <p className="text-xs text-gray-500">Rend: {stats.rendimiento_promedio?.toFixed(1) || '0'}%</p>
                            </div>
                            <Package className="w-8 h-8 text-red-500" />
                        </div>
                    </div>
                    <div className="bg-white p-4 rounded-lg shadow border">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Cuero (kg)</p>
                                <p className="text-2xl font-bold text-gray-800">{stats.total_cuero?.toFixed(1) || '0'}</p>
                            </div>
                            <Package className="w-8 h-8 text-amber-700" />
                        </div>
                    </div>
                    <div className="bg-white p-4 rounded-lg shadow border">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Grasa (kg)</p>
                                <p className="text-2xl font-bold text-gray-800">{stats.total_grasa?.toFixed(1) || '0'}</p>
                                {stats.total_plumas > 0 && (
                                    <p className="text-xs text-gray-500">Plumas: {stats.total_plumas?.toFixed(1)}kg</p>
                                )}
                            </div>
                            <Package className="w-8 h-8 text-yellow-400" />
                        </div>
                    </div>
                </div>

                {/* Búsqueda y Filtros */}
                <div className="flex flex-col sm:flex-row gap-3 mb-6">
                    <div className="flex items-center bg-white border border-gray-300 rounded-lg px-3 py-2 w-full">
                        <Search className="w-5 h-5 text-gray-500 mr-2" />
                        <input
                            type="text"
                            placeholder="Buscar por animal, arete o tipo de corte..."
                            value={busqueda}
                            onChange={(e) => setBusqueda(e.target.value)}
                            className="w-full outline-none text-gray-700"
                        />
                    </div>
                    <div className="text-sm text-gray-600 flex items-center">
                        Mostrando {faenasFiltradas.length} de {faenasArray.length} faenas
                    </div>
                </div>

                {/* Lista de Faenas */}
                <div className="bg-white rounded-lg shadow overflow-hidden">
                    {faenasFiltradas.length === 0 ? (
                        <div className="text-center py-8">
                            <Drumstick className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                            <p className="text-gray-500 text-lg">
                                {faenasArray.length === 0 
                                    ? "No hay faenas registradas" 
                                    : "No se encontraron faenas con ese criterio de búsqueda"
                                }
                            </p>
                            {faenasArray.length === 0 && (
                                <button
                                    onClick={() => setShowModal(true)}
                                    className="mt-4 bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg"
                                >
                                    Registrar primera faena
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
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo Corte</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Peso Canal</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Carne (kg)</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subproductos</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rendimiento</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {faenasFiltradas.map((faena) => (
                                        <tr key={faena.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {new Date(faena.fecha).toLocaleDateString()}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div>
                                                    <p className="text-sm font-medium text-gray-900">
                                                        {faena.animal_alias || 'Sin alias'}
                                                    </p>
                                                    <p className="text-sm text-gray-500">
                                                        {faena.animal_arete || 'Sin arete'}
                                                    </p>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getBadgeColor(faena.tipo_corte)}`}>
                                                    {getTipoCorteTexto(faena.tipo_corte)}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div className="flex items-center">
                                                    <Scale className="w-4 h-4 mr-1 text-gray-400" />
                                                    {faena.peso_canal} kg
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div className="flex items-center">
                                                    <Package className="w-4 h-4 mr-1 text-red-400" />
                                                    {faena.peso_carne} kg
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex flex-wrap gap-1">
                                                    {faena.peso_cuero > 0 && (
                                                        <span className="bg-amber-100 text-amber-800 px-2 py-1 rounded text-xs">
                                                            Cuero: {faena.peso_cuero}kg
                                                        </span>
                                                    )}
                                                    {faena.peso_grasa > 0 && (
                                                        <span className="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">
                                                            Grasa: {faena.peso_grasa}kg
                                                        </span>
                                                    )}
                                                    {faena.peso_plumas > 0 && (
                                                        <span className="bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs">
                                                            Plumas: {faena.peso_plumas}kg
                                                        </span>
                                                    )}
                                                    {faena.peso_hueso > 0 && (
                                                        <span className="bg-stone-100 text-stone-800 px-2 py-1 rounded text-xs">
                                                            Hueso: {faena.peso_hueso}kg
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <span className={`font-semibold ${
                                                    faena.rendimiento > 60 ? 'text-green-600' : 
                                                    faena.rendimiento > 50 ? 'text-yellow-600' : 'text-red-600'
                                                }`}>
                                                    {faena.rendimiento}%
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div className="flex items-center gap-2">
                                                    <Link
                                                        href={route('faenas.show', faena.id)}
                                                        className="text-blue-600 hover:text-blue-900 p-1 rounded transition"
                                                    >
                                                        <Eye className="w-4 h-4" />
                                                    </Link>
                                                    <Link
                                                        href={route('faenas.edit', faena.id)}
                                                        className="text-green-600 hover:text-green-900 p-1 rounded transition"
                                                    >
                                                        <Edit className="w-4 h-4" />
                                                    </Link>
                                                    <button
                                                        onClick={() => handleDelete(faena.id)}
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
                {faenas.links && faenas.links.length > 3 && (
                    <div className="mt-6 flex justify-center">
                        <nav className="flex items-center space-x-2">
                            {faenas.links.map((link, index) => (
                                <Link
                                    key={index}
                                    href={link.url || '#'}
                                    className={`px-3 py-1 rounded-lg text-sm font-medium ${
                                        link.active
                                            ? 'bg-yellow-600 text-white'
                                            : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                                    } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </nav>
                    </div>
                )}

                {/* Resumen de Producción */}
                {faenasFiltradas.length > 0 && (
                    <div className="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="bg-white p-4 rounded-lg shadow border">
                            <h3 className="text-lg font-semibold text-gray-800 mb-2">Eficiencia General</h3>
                            <p className="text-2xl font-bold text-blue-600">
                                {stats.rendimiento_promedio?.toFixed(1) || '0'}%
                            </p>
                            <p className="text-sm text-gray-600">Rendimiento promedio</p>
                        </div>
                        <div className="bg-white p-4 rounded-lg shadow border">
                            <h3 className="text-lg font-semibold text-gray-800 mb-2">Producción Total</h3>
                            <div className="space-y-1 text-sm">
                                <div className="flex justify-between">
                                    <span>Carne:</span>
                                    <span className="font-semibold">{stats.total_carne?.toFixed(1) || '0'} kg</span>
                                </div>
                                <div className="flex justify-between">
                                    <span>Cuero:</span>
                                    <span className="font-semibold">{stats.total_cuero?.toFixed(1) || '0'} kg</span>
                                </div>
                                <div className="flex justify-between">
                                    <span>Grasa:</span>
                                    <span className="font-semibold">{stats.total_grasa?.toFixed(1) || '0'} kg</span>
                                </div>
                            </div>
                        </div>
                        <div className="bg-white p-4 rounded-lg shadow border">
                            <h3 className="text-lg font-semibold text-gray-800 mb-2">Distribución por Corte</h3>
                            <div className="space-y-1 text-sm">
                                {Object.entries(
                                    faenasArray.reduce((acc, faena) => {
                                        acc[faena.tipo_corte] = (acc[faena.tipo_corte] || 0) + 1;
                                        return acc;
                                    }, {})
                                ).map(([tipo, cantidad]) => (
                                    <div key={tipo} className="flex justify-between">
                                        <span className="capitalize">{getTipoCorteTexto(tipo)}:</span>
                                        <span className="font-semibold">{cantidad}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                )}
            </div>

            <FaenaModal
                show={showModal}
                onClose={() => setShowModal(false)}
                animales={animales || []}
                lotes={lotes || []}
            />
        </AuthenticatedLayout>
    );
}