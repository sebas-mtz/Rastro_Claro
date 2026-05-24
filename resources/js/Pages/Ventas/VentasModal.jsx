import React, { useState, useEffect } from 'react';
import { X, DollarSign, Package, Scale, User, Calendar, ShoppingCart, Database } from 'lucide-react';
import { useForm, router } from '@inertiajs/react';

export default function VentaModal({
    show,
    onClose,
    animales = [],
    lotes = [],
    inventario_producciones = {},
    inventario_subproductos = {},
    compradores = [],
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        tipo_venta: 'animal',
        vendible_id: '',
        comprador_id: '',
        fecha_venta: new Date().toISOString().split('T')[0],
        producto: '',
        cantidad: '',
        unidad: '',
        precio_unitario: '',
        precio_total: '',
        metodo_pago: 'efectivo',
        estado_venta: 'completada',
        estado_pago: 'completado',
        condiciones_entrega: '',
        fecha_entrega: '',
        observaciones: '',
    });

    const [productosDisponibles, setProductosDisponibles] = useState([]);
    const [inventarioDisponible, setInventarioDisponible] = useState(0);

    // Normalizamos inventarios (por si vienen null/undefined)
    const inventarioProducciones = inventario_producciones || {};
    const inventarioSubproductos = inventario_subproductos || {};

    const tiposVenta = [
        { value: 'animal', label: 'Venta de Animal', icon: '🐄' },
        { value: 'lote', label: 'Venta de Lote', icon: '🏷️' },
        { value: 'produccion', label: 'Venta de Producción', icon: '🥛' },
        { value: 'subproducto_faena', label: 'Venta de Subproducto', icon: '🍖' },
    ];

    const metodosPago = [
        { value: 'efectivo', label: 'Efectivo' },
        { value: 'transferencia', label: 'Transferencia' },
        { value: 'tarjeta', label: 'Tarjeta' },
        { value: 'cheque', label: 'Cheque' },
    ];

    const calcularInventarioProduccion = (tipo) => {
        // tipos: 'leche', 'lana', 'huevo'
        return Number(inventarioProducciones[tipo] ?? 0);
    };

    const calcularInventarioSubproducto = (subproducto) => {
        // subproductos: 'carne', 'cuero', 'grasa', 'hueso', 'plumas', 'visceras'
        return Number(inventarioSubproductos[subproducto] ?? 0);
    };

    // Cuando cambia el tipo de venta, armamos la lista de productos
    useEffect(() => {
        let disponibles = [];
        let unidadDefault = '';

        switch (data.tipo_venta) {
            case 'animal':
                // Ya vienen filtrados desde el backend (solo disponibles)
                disponibles = animales;
                unidadDefault = 'unidad';
                break;

            case 'lote':
                // Lotes vienen con animales_count ya filtrado desde el service
                disponibles = lotes;
                unidadDefault = 'lote';
                break;

            case 'produccion':
                disponibles = [
                    {
                        id: 'leche',
                        tipo: 'leche',
                        nombre: 'Leche',
                        unidad: 'litros',
                        inventario: calcularInventarioProduccion('leche'),
                    },
                    {
                        id: 'lana',
                        tipo: 'lana',
                        nombre: 'Lana',
                        unidad: 'kg',
                        inventario: calcularInventarioProduccion('lana'),
                    },
                    {
                        id: 'huevo',
                        tipo: 'huevo',
                        nombre: 'Huevos',
                        unidad: 'unidades',
                        inventario: calcularInventarioProduccion('huevo'),
                    },
                ];
                unidadDefault = '';
                break;

            case 'subproducto_faena':
                disponibles = [
                    {
                        id: 'carne',
                        nombre: 'Carne',
                        unidad: 'kg',
                        inventario: calcularInventarioSubproducto('carne'),
                    },
                    {
                        id: 'cuero',
                        nombre: 'Cuero',
                        unidad: 'kg',
                        inventario: calcularInventarioSubproducto('cuero'),
                    },
                    {
                        id: 'grasa',
                        nombre: 'Grasa',
                        unidad: 'kg',
                        inventario: calcularInventarioSubproducto('grasa'),
                    },
                    {
                        id: 'hueso',
                        nombre: 'Hueso',
                        unidad: 'kg',
                        inventario: calcularInventarioSubproducto('hueso'),
                    },
                    {
                        id: 'plumas',
                        nombre: 'Plumas',
                        unidad: 'kg',
                        inventario: calcularInventarioSubproducto('plumas'),
                    },
                    {
                        id: 'visceras',
                        nombre: 'Vísceras',
                        unidad: 'kg',
                        inventario: calcularInventarioSubproducto('visceras'),
                    },
                ];
                unidadDefault = 'kg';
                break;
        }

        setProductosDisponibles(disponibles);
        setData('unidad', unidadDefault);
        setData('vendible_id', '');
        setData('producto', '');
        setData('cantidad', '');
        setData('precio_unitario', '');
        setData('precio_total', '');
        setInventarioDisponible(0);
    }, [data.tipo_venta, animales, lotes, inventarioProducciones, inventarioSubproductos]);

    // Cuando seleccionas un producto (animal, lote, tipo de producción, subproducto)
    useEffect(() => {
        if (!data.vendible_id) return;

        const producto = productosDisponibles.find((p) => p.id == data.vendible_id);
        if (!producto) return;

        let productoNombre = '';
        let cantidadDefault = '';
        let unidad = data.unidad;
        let inventario = 0;

        switch (data.tipo_venta) {
            case 'animal':
                productoNombre = `${producto.especie} - ${producto.alias}`;
                cantidadDefault = 1;
                inventario = 1;
                unidad = 'unidad';
                break;

            case 'lote':
                productoNombre = `Lote ${producto.nombre}`;
                cantidadDefault = producto.animales_count;
                inventario = producto.animales_count;
                unidad = 'lote';
                break;

            case 'produccion':
                productoNombre = producto.nombre;
                unidad = producto.unidad;
                inventario = Number(producto.inventario || 0);
                cantidadDefault = '';
                break;

            case 'subproducto_faena':
                productoNombre = producto.nombre;
                unidad = 'kg';
                inventario = Number(producto.inventario || 0);
                cantidadDefault = '';
                break;
        }

        setData('producto', productoNombre);
        setData('unidad', unidad);
        setData('cantidad', cantidadDefault);
        setInventarioDisponible(inventario);
    }, [data.vendible_id, productosDisponibles, data.tipo_venta]);

    // Calcula precio total
    useEffect(() => {
        const cantidad = Number(data.cantidad);
        const unitario = Number(data.precio_unitario);

        if (!isNaN(cantidad) && !isNaN(unitario) && cantidad > 0 && unitario > 0) {
            setData('precio_total', (cantidad * unitario).toFixed(2));
        } else {
            setData('precio_total', '');
        }
    }, [data.cantidad, data.precio_unitario]);

    // Evitar que se venda más de lo disponible en producción/subproducto
    useEffect(() => {
        if (data.tipo_venta !== 'produccion' && data.tipo_venta !== 'subproducto_faena') return;

        const cantidad = Number(data.cantidad);
        const inventario = Number(inventarioDisponible) || 0;

        if (!isNaN(cantidad) && cantidad > inventario && inventario > 0) {
            setData('cantidad', inventario);
        }
    }, [data.cantidad, data.tipo_venta, inventarioDisponible]);

    const handleSubmit = (e) => {
        e.preventDefault();

        if (data.tipo_venta !== 'subproducto_faena' && !data.vendible_id) {
            alert('Por favor seleccione un producto para vender');
            return;
        }

        if (!data.comprador_id) {
            alert('Por favor seleccione un comprador');
            return;
        }

        const cantidad = Number(data.cantidad);
        if (isNaN(cantidad) || cantidad <= 0) {
            alert('Por favor ingrese una cantidad válida');
            return;
        }

        if (
            (data.tipo_venta === 'produccion' || data.tipo_venta === 'subproducto_faena') &&
            cantidad > Number(inventarioDisponible || 0)
        ) {
            alert(
                `La cantidad no puede exceder el inventario disponible (${inventarioDisponible} ${data.unidad})`
            );
            return;
        }

        post(route('ventas.store'), {
            onSuccess: () => {
                onClose();
                reset();
                router.reload();
            },
            onError: (errors) => {
                console.log('Errores:', errors);
            },
        });
    };

    const getProductoLabel = (producto) => {
        switch (data.tipo_venta) {
            case 'animal':
                return `${producto.alias} - ${producto.especie} (${producto.peso} kg)`;

            case 'lote':
                return `${producto.nombre} - ${producto.especie} (${producto.animales_count} animales)`;

            case 'produccion':
                return `${producto.nombre} (${Number(producto.inventario || 0).toFixed(2)} ${
                    producto.unidad
                } disponibles)`;

            case 'subproducto_faena':
                return `${producto.nombre} (${Number(producto.inventario || 0).toFixed(2)} kg disponibles)`;

            default:
                return producto.nombre || producto.alias;
        }
    };

    const isCantidadEditable =
        data.tipo_venta === 'produccion' || data.tipo_venta === 'subproducto_faena';

    if (!show) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div className="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div className="flex justify-between items-center border-b p-6">
                    <div className="flex items-center gap-3">
                        <ShoppingCart className="w-6 h-6 text-green-600" />
                        <h2 className="text-xl font-bold text-gray-800">Registrar Nueva Venta</h2>
                    </div>
                    <button
                        onClick={onClose}
                        className="text-gray-400 hover:text-gray-600"
                        disabled={processing}
                    >
                        <X className="w-6 h-6" />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="p-6 space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* Tipo de venta */}
                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 mb-3">
                                Tipo de Venta
                            </label>
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                                {tiposVenta.map((tipo) => (
                                    <label
                                        key={tipo.value}
                                        className={`flex flex-col items-center p-4 border rounded-lg cursor-pointer transition-colors ${
                                            data.tipo_venta === tipo.value
                                                ? 'border-2 border-green-500 bg-green-50'
                                                : 'border-gray-300 hover:bg-gray-50'
                                        }`}
                                    >
                                        <span className="text-2xl mb-2">{tipo.icon}</span>
                                        <input
                                            type="radio"
                                            name="tipo_venta"
                                            value={tipo.value}
                                            checked={data.tipo_venta === tipo.value}
                                            onChange={(e) => setData('tipo_venta', e.target.value)}
                                            className="hidden"
                                        />
                                        <span className="text-sm font-medium text-center text-gray-700">
                                            {tipo.label}
                                        </span>
                                    </label>
                                ))}
                            </div>
                            {errors.tipo_venta && (
                                <p className="text-red-500 text-sm mt-1">{errors.tipo_venta}</p>
                            )}
                        </div>

                        {/* Producto / Animal / Lote */}
                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                <Package className="w-4 h-4 inline mr-2" />
                                {data.tipo_venta === 'animal'
                                    ? 'Seleccionar Animal'
                                    : data.tipo_venta === 'lote'
                                    ? 'Seleccionar Lote'
                                    : data.tipo_venta === 'produccion'
                                    ? 'Seleccionar Producto'
                                    : 'Seleccionar Subproducto'}
                            </label>
                            <select
                                value={data.vendible_id}
                                onChange={(e) => setData('vendible_id', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                disabled={processing}
                                required={data.tipo_venta !== 'subproducto_faena'}
                            >
                                <option value="">Seleccionar...</option>
                                {productosDisponibles.map((producto) => (
                                    <option key={producto.id} value={producto.id}>
                                        {getProductoLabel(producto)}
                                    </option>
                                ))}
                            </select>
                            {errors.vendible_id && (
                                <p className="text-red-500 text-sm mt-1">{errors.vendible_id}</p>
                            )}
                        </div>

                        {/* Comprador */}
                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                <User className="w-4 h-4 inline mr-2" />
                                Comprador *
                            </label>
                            <select
                                value={data.comprador_id}
                                onChange={(e) => setData('comprador_id', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                disabled={processing}
                                required
                            >
                                <option value="">Seleccionar comprador...</option>
                                {compradores.map((comprador) => (
                                    <option key={comprador.id} value={comprador.id}>
                                        {comprador.nombre}
                                    </option>
                                ))}
                            </select>
                            {errors.comprador_id && (
                                <p className="text-red-500 text-sm mt-1">{errors.comprador_id}</p>
                            )}
                        </div>

                        {/* Fecha venta */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                <Calendar className="w-4 h-4 inline mr-2" />
                                Fecha de Venta
                            </label>
                            <input
                                type="date"
                                value={data.fecha_venta}
                                onChange={(e) => setData('fecha_venta', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                disabled={processing}
                                required
                            />
                            {errors.fecha_venta && (
                                <p className="text-red-500 text-sm mt-1">{errors.fecha_venta}</p>
                            )}
                        </div>

                        {/* Producto nombre */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Producto
                            </label>
                            <input
                                type="text"
                                value={data.producto}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                disabled={true}
                                required
                            />
                            {errors.producto && (
                                <p className="text-red-500 text-sm mt-1">{errors.producto}</p>
                            )}
                        </div>

                        {/* Cantidad */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                <Scale className="w-4 h-4 inline mr-2" />
                                Cantidad a Vender
                                {inventarioDisponible > 0 && (
                                    <span className="text-xs text-gray-500 ml-2">
                                        (Disponible: {inventarioDisponible} {data.unidad})
                                    </span>
                                )}
                            </label>
                            <input
                                type="number"
                                value={data.cantidad}
                                onChange={(e) => setData('cantidad', e.target.value)}
                                className={`w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent ${
                                    !isCantidadEditable ? 'bg-gray-50' : ''
                                }`}
                                step={data.tipo_venta === 'subproducto_faena' ? '0.01' : '1'}
                                min="0.01"
                                max={isCantidadEditable ? inventarioDisponible : undefined}
                                disabled={processing || !isCantidadEditable}
                                required
                            />
                            {errors.cantidad && (
                                <p className="text-red-500 text-sm mt-1">{errors.cantidad}</p>
                            )}
                        </div>

                        {/* Unidad */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Unidad de Medida
                            </label>
                            <input
                                type="text"
                                value={data.unidad}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                disabled={true}
                                required
                            />
                            {errors.unidad && (
                                <p className="text-red-500 text-sm mt-1">{errors.unidad}</p>
                            )}
                        </div>

                        {/* Precio unitario */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                <DollarSign className="w-4 h-4 inline mr-2" />
                                Precio Unitario ($)
                            </label>
                            <input
                                type="number"
                                value={data.precio_unitario}
                                onChange={(e) => setData('precio_unitario', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                placeholder="0.00"
                                step="0.01"
                                min="0.01"
                                disabled={processing}
                                required
                            />
                            {errors.precio_unitario && (
                                <p className="text-red-500 text-sm mt-1">{errors.precio_unitario}</p>
                            )}
                        </div>

                        {/* Precio total */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Precio Total ($)
                            </label>
                            <input
                                type="number"
                                value={data.precio_total}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                placeholder="0.00"
                                step="0.01"
                                min="0.01"
                                disabled={true}
                                required
                            />
                            {errors.precio_total && (
                                <p className="text-red-500 text-sm mt-1">{errors.precio_total}</p>
                            )}
                        </div>

                        {/* Método de pago */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Método de Pago
                            </label>
                            <select
                                value={data.metodo_pago}
                                onChange={(e) => setData('metodo_pago', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                disabled={processing}
                                required
                            >
                                {metodosPago.map((metodo) => (
                                    <option key={metodo.value} value={metodo.value}>
                                        {metodo.label}
                                    </option>
                                ))}
                            </select>
                            {errors.metodo_pago && (
                                <p className="text-red-500 text-sm mt-1">{errors.metodo_pago}</p>
                            )}
                        </div>

                        {/* Fecha entrega opcional */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Fecha de Entrega (opcional)
                            </label>
                            <input
                                type="date"
                                value={data.fecha_entrega}
                                onChange={(e) => setData('fecha_entrega', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                disabled={processing}
                            />
                            {errors.fecha_entrega && (
                                <p className="text-red-500 text-sm mt-1">{errors.fecha_entrega}</p>
                            )}
                        </div>

                        {/* Condiciones de entrega */}
                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Condiciones de Entrega
                            </label>
                            <textarea
                                value={data.condiciones_entrega}
                                onChange={(e) => setData('condiciones_entrega', e.target.value)}
                                rows="2"
                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                placeholder="Detalles de entrega, transporte, etc."
                                disabled={processing}
                            />
                            {errors.condiciones_entrega && (
                                <p className="text-red-500 text-sm mt-1">
                                    {errors.condiciones_entrega}
                                </p>
                            )}
                        </div>

                        {/* Observaciones */}
                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Observaciones
                            </label>
                            <textarea
                                value={data.observaciones}
                                onChange={(e) => setData('observaciones', e.target.value)}
                                rows="3"
                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                placeholder="Notas adicionales sobre la venta..."
                                disabled={processing}
                            />
                            {errors.observaciones && (
                                <p className="text-red-500 text-sm mt-1">{errors.observaciones}</p>
                            )}
                        </div>
                    </div>

                    {/* Resumen */}
                    <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                        <h3 className="font-semibold text-green-800 mb-2">Resumen de Venta</h3>
                        <div className="grid grid-cols-2 gap-2 text-sm">
                            <div>Producto:</div>
                            <div className="font-medium">{data.producto || '-'}</div>

                            <div>Cantidad:</div>
                            <div className="font-medium">
                                {data.cantidad || '0'} {data.unidad}
                            </div>

                            <div>Precio Unitario:</div>
                            <div className="font-medium">
                                ${data.precio_unitario || '0.00'}
                            </div>

                            <div className="text-lg font-bold text-green-600">Total:</div>
                            <div className="text-lg font-bold text-green-600">
                                {Number(data.precio_total || 0).toLocaleString('es-ES', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2,
                                })}
                            </div>
                        </div>

                        {isCantidadEditable && inventarioDisponible > 0 && (
                            <div className="mt-2 text-xs text-blue-600">
                                <Database className="w-3 h-3 inline mr-1" />
                                {(() => {
                                    const inv = Number(inventarioDisponible) || 0;
                                    const cant = Number(data.cantidad) || 0;
                                    const restante = Math.max(inv - cant, 0);
                                    return (
                                        <>
                                            Después de esta venta quedarán:{' '}
                                            {restante.toFixed(2)} {data.unidad} en inventario
                                        </>
                                    );
                                })()}
                            </div>
                        )}
                    </div>

                    <div className="flex justify-end gap-3 pt-6 border-t">
                        <button
                            type="button"
                            onClick={onClose}
                            disabled={processing}
                            className="px-6 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <DollarSign className="w-4 h-4" />
                            {processing ? 'Registrando...' : 'Registrar Venta'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}