import { router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function Inventario() {
    const { inventario = [] } = usePage().props;
    const [processingId, setProcessingId] = useState(null);

    // Formulario para agregar alimento
    const {
        data,
        setData,
        post,
        processing,
        reset,
        errors,
    } = useForm({
        nombre: '',
        tipo: '',
        existencias: '',
        unidad: 'kg',
        costo_promedio: '',
    });

    const items = useMemo(() => inventario, [inventario]);

    // Indicador de stock usando solo "existencias"
    const getStatus = (item) => {
        const e = Number(item.existencias ?? 0);

        if (e <= 0) {
            return { label: 'Sin stock', color: 'bg-red-100 text-red-700' };
        } else if (e <= 500) {
            return { label: 'Stock bajo', color: 'bg-red-100 text-red-700' };
        } else if (e <= 1500) {
            return { label: 'Stock medio', color: 'bg-yellow-100 text-yellow-700' };
        } else {
            return { label: 'Stock alto', color: 'bg-emerald-100 text-emerald-700' };
        }
    };

    const handleReabastecer = (item) => {
        const cantidadStr = window.prompt(
            `¿Cuánta cantidad quieres agregar a "${item.nombre}"?`,
            '100'
        );

        if (cantidadStr === null) return; // cancelado

        const cantidad = Number(cantidadStr);
        if (Number.isNaN(cantidad) || cantidad < 0) {
            alert('Cantidad inválida');
            return;
        }

        setProcessingId(item.id);
        router.put(
            route('alimentacion.inventario.reabastecer', item.id),
            { cantidad },
            {
                onFinish: () => setProcessingId(null),
            }
        );
    };

    const handleCreate = (e) => {
        e.preventDefault();
        post(route('alimentacion.inventario.store'), {
            onSuccess: () => reset('nombre', 'tipo', 'existencias', 'unidad', 'costo_promedio'),
        });
    };

    return (
        <div className="space-y-4">
            <h2 className="text-xl font-semibold text-gray-800">Inventario de Alimentos</h2>
            <p className="text-sm text-gray-500">
                Control de stock de concentrados, forrajes y suplementos.
            </p>

            {/* Formulario para agregar alimento */}
            <div className="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
                <h3 className="text-sm font-semibold text-gray-800 mb-3">
                    Agregar alimento al inventario
                </h3>

                <form
                    onSubmit={handleCreate}
                    className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm"
                >
                    <div>
                        <label className="block text-xs font-medium text-gray-600 mb-1">
                            Nombre *
                        </label>
                        <input
                            type="text"
                            className="w-full rounded-md border-gray-300"
                            value={data.nombre}
                            onChange={e => setData('nombre', e.target.value)}
                        />
                        {errors.nombre && (
                            <p className="text-xs text-red-500 mt-1">{errors.nombre}</p>
                        )}
                    </div>

                    <div>
                        <label className="block text-xs font-medium text-gray-600 mb-1">
                            Tipo
                        </label>
                        <input
                            type="text"
                            className="w-full rounded-md border-gray-300"
                            value={data.tipo}
                            onChange={e => setData('tipo', e.target.value)}
                            placeholder="Concentrado, forraje..."
                        />
                        {errors.tipo && (
                            <p className="text-xs text-red-500 mt-1">{errors.tipo}</p>
                        )}
                    </div>

                    <div>
                        <label className="block text-xs font-medium text-gray-600 mb-1">
                            Existencias *
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            className="w-full rounded-md border-gray-300"
                            value={data.existencias}
                            onChange={e => setData('existencias', e.target.value)}
                        />
                        {errors.existencias && (
                            <p className="text-xs text-red-500 mt-1">{errors.existencias}</p>
                        )}
                    </div>

                    <div>
                        <label className="block text-xs font-medium text-gray-600 mb-1">
                            Unidad
                        </label>
                        <input
                            type="text"
                            className="w-full rounded-md border-gray-300"
                            value={data.unidad}
                            onChange={e => setData('unidad', e.target.value)}
                            placeholder="kg, pacas..."
                        />
                        {errors.unidad && (
                            <p className="text-xs text-red-500 mt-1">{errors.unidad}</p>
                        )}
                    </div>

                    <div>
                        <label className="block text-xs font-medium text-gray-600 mb-1">
                            Costo promedio
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            className="w-full rounded-md border-gray-300"
                            value={data.costo_promedio}
                            onChange={e => setData('costo_promedio', e.target.value)}
                        />
                        {errors.costo_promedio && (
                            <p className="text-xs text-red-500 mt-1">
                                {errors.costo_promedio}
                            </p>
                        )}
                    </div>

                    <div className="md:col-span-3 flex justify-end pt-2">
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-4 py-2 text-xs rounded-md bg-green-600 text-white hover:bg-green-700 disabled:opacity-50"
                        >
                            {processing ? 'Guardando...' : 'Agregar alimento'}
                        </button>
                    </div>
                </form>
            </div>

            {/* Lista de inventario */}
            <div className="space-y-3">
                {items.map((item) => {
                    const status = getStatus(item);

                    return (
                        <div
                            key={item.id}
                            className="bg-white border border-gray-100 rounded-xl shadow-sm px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                        >
                            <div>
                                <h3 className="text-sm font-semibold text-gray-900">
                                    {item.nombre}
                                </h3>
                                <p className="text-xs text-gray-500">
                                    {item.tipo && <span>{item.tipo} · </span>}
                                    {item.unidad
                                        ? `${item.existencias} ${item.unidad}`
                                        : item.existencias}
                                </p>
                                {item.costo_promedio != null && (
                                    <p className="text-xs text-gray-400">
                                        Costo promedio: ${item.costo_promedio}
                                    </p>
                                )}
                            </div>

                            <div className="flex items-center gap-3">
                                <span className={`text-xs px-3 py-1 rounded-full ${status.color}`}>
                                    {status.label}
                                </span>

                                <button
                                    type="button"
                                    onClick={() => handleReabastecer(item)}
                                    disabled={processingId === item.id}
                                    className="text-xs px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                                >
                                    {processingId === item.id ? 'Guardando...' : 'Reabastecer'}
                                </button>
                            </div>
                        </div>
                    );
                })}

                {items.length === 0 && (
                    <div className="text-sm text-gray-500">
                        Aún no hay alimentos registrados en el inventario.
                    </div>
                )}
            </div>
        </div>
    );
}
