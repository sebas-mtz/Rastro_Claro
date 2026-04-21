import { useForm, usePage, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

const DRAFT_KEY = 'raciones_form_draft_v2';

export default function Raciones() {
    // inventario siempre viene fresco de Inertia — si el controller lo pasa
    // correctamente, cualquier edición en Inventario se verá aquí al recargar
    // o navegar de vuelta.
    const { raciones = [], inventario = [] } = usePage().props;

    const [expanded, setExpanded]                         = useState(null);
    const [editingId, setEditingId]                       = useState(null);
    // 'none' | 'manual_total_ration'
    const [nutritionMode, setNutritionMode]               = useState('none');
    const [editandoConHistorial, setEditandoConHistorial] = useState(false);
    const [mostrarArchivadas, setMostrarArchivadas]       = useState(false);
    const [draftLoaded, setDraftLoaded]                   = useState(false);

    const { data, setData, post, put, reset, processing, errors } = useForm({
        nombre:                '',
        MS:                    '',
        PB:                    '',
        EM:                    '',
        FDN:                   '',
        minerales:             '',
        sin_valores_nutricion: false,
        insumos:               [{ id: '', cantidad: '' }],
    });

    const isEditing         = Boolean(editingId);
    const inputClass        = isEditing
        ? 'w-full rounded-md border-blue-300 bg-blue-50 focus:border-blue-500 focus:ring-blue-300'
        : 'w-full rounded-md border-gray-300 focus:border-blue-300 focus:ring-blue-200';
    const readonlyBlueClass = 'w-full rounded-md border border-blue-300 bg-blue-100 px-3 py-2 text-sm text-blue-800';

    const racionesFiltradas = useMemo(
        () => raciones.filter(r => mostrarArchivadas || r.activo !== false),
        [raciones, mostrarArchivadas]
    );

    // Solo insumos activos en el selector
    const inventarioActivo = useMemo(
        () => inventario.filter(i => i.activo !== false),
        [inventario]
    );

    /**
     * rowsDetailed: los datos de cada fila del formulario combinados con
     * el inventario ACTUAL que viene de props. Si el usuario editó un insumo,
     * al volver a Raciones los props se recargan y aquí se reflejan.
     */
    const rowsDetailed = useMemo(() => {
        return data.insumos.map((row, index) => {
            // Buscar siempre en el inventario fresco de props
            const item     = inventario.find(i => String(i.id) === String(row.id));
            const cantidad = Number(row.cantidad || 0);
            return { row, item: item ? { ...item, cantidad } : null, index };
        });
    }, [data.insumos, inventario]); // inventario viene de usePage — se actualiza solo

    const nutritionSummary = useMemo(() => {
        const rows          = rowsDetailed.map(x => x.item).filter(Boolean).filter(i => i.cantidad > 0);
        const totalCantidad = rows.reduce((acc, i) => acc + i.cantidad, 0);

        const empty = {
            totalCantidad: 0, conValores: 0, sinValores: 0,
            allHaveValues: false, anyHaveValues: false,
            costoTotal: '', costoPorKilo: '',
            MS: '', PB: '', EM: '', FDN: '',
            faltantes: [], disponibilidad: true,
        };
        if (!totalCantidad) return empty;

        const tieneValores   = i => i.MS != null && i.PB != null && i.EM != null && i.FDN != null;
        const conValoresRows = rows.filter(tieneValores);
        const sinValoresRows = rows.filter(i => !tieneValores(i));

        const calcWeighted = (field) => {
            const base  = conValoresRows.reduce((acc, i) => acc + i.cantidad, 0);
            const total = conValoresRows.reduce((acc, i) => acc + i.cantidad * Number(i[field] || 0), 0);
            return base > 0 ? Number((total / base).toFixed(2)) : '';
        };

        const costoRaw     = rows.reduce((acc, i) => acc + i.cantidad * Number(i.costo_promedio || 0), 0);
        const costoTotal   = Number(costoRaw.toFixed(2));
        const costoPorKilo = totalCantidad > 0 ? Number((costoRaw / totalCantidad).toFixed(2)) : '';

        const faltantes = rows
            .filter(i => Number(i.existencias || 0) < i.cantidad)
            .map(i => ({
                nombre:     i.nombre,
                requerido:  i.cantidad,
                disponible: Number(i.existencias || 0),
                faltante:   Number((i.cantidad - Number(i.existencias || 0)).toFixed(2)),
                unidad:     i.unidad || 'kg',
            }));

        return {
            totalCantidad,
            conValores:    conValoresRows.length,
            sinValores:    sinValoresRows.length,
            allHaveValues: sinValoresRows.length === 0 && conValoresRows.length > 0,
            anyHaveValues: conValoresRows.length > 0,
            costoTotal,
            costoPorKilo,
            MS:  calcWeighted('MS'), PB: calcWeighted('PB'),
            EM:  calcWeighted('EM'), FDN: calcWeighted('FDN'),
            faltantes,
            disponibilidad: faltantes.length === 0,
        };
    }, [rowsDetailed]);

    // ── Draft ──────────────────────────────────────────────────────────────
    useEffect(() => {
        try {
            const saved = localStorage.getItem(DRAFT_KEY);
            if (!saved) { setDraftLoaded(true); return; }
            const p = JSON.parse(saved);
            if (p.data) {
                setData({
                    nombre:                p.data.nombre ?? '',
                    MS:                    p.data.MS ?? '',
                    PB:                    p.data.PB ?? '',
                    EM:                    p.data.EM ?? '',
                    FDN:                   p.data.FDN ?? '',
                    minerales:             p.data.minerales ?? '',
                    sin_valores_nutricion: p.data.sin_valores_nutricion ?? false,
                    insumos:               Array.isArray(p.data.insumos) && p.data.insumos.length
                        ? p.data.insumos
                        : [{ id: '', cantidad: '' }],
                });
            }
            setNutritionMode(p.nutritionMode ?? 'none');
            setEditingId(p.editingId ?? null);
            setEditandoConHistorial(p.editandoConHistorial ?? false);
        } catch (e) {
            console.error('No se pudo restaurar el borrador:', e);
        } finally {
            setDraftLoaded(true);
        }
    }, []);

    useEffect(() => {
        if (!draftLoaded) return;
        try {
            localStorage.setItem(DRAFT_KEY, JSON.stringify({ data, nutritionMode, editingId, editandoConHistorial }));
        } catch (e) {
            console.error('No se pudo guardar el borrador:', e);
        }
    }, [data, nutritionMode, editingId, editandoConHistorial, draftLoaded]);

    // ── Formulario ─────────────────────────────────────────────────────────
    const agregarFila      = () => setData('insumos', [...data.insumos, { id: '', cantidad: '' }]);
    const eliminarFila     = (index) => {
        if (data.insumos.length === 1) return;
        const n = [...data.insumos]; n.splice(index, 1); setData('insumos', n);
    };
    const actualizarInsumo = (index, field, value) => {
        const n = [...data.insumos]; n[index][field] = value; setData('insumos', n);
    };

    const aplicarNutricionAutomatica = () => {
        if (!nutritionSummary.anyHaveValues) return;
        setNutritionMode('manual_total_ration');
        setData(prev => ({
            ...prev,
            sin_valores_nutricion: false,
            MS:  nutritionSummary.MS  !== '' ? String(nutritionSummary.MS)  : '',
            PB:  nutritionSummary.PB  !== '' ? String(nutritionSummary.PB)  : '',
            EM:  nutritionSummary.EM  !== '' ? String(nutritionSummary.EM)  : '',
            FDN: nutritionSummary.FDN !== '' ? String(nutritionSummary.FDN) : '',
        }));
    };

    /**
     * "Sin valores por ahora" / "Quitar valores":
     * Limpia los campos nutrimentales del form, cierra el panel manual,
     * y envía sin_valores_nutricion=true para que el backend guarde null.
     */
    const sinValoresPorAhora = () => {
        setNutritionMode('none');
        setData(prev => ({
            ...prev,
            sin_valores_nutricion: true,
            MS: '', PB: '', EM: '', FDN: '',
        }));
    };

    const abrirPanelManual = () => {
        setNutritionMode('manual_total_ration');
        setData(prev => ({ ...prev, sin_valores_nutricion: false }));
    };

    const limpiarFormulario = () => {
        reset();
        setData({ nombre: '', MS: '', PB: '', EM: '', FDN: '', minerales: '', sin_valores_nutricion: false, insumos: [{ id: '', cantidad: '' }] });
        setEditingId(null);
        setNutritionMode('none');
        setEditandoConHistorial(false);
        localStorage.removeItem(DRAFT_KEY);
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        const payload = {
            nombre:    data.nombre,
            minerales: data.minerales,
            insumos:   data.insumos,
            costo_total: nutritionSummary.costoTotal !== '' ? nutritionSummary.costoTotal : null,
            // sin_valores_nutricion le dice al backend que NO use nada,
            // ni manual ni calculado.
            sin_valores_nutricion: nutritionMode === 'none' ? true : false,
            // Valores manuales solo si el panel está abierto
            MS:  nutritionMode === 'manual_total_ration' ? data.MS  : null,
            PB:  nutritionMode === 'manual_total_ration' ? data.PB  : null,
            EM:  nutritionMode === 'manual_total_ration' ? data.EM  : null,
            FDN: nutritionMode === 'manual_total_ration' ? data.FDN : null,
        };

        if (editingId) {
            put(route('raciones.update', editingId), { data: payload, onSuccess: limpiarFormulario });
        } else {
            post(route('raciones.store'), { data: payload, onSuccess: limpiarFormulario });
        }
    };

    const handleToggleArchivado = (racion) => {
        if (racion.activo === false) {
            if (!confirm(`¿Reactivar la ración "${racion.nombre}"?`)) return;
            router.put(route('raciones.reactivar', racion.id));
        } else {
            const tieneHistorial = (racion.alimentaciones_count ?? 0) > 0;
            const msg = tieneHistorial
                ? `La ración "${racion.nombre}" tiene consumos y será archivada. Sus programaciones activas se pausarán. ¿Continuar?`
                : `¿Eliminar la ración "${racion.nombre}"? No tiene consumos, se eliminará definitivamente.`;
            if (!confirm(msg)) return;
            router.delete(route('raciones.destroy', racion.id));
        }
    };

    const handleEdit = (racion) => {
        setEditandoConHistorial((racion.alimentaciones_count ?? 0) > 0);
        setEditingId(racion.id);
        const tieneValores = racion.MS != null || racion.PB != null || racion.EM != null || racion.FDN != null;
        setNutritionMode(tieneValores ? 'manual_total_ration' : 'none');
        setData({
            nombre:                racion.nombre ?? '',
            MS:                    racion.MS  != null ? String(racion.MS)  : '',
            PB:                    racion.PB  != null ? String(racion.PB)  : '',
            EM:                    racion.EM  != null ? String(racion.EM)  : '',
            FDN:                   racion.FDN != null ? String(racion.FDN) : '',
            minerales:             racion.minerales ?? '',
            sin_valores_nutricion: !tieneValores,
            insumos:               racion.insumos?.map(i => ({
                id:       i.id,
                cantidad: i.pivot?.cantidad ?? '',
            })) ?? [{ id: '', cantidad: '' }],
        });
    };

    // Estados derivados para el panel nutricional
    const hayInsumosConCantidad = nutritionSummary.totalCantidad > 0;
    const ningunoTieneValores   = hayInsumosConCantidad && !nutritionSummary.anyHaveValues;
    const algunoSinValores      = hayInsumosConCantidad && nutritionSummary.anyHaveValues && nutritionSummary.sinValores > 0;
    const todosConValores       = nutritionSummary.allHaveValues;
    const { flash = {} } = usePage().props;

    return (
        <div className="space-y-5">
            <div className="flex items-center justify-between">
                <h2 className="text-xl font-semibold text-gray-800">Raciones</h2>
                <label className="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                    <input type="checkbox" checked={mostrarArchivadas}
                        onChange={e => setMostrarArchivadas(e.target.checked)}
                        className="rounded border-gray-300" />
                    Ver archivadas
                </label>
            </div>

            <div className={`${isEditing ? 'border-blue-200 bg-blue-50' : 'border-blue-100 bg-white'} rounded-xl border p-4 shadow-sm`}>
                <div className="mb-3">
                    <h3 className={`text-sm font-semibold ${isEditing ? 'text-blue-800' : 'text-gray-800'}`}>
                        {editingId ? 'Editar ración' : 'Crear nueva ración'}
                    </h3>
                </div>

                {editandoConHistorial && (
                    <div className="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                        <p className="text-xs font-medium text-amber-800">
                            Esta ración tiene consumos registrados. Los cambios aplican solo a consumos futuros —
                            el historial anterior se conserva intacto mediante snapshots.
                        </p>
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-5">
                    {/* Datos generales */}
                    <div className="grid grid-cols-1 gap-4 text-sm md:grid-cols-4">
                        <div>
                            <label className="mb-1 block text-xs font-medium text-gray-600">Nombre *</label>
                            <input type="text" value={data.nombre}
                                onChange={e => setData('nombre', e.target.value)} className={inputClass} />
                            {errors.nombre && <p className="mt-1 text-xs text-red-500">{errors.nombre}</p>}
                        </div>
                        <div>
                            <label className="mb-1 block text-xs font-medium text-gray-600">Minerales</label>
                            <input type="text" value={data.minerales}
                                onChange={e => setData('minerales', e.target.value)}
                                className={inputClass} placeholder="Ej. calcio, fósforo, zinc" />
                        </div>
                        <div>
                            <label className="mb-1 block text-xs font-medium text-gray-600">Precio total</label>
                            <div className={readonlyBlueClass}>
                                {nutritionSummary.costoTotal !== '' ? `$${nutritionSummary.costoTotal}` : '—'}
                            </div>
                        </div>
                        <div>
                            <label className="mb-1 block text-xs font-medium text-gray-600">Precio por kilo</label>
                            <div className={readonlyBlueClass}>
                                {nutritionSummary.costoPorKilo !== '' ? `$${nutritionSummary.costoPorKilo}` : '—'}
                            </div>
                        </div>
                    </div>

                    <div className="rounded-lg border border-blue-100 p-4 space-y-4">
                        {/* Panel de valores nutrimentales manuales */}
                        {nutritionMode === 'manual_total_ration' && (
                            <div className="rounded-lg border border-blue-200 bg-blue-50 p-4">
                                <div className="mb-3 flex items-center justify-between">
                                    <h5 className="text-xs font-semibold text-blue-800">Valores nutrimentales de la ración</h5>
                                    <button type="button" onClick={sinValoresPorAhora}
                                        className="text-xs text-gray-500 hover:text-gray-700 underline">
                                        Quitar valores
                                    </button>
                                </div>
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                                    {['MS','PB','EM','FDN'].map(campo => (
                                        <div key={campo}>
                                            <label className="mb-1 block text-xs font-medium text-blue-700">{campo}</label>
                                            <input type="number" step="0.01" value={data[campo]}
                                                onChange={e => setData(campo, e.target.value)}
                                                className="w-full rounded-md border-blue-300 bg-blue-100 focus:border-blue-500 focus:ring-blue-300" />
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Lista de insumos */}
                        <div className="flex items-center justify-between">
                            <h4 className="text-sm font-semibold text-gray-800">Insumos de la ración</h4>
                            <button type="button" onClick={agregarFila}
                                className="rounded-md bg-blue-600 px-3 py-1.5 text-xs text-white hover:bg-blue-700">
                                + Agregar insumo
                            </button>
                        </div>

                        <div className="space-y-3">
                            {data.insumos.map((insumo, index) => (
                                <div key={index} className="grid grid-cols-1 items-end gap-3 md:grid-cols-3">
                                    <div>
                                        <label className="mb-1 block text-xs font-medium text-gray-600">Insumo *</label>
                                        <select value={insumo.id}
                                            onChange={e => actualizarInsumo(index, 'id', e.target.value)}
                                            className={inputClass}>
                                            <option value="">Selecciona un insumo</option>
                                            {inventarioActivo.map(item => (
                                                <option key={item.id} value={item.id}>
                                                    {item.nombre} ({item.existencias} {item.unidad ?? ''})
                                                </option>
                                            ))}
                                        </select>
                                        {errors[`insumos.${index}.id`] && (
                                            <p className="mt-1 text-xs text-red-500">{errors[`insumos.${index}.id`]}</p>
                                        )}
                                    </div>
                                    <div>
                                        <label className="mb-1 block text-xs font-medium text-gray-600">Cantidad *</label>
                                        <input type="number" step="0.01" value={insumo.cantidad}
                                            onChange={e => actualizarInsumo(index, 'cantidad', e.target.value)}
                                            className={inputClass} />
                                        {errors[`insumos.${index}.cantidad`] && (
                                            <p className="mt-1 text-xs text-red-500">{errors[`insumos.${index}.cantidad`]}</p>
                                        )}
                                    </div>
                                    <div>
                                        <button type="button" onClick={() => eliminarFila(index)}
                                            className="rounded-md bg-red-600 px-3 py-2 text-xs text-white hover:bg-red-700">
                                            Eliminar
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>

                        {errors.insumos && <p className="text-xs text-red-500">{errors.insumos}</p>}

                        {/* Panel estado nutricional */}
                        {hayInsumosConCantidad && (
                            <div className="rounded-lg border border-blue-100 bg-blue-50/40 p-4 space-y-3">
                                <h5 className="text-xs font-semibold text-gray-700">Estado nutricional y costo</h5>

                                <div className="flex flex-wrap gap-4 text-xs text-blue-700">
                                    <span>Precio total: {nutritionSummary.costoTotal !== '' ? `$${nutritionSummary.costoTotal}` : '—'}</span>
                                    <span>Por kilo: {nutritionSummary.costoPorKilo !== '' ? `$${nutritionSummary.costoPorKilo}` : '—'}</span>
                                </div>

                                {/* Ninguno tiene valores → opción de registrar manualmente */}
                                {ningunoTieneValores && nutritionMode === 'none' && (
                                    <div className="rounded-md border border-blue-200 bg-white px-3 py-2.5 flex items-center justify-between gap-3 flex-wrap">
                                        <p className="text-xs text-blue-800">
                                            Ningún insumo tiene valores nutrimentales. ¿Registrar los valores de la ración manualmente?
                                        </p>
                                        <button type="button" onClick={abrirPanelManual}
                                            className="rounded-md border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs text-blue-700 hover:bg-blue-100 whitespace-nowrap">
                                            Sí, registrar valores
                                        </button>
                                    </div>
                                )}

                                {/* Algunos sin valores → aviso para ir a insumos */}
                                {algunoSinValores && (
                                    <div className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2.5 flex items-start gap-2">
                                        <span className="text-amber-600 text-xs mt-0.5">⚠</span>
                                        <div className="space-y-1.5">
                                            <p className="text-xs text-amber-800">
                                                {nutritionSummary.sinValores} insumo{nutritionSummary.sinValores > 1 ? 's' : ''} sin valores nutrimentales —
                                                edítalos en la pestaña <strong>Insumos</strong> para un cálculo completo.
                                            </p>
                                            <div className="flex flex-wrap gap-2">
                                                {nutritionMode !== 'manual_total_ration' && (
                                                    <button type="button" onClick={abrirPanelManual}
                                                        className="rounded-md border border-blue-200 bg-white px-3 py-1 text-xs text-blue-700 hover:bg-blue-50">
                                                        Registrar valores de la ración manualmente
                                                    </button>
                                                )}
                                                <button type="button" onClick={sinValoresPorAhora}
                                                    className="rounded-md border border-gray-200 bg-white px-3 py-1 text-xs text-gray-600 hover:bg-gray-50">
                                                    Continuar sin valores
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {/* Todos con valores */}
                                {todosConValores && (
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="text-xs text-green-700">
                                            Todos los insumos tienen valores nutrimentales.
                                        </p>
                                        {nutritionMode !== 'manual_total_ration' ? (
                                            <button type="button" onClick={aplicarNutricionAutomatica}
                                                className="rounded-md border border-blue-200 bg-white px-3 py-1.5 text-xs text-blue-700 hover:bg-blue-50">
                                                Aplicar cálculo automático a la ración
                                            </button>
                                        ) : (
                                            <button type="button" onClick={sinValoresPorAhora}
                                                className="rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50">
                                                Quitar valores de la ración
                                            </button>
                                        )}
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Disponibilidad */}
                        <div className="rounded-lg border border-red-100 bg-red-50/60 p-4 space-y-2">
                            <h5 className="text-xs font-semibold text-red-700">Disponibilidad</h5>
                            {nutritionSummary.disponibilidad ? (
                                <p className="text-xs text-green-700">Hay stock suficiente para preparar esta ración.</p>
                            ) : (
                                <>
                                    <p className="text-xs text-red-700">Stock insuficiente.</p>
                                    <div className="space-y-1 text-xs text-red-700">
                                        {nutritionSummary.faltantes.map((f, idx) => (
                                            <div key={idx}>
                                                {f.nombre}: faltan {f.faltante} {f.unidad}{' '}
                                                ({f.disponible} disponibles / {f.requerido} requeridos)
                                            </div>
                                        ))}
                                    </div>
                                </>
                            )}
                        </div>
                    </div>

                    <div className="flex justify-end gap-2">
                        {editingId && (
                            <button type="button" onClick={limpiarFormulario}
                                className="rounded-md border border-gray-300 px-4 py-2 text-xs text-gray-700 hover:bg-gray-50">
                                Cancelar
                            </button>
                        )}
                        <button type="submit" disabled={processing}
                            className={`rounded-md px-4 py-2 text-xs text-white disabled:opacity-50 ${isEditing ? 'bg-blue-600 hover:bg-blue-700' : 'bg-green-600 hover:bg-green-700'}`}>
                            {editingId ? 'Guardar cambios' : 'Guardar ración'}
                        </button>
                    </div>
                </form>
            </div>
            {flash.success && (
    <div className="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
        {flash.success}
    </div>
)}
{flash.error && (
    <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        {flash.error}
    </div>
)}
            {/* Tabla */}
            <div className="overflow-hidden rounded-lg border border-gray-100 bg-white shadow-sm">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-4 py-2 text-left">Nombre</th>
                                <th className="px-4 py-2 text-left">MS</th>
                                <th className="px-4 py-2 text-left">PB</th>
                                <th className="px-4 py-2 text-left">EM</th>
                                <th className="px-4 py-2 text-left">FDN</th>
                                <th className="px-4 py-2 text-left">Precio total</th>
                                <th className="px-4 py-2 text-left">Estado</th>
                                <th className="px-4 py-2 text-left">Acciones</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200 bg-white">
                            {racionesFiltradas.map(racion => (
                                <tr key={racion.id} className={racion.activo === false ? 'opacity-50 bg-gray-50' : ''}>
                                    <td className="px-4 py-2">{racion.nombre}</td>
                                    <td className="px-4 py-2">{racion.MS ?? '—'}</td>
                                    <td className="px-4 py-2">{racion.PB ?? '—'}</td>
                                    <td className="px-4 py-2">{racion.EM ?? '—'}</td>
                                    <td className="px-4 py-2">{racion.FDN ?? '—'}</td>
                                    <td className="px-4 py-2">{racion.costo_total != null ? `$${racion.costo_total}` : '—'}</td>
                                    <td className="px-4 py-2">
                                        {racion.activo === false
                                            ? <span className="text-xs text-gray-400">Archivada</span>
                                            : <span className="text-xs text-green-600">Activa</span>}
                                    </td>
                                    <td className="px-4 py-2 space-x-3">
                                        <button type="button"
                                            onClick={() => setExpanded(expanded === racion.id ? null : racion.id)}
                                            className="text-xs text-blue-600 hover:underline">
                                            {expanded === racion.id ? 'Ocultar' : 'Ver insumos'}
                                        </button>
                                        {racion.activo !== false && (
                                            <button type="button" onClick={() => handleEdit(racion)}
                                                className="text-xs text-blue-700 hover:underline">
                                                Editar
                                            </button>
                                        )}
                                        <button type="button" onClick={() => handleToggleArchivado(racion)}
                                            className={`text-xs hover:underline ${racion.activo === false ? 'text-green-600' : 'text-red-600'}`}>
                                            {racion.activo === false ? 'Reactivar' : 'Archivar'}
                                        </button>
                                    </td>
                                </tr>
                            ))}
                            {racionesFiltradas.length === 0 && (
                                <tr>
                                    <td colSpan="8" className="px-4 py-6 text-center text-sm text-gray-500">
                                        No hay raciones {mostrarArchivadas ? '' : 'activas '}registradas.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}