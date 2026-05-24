import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { DollarSign, PlusCircle, Search, FileText, Calendar, Scale, User, Edit, Trash2, Eye, Package, TrendingUp } from 'lucide-react';
import VentaModal from './VentasModal';
import { ArrowLeft } from 'lucide-react';

export default function VentasIndex({ auth, ventas, estadisticas, animales, lotes, producciones,inventario_producciones,
    inventario_subproductos, faenas, compradores }) {
    const [showModal, setShowModal] = useState(false);
    const [busqueda, setBusqueda] = useState('');

    // ✅ CORRECCIÓN: Usar ventas.data para el array de ventas
    const ventasArray = ventas?.data || [];
    const ventasFiltradas = ventasArray.filter(venta =>
        venta.comprador_nombre?.toLowerCase().includes(busqueda.toLowerCase()) ||
        venta.producto?.toLowerCase().includes(busqueda.toLowerCase()) ||
        venta.tipo_venta?.toLowerCase().includes(busqueda.toLowerCase()) ||
        venta.numero_factura?.toLowerCase().includes(busqueda.toLowerCase())
    );

    const calcularTotalVentasFiltradas = () => {
        return ventasFiltradas.reduce((total, venta) => total + (parseFloat(venta.precio_total) || 0), 0);
    };

    const handleDelete = (id) => {
        if (confirm("¿Seguro que deseas eliminar esta venta?")) {
            router.delete(route('ventas.destroy', id));
        }
    };

    const getTipoVentaTexto = (tipo) => {
        const tipos = {
            'animal': 'Animal Individual',
            'lote': 'Lote Completo',
            'produccion': 'Producción',
            'subproducto_faena': 'Subproducto'
        };
        return tipos[tipo] || tipo;
    };

    const getTipoVentaIcon = (tipo) => {
        const iconos = {
            'animal': '🐄',
            'lote': '🏷️',
            'produccion': '🥛',
            'subproducto_faena': '🍖'
        };
        return iconos[tipo] || '📦';
    };

    const getEstadoVentaColor = (estado) => {
        const colores = {
            'pendiente': 'bg-yellow-100 text-yellow-800',
            'completada': 'bg-green-100 text-green-800',
            'cancelada': 'bg-red-100 text-red-800'
        };
        return colores[estado] || 'bg-gray-100 text-gray-800';
    };

    const getEstadoPagoColor = (estado) => {
        const colores = {
            'pendiente': 'bg-orange-100 text-orange-800',
            'parcial': 'bg-blue-100 text-blue-800',
            'completado': 'bg-green-100 text-green-800'
        };
        return colores[estado] || 'bg-gray-100 text-gray-800';
    };

    const getMetodoPagoTexto = (metodo) => {
        const metodos = {
            'efectivo': 'Efectivo',
            'transferencia': 'Transferencia',
            'tarjeta': 'Tarjeta',
            'cheque': 'Cheque'
        };
        return metodos[metodo] || metodo;
    };

    // ✅ Estadísticas seguras
    const stats = estadisticas || {
        total_ventas: 0,
        ingreso_total: 0,
        ingreso_pendiente: 0,
        ventas_pendientes: 0,
        ingreso_mensual: 0,
        por_tipo_venta: {
            animal: 0,
            lote: 0,
            produccion: 0,
            subproducto_faena: 0
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800">Módulo de Ventas</h2>}
        >
            <Head title="Ventas" />
 
            <div className="py-8 px-6 max-w-7xl mx-auto">
                {/* Header */}
                <div className="flex justify-between items-center mb-6">

    {/* Botón Volver */}
    <a
        href="/animales"
        className="flex items-center text-sm text-green-700 hover:text-green-800 transition"
    >
        <ArrowLeft className="w-4 h-4 mr-1" /> Volver
    </a>

            {/* Título */}
       <div className="text-right">
        <h1 className="text-2xl font-bold text-gray-800">Registro de Ventas</h1>
        <p className="text-gray-600">Gestión integral de ventas - Animales, Lotes, Producciones y Subproductos</p>
              </div>

             {/* Botón Nueva Venta */}
                  <button
        onClick={() => setShowModal(true)}
        className="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
             >
        <PlusCircle className="w-5 h-5" />
        Nueva Venta
              </button>

                </div>


                {/* Estadísticas Actualizadas */}
                <div className="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                    <div className="bg-white p-4 rounded-lg shadow border">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Ingreso Total</p>
                                <p className="text-2xl font-bold text-gray-800">
                                ${parseFloat(stats.ingreso_total || 0).toLocaleString()}
                                </p>
                                <p className="text-xs text-green-600">
                                    {stats.total_ventas} ventas
                                </p>
                            </div>
                            <DollarSign className="w-8 h-8 text-green-500" />
                        </div>
                    </div>
                    
                    <div className="bg-white p-4 rounded-lg shadow border">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Ingreso Mensual</p>
                                <p className="text-2xl font-bold text-gray-800">
                                ${parseFloat(stats.ingreso_mensual || 0).toLocaleString()}
                                </p>
                            </div>
                            <TrendingUp className="w-8 h-8 text-blue-500" />
                        </div>
                    </div>
                    
                    <div className="bg-white p-4 rounded-lg shadow border">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-gray-600">Ventas Pendientes</p>
                                <p className="text-2xl font-bold text-gray-800">
                                    {stats.ventas_pendientes || '0'}
                                </p>
                            </div>
                            <FileText className="w-8 h-8 text-yellow-500" />
                        </div>
                    </div>

                    {/* Distribución por Tipo */}
                    <div className="bg-white p-4 rounded-lg shadow border md:col-span-2">
                        <div className="flex items-center justify-between mb-2">
                            <p className="text-sm font-medium text-gray-700">Distribución por Tipo</p>
                            <Package className="w-6 h-6 text-gray-400" />
                        </div>
                        <div className="grid grid-cols-2 gap-2 text-xs">
                            <div className="flex justify-between">
                                <span>🐄 Animales:</span>
                                <span className="font-semibold">{stats.por_tipo_venta?.animal || 0}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>🏷️ Lotes:</span>
                                <span className="font-semibold">{stats.por_tipo_venta?.lote || 0}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>🥛 Producción:</span>
                                <span className="font-semibold">{stats.por_tipo_venta?.produccion || 0}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>🍖 Subproductos:</span>
                                <span className="font-semibold">{stats.por_tipo_venta?.subproducto_faena || 0}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Búsqueda y Resumen */}
                <div className="flex flex-col sm:flex-row gap-3 mb-6">
                    <div className="flex items-center bg-white border border-gray-300 rounded-lg px-3 py-2 w-full">
                        <Search className="w-5 h-5 text-gray-500 mr-2" />
                        <input
                            type="text"
                            placeholder="Buscar por comprador, producto, tipo de venta o número de factura..."
                            value={busqueda}
                            onChange={(e) => setBusqueda(e.target.value)}
                            className="w-full outline-none text-gray-700"
                        />
                    </div>
                    <div className="flex items-center gap-4 text-sm text-gray-600">
                        <span>Mostrando {ventasFiltradas.length} de {ventasArray.length} ventas</span>
                        {ventasFiltradas.length > 0 && (
                            <span className="font-semibold text-green-600">
                                Total filtrado: ${calcularTotalVentasFiltradas().toLocaleString()}
                            </span>
                        )}
                    </div>
                </div>

                {/* Lista de Ventas Actualizada */}
                <div className="bg-white rounded-lg shadow overflow-hidden">
                    {ventasFiltradas.length === 0 ? (
                        <div className="text-center py-8">
                            <DollarSign className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                            <p className="text-gray-500 text-lg">
                                {ventasArray.length === 0 
                                    ? "No hay ventas registradas" 
                                    : "No se encontraron ventas con ese criterio de búsqueda"
                                }
                            </p>
                            {ventasArray.length === 0 && (
                                <button
                                    onClick={() => setShowModal(true)}
                                    className="mt-4 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg"
                                >
                                    Registrar primera venta
                                </button>
                            )}
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Factura</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Comprador</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Precio Total</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estados</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {ventasFiltradas.map((venta) => (
                                        <tr key={venta.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {new Date(venta.fecha_venta).toLocaleDateString()}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">
                                                {venta.numero_factura}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center">
                                                    <User className="w-4 h-4 mr-2 text-gray-400" />
                                                    <div>
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {venta.comprador_nombre}
                                                        </div>
                                                        <div className="text-xs text-gray-500">
                                                            {venta.vendedor_nombre}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex items-center gap-2">
                                                    <span className="text-lg">{getTipoVentaIcon(venta.tipo_venta)}</span>
                                                    <div>
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {getTipoVentaTexto(venta.tipo_venta)}
                                                        </div>
                                                        <div className="text-xs text-gray-500">
                                                            {getMetodoPagoTexto(venta.metodo_pago)}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="text-sm text-gray-900 max-w-xs truncate">
                                                    {venta.producto}
                                                </div>
                                                {venta.vendible_info && (
                                                    <div className="text-xs text-gray-500">
                                                        {venta.vendible_info}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div className="flex items-center">
                                                    <Scale className="w-4 h-4 mr-1 text-gray-400" />
                                                    {venta.cantidad} {venta.unidad}
                                                </div>
                                                <div className="text-xs text-gray-500">
                                                    ${venta.precio_unitario}/unidad
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-lg font-semibold text-green-600">
                                                    ${parseFloat(venta.precio_total || 0).toLocaleString()}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="space-y-1">
                                                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getEstadoVentaColor(venta.estado_venta)}`}>
                                                        {venta.estado_venta}
                                                    </span>
                                                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getEstadoPagoColor(venta.estado_pago)}`}>
                                                        {venta.estado_pago}
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div className="flex items-center gap-2">
                                                    <Link
                                                        href={route('ventas.show', venta.id)}
                                                        className="text-blue-600 hover:text-blue-900 p-1 rounded transition"
                                                        title="Ver detalles"
                                                    >
                                                        <Eye className="w-4 h-4" />
                                                    </Link>
                                                    <Link
                                                        href={route('ventas.edit', venta.id)}
                                                        className="text-green-600 hover:text-green-900 p-1 rounded transition"
                                                        title="Editar venta"
                                                    >
                                                        <Edit className="w-4 h-4" />
                                                    </Link>
                                                    <button
                                                        onClick={() => handleDelete(venta.id)}
                                                        className="text-red-600 hover:text-red-900 p-1 rounded transition"
                                                        title="Eliminar venta"
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
                {ventas.links && ventas.links.length > 3 && (
                    <div className="mt-6 flex justify-center">
                        <nav className="flex items-center space-x-2">
                            {ventas.links.map((link, index) => (
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

            {/* ✅ Pasar todas las props necesarias al modal */}
            <VentaModal
                show={showModal}
                onClose={() => setShowModal(false)}
                animales={animales || []}
                lotes={lotes || []}
                producciones={producciones || []}
                inventario_producciones={inventario_producciones}
                inventario_subproductos={inventario_subproductos}
                faenas={faenas || []}
                compradores={compradores || []}
            />
        </AuthenticatedLayout>
    );
}