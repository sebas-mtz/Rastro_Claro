import { router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

const DRAFT_KEY = 'inventario_form_draft_v2';

export default function Inventario() {
    const { inventario = [], flash = {} } = usePage().props;

    const [processingId, setProcessingId]               = useState(null);
    const [editingId, setEditingId]                     = useState(null);
    const [mostrarDesactivados, setMostrarDesactivados] = useState(false);
    const [usarValoresNutrimentales, setUsarValoresNutrimentales] = useState(false);
    const [draftLoaded, setDraftLoaded]                 = useState(false);

    const { data, setData, post, put,patch, processing, reset, errors } = useForm({
        nombre:             '',
        tipo:               '',
        marca:              '',
        existencias:        '',
        unidad:             'kg',
        costo_total:        '',
        MS:                 '',
        PB:                 '',
        EM:                 '',
        FDN:                '',
        auto_rellenar:      false,
        cantidad_rellenado: '',
    });

    const preventWheelChange = (e) => e.target.blur();

    // ─── Filtro activos / desactivados ───────────────────────────────────────
    const items = useMemo(
        () => inventario.filter(i => mostrarDesactivados || i.activo),
        [inventario, mostrarDesactivados]
    );

    const isEditing = Boolean(editingId);

    // ─── Estilos condicionales edición ───────────────────────────────────────
    const inputClass = isEditing
        ? 'w-full rounded-md border-blue-300 bg-blue-50 focus:border-blue-500 focus:ring-blue-300'
        : 'w-full rounded-md border-gray-300 focus:border-blue-300 focus:ring-blue-200';

    const panelClass = isEditing
        ? 'bg-blue-50 border border-blue-200 rounded-xl shadow-sm p-4'
        : 'bg-white border border-blue-100 rounded-xl shadow-sm p-4';

    // ─── Badge de stock ───────────────────────────────────────────────────────
    const getStatus = (item) => {
        if (!item.activo)
            return { label: 'Desactivado', color: 'bg-gray-100 text-gray-600 border-gray-200' };

        const e = Number(item.existencias ?? 0);

        if (e <= 0)    return { label: 'Sin stock',   color: 'bg-red-100 text-red-700 border-red-200' };
        if (e <= 500)  return { label: 'Stock bajo',  color: 'bg-red-100 text-red-700 border-red-200' };
        if (e <= 1500) return { label: 'Stock medio', color: 'bg-yellow-100 text-yellow-700 border-yellow-200' };

        return { label: 'Stock alto', color: 'bg-blue-50 text-blue-700 border-blue-200' };
    };

    // ─── Costo por kg calculado en tiempo real ────────────────────────────────
    const costoPorKg = useMemo(() => {
        const existencias = parseFloat(data.existencias || 0);
        const costoTotal  = parseFloat(data.costo_total || 0);
        if (!existencias || existencias <= 0 || !costoTotal) return null;
        return (costoTotal / existencias).toFixed(2);
    }, [data.existencias, data.costo_total]);

    // ─── Restaurar borrador ───────────────────────────────────────────────────
    useEffect(() => {
        try {
            const saved = localStorage.getItem(DRAFT_KEY);
            if (!saved) { setDraftLoaded(true); return; }

            const parsed = JSON.parse(saved);

            if (parsed.data) {
                setData({
                    nombre:             parsed.data.nombre             ?? '',
                    tipo:               parsed.data.tipo               ?? '',
                    marca:              parsed.data.marca              ?? '',
                    existencias:        parsed.data.existencias        ?? '',
                    unidad:             parsed.data.unidad             ?? 'kg',
                    costo_total:        parsed.data.costo_total        ?? '',
                    MS:                 parsed.data.MS                 ?? '',
                    PB:                 parsed.data.PB                 ?? '',
                    EM:                 parsed.data.EM                 ?? '',
                    FDN:                parsed.data.FDN                ?? '',
                    auto_rellenar:      parsed.data.auto_rellenar      ?? false,
                    cantidad_rellenado: parsed.data.cantidad_rellenado ?? '',
                });
            }

            setEditingId(parsed.editingId ?? null);
            setUsarValoresNutrimentales(parsed.usarValoresNutrimentales ?? false);
        } catch (e) {
            console.error('No se pudo restaurar el borrador:', e);
        } finally {
            setDraftLoaded(true);
        }
    }, []);

    // ─── Guardar borrador ─────────────────────────────────────────────────────
    useEffect(() => {
        if (!draftLoaded) return;
        try {
            localStorage.setItem(
                DRAFT_KEY,
                JSON.stringify({ data, editingId, usarValoresNutrimentales })
            );
        } catch (e) {
            console.error('No se pudo guardar el borrador:', e);
        }
    }, [data, editingId, usarValoresNutrimentales, draftLoaded]);

    // ─── Reabastecer ──────────────────────────────────────────────────────────
    const handleReabastecer = (item) => {
        const cantidadStr = window.prompt(`¿Cuánta cantidad quieres agregar a "${item.nombre}"?`, '100');
        if (cantidadStr === null) return;

        const cantidad = Number(cantidadStr);
        if (Number.isNaN(cantidad) || cantidad <= 0) {
            alert('La cantidad debe ser mayor a 0');
            return;
        }

        const costoTotalStr = window.prompt(`¿Cuál fue el costo total de la compra de "${item.nombre}"?`, '0');
        if (costoTotalStr === null) return;

        const costo_total = Number(costoTotalStr);
        if (Number.isNaN(costo_total) || costo_total < 0) {
            alert('El costo total no es válido');
            return;
        }

        setProcessingId(item.id);
        router.put(
            route('alimentacion.inventario.reabastecer', item.id),
            { cantidad, costo_total },
            { onFinish: () => setProcessingId(null) }
        );
    };

    // ─── Editar ───────────────────────────────────────────────────────────────
    const handleEdit = (item) => {
        setEditingId(item.id);
        setData({
            nombre:             item.nombre             ?? '',
            tipo:               item.tipo               ?? '',
            marca:              item.marca              ?? '',
            existencias:        item.existencias        ?? '',
            unidad:             item.unidad             ?? 'kg',
            costo_total:        '',
            MS:                 item.MS                 ?? '',
            PB:                 item.PB                 ?? '',
            EM:                 item.EM                 ?? '',
            FDN:                item.FDN                ?? '',
            auto_rellenar:      Boolean(item.auto_rellenar),
            cantidad_rellenado: item.cantidad_rellenado ?? '',
        });
        setUsarValoresNutrimentales(
            item.MS != null || item.PB != null || item.EM != null || item.FDN != null
        );
    };

    // ─── Limpiar formulario ───────────────────────────────────────────────────
    const clearForm = () => {
        setEditingId(null);
        setUsarValoresNutrimentales(false);
        reset();
        setData({
            nombre: '', tipo: '', marca: '', existencias: '', unidad: 'kg',
            costo_total: '', MS: '', PB: '', EM: '', FDN: '',
            auto_rellenar: false, cantidad_rellenado: '',
        });
        localStorage.removeItem(DRAFT_KEY);
    };

    // ─── Crear ────────────────────────────────────────────────────────────────
    const handleCreate = (e) => {
        e.preventDefault();
        post(route('alimentacion.inventario.store'), {
            data: {
                ...data,
                activo: true,
                MS:  usarValoresNutrimentales ? data.MS  : null,
                PB:  usarValoresNutrimentales ? data.PB  : null,
                EM:  usarValoresNutrimentales ? data.EM  : null,
                FDN: usarValoresNutrimentales ? data.FDN : null,
            },
            onSuccess: clearForm,
        });
    };

    // ─── Actualizar ───────────────────────────────────────────────────────────
    const handleUpdate = (e) => {
        e.preventDefault();
        if (!editingId) return;
        put(route('alimentacion.inventario.update', editingId), {
            data: {
                nombre:             data.nombre,
                tipo:               data.tipo,
                marca:              data.marca,
                unidad:             data.unidad,
                auto_rellenar:      data.auto_rellenar,
                cantidad_rellenado: data.cantidad_rellenado,
                MS:  usarValoresNutrimentales ? data.MS  : null,
                PB:  usarValoresNutrimentales ? data.PB  : null,
                EM:  usarValoresNutrimentales ? data.EM  : null,
                FDN: usarValoresNutrimentales ? data.FDN : null,
            },
            onSuccess: clearForm,
        });
    };

    // ─── Activar / Desactivar / Eliminar ─────────────────────────────────────
    const handleToggleActivo = (item) => {
        if (!item.activo) {
            if (!confirm(`¿Reactivar "${item.nombre}"?`)) return;
            router.patch(route('alimentacion.inventario.reactivar', item.id));
            return;
        }

        if (!confirm(`¿Desactivar o eliminar "${item.nombre}"? Si nunca fue usado se eliminará permanentemente; si ya tiene historial, solo se desactivará.`)) return;
        router.delete(route('alimentacion.inventario.destroy', item.id));
    };

    // ─── Render ───────────────────────────────────────────────────────────────
    return (
        <div className="space-y-4">

            {/* Encabezado */}
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-xl font-semibold text-gray-800">Inventario de Insumos</h2>
                    <p className="text-sm text-gray-500">Control de stock, costo por kilo y reabastecimiento.</p>
                </div>

                <label className="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                    <input
                        type="checkbox"
                        checked={mostrarDesactivados}
                        onChange={e => setMostrarDesactivados(e.target.checked)}
                        className="rounded border-gray-300"
                    />
                    Ver desactivados
                </label>
            </div>

            {/* Formulario */}
            <div className={panelClass}>
                <div className="mb-3 flex items-center justify-between flex-wrap gap-2">
                    <h3 className={`text-sm font-semibold ${isEditing ? 'text-blue-800' : 'text-gray-800'}`}>
                        {isEditing ? 'Editar insumo' : 'Agregar insumo al inventario'}
                    </h3>

                    {isEditing && (
                        <p className="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-3 py-1">
                            Solo se editan datos descriptivos. Para cambiar stock o costo, usa "Rellenar".
                        </p>
                    )}
                </div>

                <form
                    onSubmit={isEditing ? handleUpdate : handleCreate}
                    className="grid grid-cols-1 gap-4 text-sm md:grid-cols-3"
                >
                    {/* Nombre */}
                    <div>
                        <label className="mb-1 block text-xs font-medium text-gray-600">Nombre *</label>
                        <input
                            type="text"
                            className={inputClass}
                            placeholder="Ej. Maíz molido, Alfalfa"
                            value={data.nombre}
                            onChange={e => setData('nombre', e.target.value)}
                        />
                        {errors.nombre && <p className="mt-1 text-xs text-red-500">{errors.nombre}</p>}
                    </div>

                    {/* Tipo */}
                    <div>
                        <label className="mb-1 block text-xs font-medium text-gray-600">Tipo *</label>
                        <select
                            className={inputClass}
                            value={data.tipo}
                            onChange={e => setData('tipo', e.target.value)}
                        >
                            <option value="">Selecciona un tipo</option>
                            {['Forraje', 'Concentrado', 'Suplemento', 'Mineral', 'Energético', 'Proteico', 'Aditivo'].map(t => (
                                <option key={t} value={t}>{t}</option>
                            ))}
                        </select>
                        {errors.tipo && <p className="mt-1 text-xs text-red-500">{errors.tipo}</p>}
                    </div>

                    {/* Marca */}
                    <div>
                        <label className="mb-1 block text-xs font-medium text-gray-600">Marca</label>
                        <input
                            type="text"
                            className={inputClass}
                            placeholder="Ej. Purina"
                            value={data.marca}
                            onChange={e => setData('marca', e.target.value)}
                        />
                    </div>

                    {/* Campos solo al crear */}
                    {!isEditing && (
                        <>
                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Existencias *</label>
                                <input
                                    type="number" step="0.01" min="0" inputMode="decimal"
                                    onWheel={preventWheelChange}
                                    className={inputClass}
                                    value={data.existencias}
                                    onChange={e => setData('existencias', e.target.value)}
                                />
                                {errors.existencias && <p className="mt-1 text-xs text-red-500">{errors.existencias}</p>}
                            </div>

                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Unidad</label>
                                <input
                                    type="text" className={inputClass} placeholder="kg, paca, bulto..."
                                    value={data.unidad}
                                    onChange={e => setData('unidad', e.target.value)}
                                />
                            </div>

                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Costo total *</label>
                                <input
                                    type="number" step="0.01" min="0" inputMode="decimal"
                                    onWheel={preventWheelChange}
                                    className={inputClass}
                                    placeholder="Ej. 500"
                                    value={data.costo_total}
                                    onChange={e => setData('costo_total', e.target.value)}
                                />
                                {errors.costo_total && <p className="mt-1 text-xs text-red-500">{errors.costo_total}</p>}
                            </div>

                            <div>
                                <label className="mb-1 block text-xs font-medium text-gray-600">Costo por kg</label>
                                <div className="rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                                    {costoPorKg ? `$${costoPorKg}` : '—'}
                                </div>
                            </div>
                        </>
                    )}

                    {/* Unidad al editar */}
                    {isEditing && (
                        <div>
                            <label className="mb-1 block text-xs font-medium text-gray-600">Unidad</label>
                            <input
                                type="text" className={inputClass} placeholder="kg, paca, bulto..."
                                value={data.unidad}
                                onChange={e => setData('unidad', e.target.value)}
                            />
                        </div>
                    )}

                    {/* Toggle valores nutrimentales */}
                    <div className="md:col-span-2 flex items-end">
                        <label className="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                            <input
                                type="checkbox"
                                checked={usarValoresNutrimentales}
                                onChange={e => {
                                    setUsarValoresNutrimentales(e.target.checked);
                                    if (!e.target.checked) {
                                        setData(prev => ({ ...prev, MS: '', PB: '', EM: '', FDN: '' }));
                                    }
                                }}
                                className="rounded border-gray-300"
                            />
                            Aplicar valores nutrimentales
                        </label>
                    </div>

                    {/* Campos nutrimentales */}
                    {usarValoresNutrimentales && (
                        ['MS', 'PB', 'EM', 'FDN'].map(campo => (
                            <div key={campo}>
                                <label className="mb-1 block text-xs font-medium text-gray-600">{campo}</label>
                                <input
                                    type="number" step="0.01" min="0" inputMode="decimal"
                                    onWheel={preventWheelChange}
                                    className={inputClass}
                                    value={data[campo]}
                                    onChange={e => setData(campo, e.target.value)}
                                />
                            </div>
                        ))
                    )}

                    {/* Auto rellenar */}
                    <div className={`md:col-span-3 rounded-lg border p-4 ${isEditing ? 'border-blue-200 bg-blue-100/60' : 'border-blue-100 bg-blue-50/40'}`}>
                        <div className="mb-3 flex items-center gap-2">
                            <input
                                id="auto_rellenar"
                                type="checkbox"
                                checked={data.auto_rellenar}
                                onChange={e => setData('auto_rellenar', e.target.checked)}
                            />
                            <label htmlFor="auto_rellenar" className="text-xs font-medium text-gray-700">
                                Rellenar insumo automáticamente cuando se acabe el stock
                            </label>
                        </div>

                        <div>
                            <label className="mb-1 block text-xs font-medium text-gray-600">Cantidad a rellenar</label>
                            <input
                                type="number" step="0.01" min="0" inputMode="decimal"
                                onWheel={preventWheelChange}
                                className={inputClass}
                                value={data.cantidad_rellenado}
                                onChange={e => setData('cantidad_rellenado', e.target.value)}
                                disabled={!data.auto_rellenar}
                            />
                            {errors.cantidad_rellenado && (
                                <p className="mt-1 text-xs text-red-500">{errors.cantidad_rellenado}</p>
                            )}
                        </div>
                    </div>

                    {/* Botones */}
                    <div className="md:col-span-3 flex justify-end gap-2 pt-2">
                        {isEditing && (
                            <button
                                type="button"
                                onClick={clearForm}
                                className="rounded-md border border-gray-300 px-4 py-2 text-xs text-gray-700 hover:bg-gray-50"
                            >
                                Cancelar
                            </button>
                        )}
                        <button
                            type="submit"
                            disabled={processing}
                            className={`rounded-md px-4 py-2 text-xs text-white disabled:opacity-50 ${
                                isEditing ? 'bg-blue-600 hover:bg-blue-700' : 'bg-green-600 hover:bg-green-700'
                            }`}
                        >
                            {processing ? 'Guardando...' : isEditing ? 'Guardar cambios' : 'Agregar insumo'}
                        </button>
                    </div>
                </form>
            </div>

            {/* Flash messages */}
            {flash.success && (
                <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {flash.success}
                </div>
            )}
            {flash.error && (
                <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {flash.error}
                </div>
            )}

            {/* Lista de insumos */}
            <div className="space-y-3">
                {items.map((item) => {
                    const status = getStatus(item);

                    return (
                        <div
                            key={item.id}
                            className={`flex flex-col gap-3 rounded-xl border px-5 py-4 shadow-sm sm:flex-row sm:items-center sm:justify-between ${
                                !item.activo
                                    ? 'border-gray-200 bg-gray-50 opacity-70'
                                    : 'border-blue-50 bg-white'
                            }`}
                        >
                            {/* Info */}
                            <div>
                                <h3 className="text-sm font-semibold text-gray-900">
                                    {item.nombre}
                                    {!item.activo && (
                                        <span className="ml-2 text-xs font-normal text-gray-400">(desactivado)</span>
                                    )}
                                </h3>

                                <p className="text-xs text-gray-500">
                                    {item.tipo  && <span>{item.tipo} · </span>}
                                    {item.marca && <span>{item.marca} · </span>}
                                    {item.unidad ? `${item.existencias} ${item.unidad}` : item.existencias}
                                </p>

                                {item.costo_promedio != null && (
                                    <p className="text-xs text-gray-400">Costo por kg: ${item.costo_promedio}</p>
                                )}

                                {(item.MS != null || item.PB != null || item.EM != null || item.FDN != null) && (
                                    <div className="mt-2 flex flex-wrap gap-2 text-[11px]">
                                        {item.MS  != null && <span className="rounded bg-blue-50 px-2 py-1 text-blue-700">MS: {item.MS}</span>}
                                        {item.PB  != null && <span className="rounded bg-blue-50 px-2 py-1 text-blue-700">PB: {item.PB}</span>}
                                        {item.EM  != null && <span className="rounded bg-blue-50 px-2 py-1 text-blue-700">EM: {item.EM}</span>}
                                        {item.FDN != null && <span className="rounded bg-blue-50 px-2 py-1 text-blue-700">FDN: {item.FDN}</span>}
                                    </div>
                                )}

                                {item.auto_rellenar && (
                                    <p className="mt-2 text-xs text-blue-600">
                                        Auto rellenado al terminar stock, cantidad: {item.cantidad_rellenado}
                                    </p>
                                )}
                            </div>

                            {/* Acciones */}
                            <div className="flex flex-wrap items-center gap-2">
                                <span className={`rounded-full border px-3 py-1 text-xs ${status.color}`}>
                                    {status.label}
                                </span>

                                {item.activo && (
                                    <button
                                        type="button"
                                        onClick={() => handleReabastecer(item)}
                                        disabled={processingId === item.id}
                                        className="rounded-md bg-blue-600 px-4 py-2 text-xs text-white hover:bg-blue-700 disabled:opacity-50"
                                    >
                                        {processingId === item.id ? 'Guardando...' : 'Rellenar'}
                                    </button>
                                )}

                                {item.activo && (
                                    <button
                                        type="button"
                                        onClick={() => handleEdit(item)}
                                        className="rounded-md border border-blue-200 px-4 py-2 text-xs text-blue-700 hover:bg-blue-50"
                                    >
                                        Editar
                                    </button>
                                )}

                                <button
                                    type="button"
                                    onClick={() => handleToggleActivo(item)}
                                    className={`rounded-md border px-4 py-2 text-xs ${
                                        !item.activo
                                            ? 'border-green-200 text-green-700 hover:bg-green-50'
                                            : 'border-red-200 text-red-600 hover:bg-red-50'
                                    }`}
                                >
                                    {!item.activo ? 'Reactivar' : 'Desactivar'}
                                </button>
                            </div>
                        </div>
                    );
                })}

                {items.length === 0 && (
                    <p className="text-sm text-gray-500">
                        {mostrarDesactivados
                            ? 'No hay insumos registrados.'
                            : 'No hay insumos activos. Puedes ver los desactivados arriba.'}
                    </p>
                )}
            </div>
        </div>
    );
}