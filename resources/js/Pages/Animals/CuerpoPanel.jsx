import React, { lazy, Suspense, useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import { Box, Plus, Check, RotateCcw, Trash2, X, Loader2 } from 'lucide-react';
import { ZONAS, COLOR_TIPO, COLOR_RESUELTA } from '@/Components/Borrega3D/zonas';

/**
 * Panel del cuerpo del ejemplar dentro de su ficha.
 *
 * El visor 3D se carga aparte y solo cuando se abre este panel: las librerías
 * de 3D pesan más que todo el resto de la aplicación junta, y no tiene sentido
 * que las descargue quien solo viene a ver el peso de una borrega.
 */
const VisorBorrega = lazy(() => import('@/Components/Borrega3D/VisorBorrega'));

function Cargando({ altura }) {
    return (
        <div
            style={{ height: altura }}
            className="rounded-2xl border border-slate-200 bg-slate-50 flex flex-col items-center justify-center gap-2 text-slate-400"
        >
            <Loader2 className="w-6 h-6 animate-spin" />
            <span className="text-sm">Preparando la vista 3D…</span>
        </div>
    );
}

function fmt(f) {
    return f ? new Date(f).toLocaleDateString('es-MX') : '—';
}

export default function CuerpoPanel({ animal, marcas = [], tipos = {}, puedeEditar = true }) {
    const [modoSeleccion, setModoSeleccion] = useState(false);
    const [zona, setZona] = useState(null);
    const [resaltada, setResaltada] = useState(null);

    const form = useForm({
        zona: '',
        tipo: 'lesion',
        descripcion: '',
        fecha: new Date().toISOString().split('T')[0],
    });

    const activas = marcas.filter((m) => m.estado === 'activa');

    const elegirZona = (clave) => {
        setZona(clave);
        form.setData('zona', clave);
    };

    const cancelar = () => {
        setModoSeleccion(false);
        setZona(null);
        form.reset();
        form.clearErrors();
    };

    const guardar = (e) => {
        e.preventDefault();
        form.post(route('marcas.store', animal.id), {
            preserveScroll: true,
            onSuccess: () => cancelar(),
        });
    };

    const cambiarEstado = (marca) => {
        const resolver = marca.estado === 'activa';

        if (resolver && !confirm(`¿Dar por resuelta la marca de ${marca.zona_legible}?`)) return;

        router.patch(
            route('marcas.resolver', marca.id),
            { estado: resolver ? 'resuelta' : 'activa' },
            { preserveScroll: true },
        );
    };

    const borrar = (marca) => {
        if (!confirm('¿Eliminar esta marca? Solo procede si se capturó hoy.')) return;
        router.delete(route('marcas.destroy', marca.id), { preserveScroll: true });
    };

    // La lana toma el color de la raza cuando se conoce; si no, crema.
    const colorLana = animal?.raza?.color_lana || '#f5f0e6';
    const gestante = ['gestante', 'proxima_a_parir'].includes(animal?.estado_reproductivo);

    return (
        <div className="space-y-5">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 className="font-semibold text-slate-800 flex items-center gap-2">
                        <Box className="w-5 h-5 text-slate-400" /> Cuerpo del ejemplar
                    </h3>
                    <p className="text-sm text-slate-600">
                        Señala dónde tiene una lesión, dónde se le aplicó algo o dónde trae el arete.
                    </p>
                </div>

                {puedeEditar && !modoSeleccion && (
                    <button
                        onClick={() => setModoSeleccion(true)}
                        className="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium px-4 py-2 rounded-lg flex items-center gap-2"
                    >
                        <Plus className="w-4 h-4" /> Marcar una parte
                    </button>
                )}
            </div>

            {modoSeleccion && (
                <p className="text-sm text-emerald-900 bg-emerald-50 border border-emerald-200 rounded-lg p-3">
                    Gira la borrega y toca el punto de la parte que quieres marcar.
                    {zona && <> Elegiste: <strong>{ZONAS[zona]?.etiqueta}</strong>.</>}
                </p>
            )}

            <Suspense fallback={<Cargando altura={380} />}>
                <VisorBorrega
                    marcas={marcas}
                    modoSeleccion={modoSeleccion}
                    zonaSeleccionada={zona}
                    onElegirZona={elegirZona}
                    onSeleccionarMarca={(m) => setResaltada(m.id)}
                    marcaResaltada={resaltada}
                    colorLana={colorLana}
                    gestante={gestante}
                />
            </Suspense>

            {/* Alta de marca */}
            {modoSeleccion && (
                <form onSubmit={guardar} className="bg-white rounded-2xl border border-slate-200 p-4 space-y-4">
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Parte *</label>
                            <select
                                value={form.data.zona}
                                onChange={(e) => elegirZona(e.target.value)}
                                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                                required
                            >
                                <option value="">Toca un punto o elige aquí…</option>
                                {Object.entries(ZONAS).map(([clave, z]) => (
                                    <option key={clave} value={clave}>{z.etiqueta}</option>
                                ))}
                            </select>
                            {form.errors.zona && <p className="text-xs text-red-600 mt-1">{form.errors.zona}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Qué es *</label>
                            <select
                                value={form.data.tipo}
                                onChange={(e) => form.setData('tipo', e.target.value)}
                                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                            >
                                {Object.entries(tipos).map(([clave, etiqueta]) => (
                                    <option key={clave} value={clave}>{etiqueta}</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Fecha *</label>
                            <input
                                type="date"
                                value={form.data.fecha}
                                onChange={(e) => form.setData('fecha', e.target.value)}
                                max={new Date().toISOString().split('T')[0]}
                                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                                required
                            />
                            {form.errors.fecha && <p className="text-xs text-red-600 mt-1">{form.errors.fecha}</p>}
                        </div>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">Descripción</label>
                        <textarea
                            rows="2"
                            value={form.data.descripcion}
                            onChange={(e) => form.setData('descripcion', e.target.value)}
                            placeholder="Qué se observó y, si aplica, qué se hizo."
                            className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                        />
                    </div>

                    <div className="flex justify-end gap-3">
                        <button
                            type="button"
                            onClick={cancelar}
                            className="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm flex items-center gap-2"
                        >
                            <X className="w-4 h-4" /> Cancelar
                        </button>
                        <button
                            type="submit"
                            disabled={form.processing || !form.data.zona}
                            className="px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium disabled:opacity-50"
                        >
                            {form.processing ? 'Guardando…' : 'Registrar marca'}
                        </button>
                    </div>
                </form>
            )}

            {/* Listado */}
            <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div className="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                    <h4 className="font-medium text-slate-800 text-sm">
                        Marcas registradas
                        {activas.length > 0 && (
                            <span className="ml-2 text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full">
                                {activas.length} activa{activas.length === 1 ? '' : 's'}
                            </span>
                        )}
                    </h4>
                </div>

                {marcas.length === 0 ? (
                    <p className="text-center text-slate-500 text-sm py-8">
                        Sin marcas. Este ejemplar no tiene nada señalado sobre el cuerpo.
                    </p>
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {marcas.map((m) => (
                            <li
                                key={m.id}
                                onMouseEnter={() => setResaltada(m.id)}
                                onMouseLeave={() => setResaltada(null)}
                                className={
                                    'px-4 py-3 flex flex-wrap items-center gap-3 transition ' +
                                    (resaltada === m.id ? 'bg-slate-50' : '')
                                }
                            >
                                <span
                                    className="w-3 h-3 rounded-full shrink-0"
                                    style={{
                                        backgroundColor: m.estado === 'resuelta'
                                            ? COLOR_RESUELTA
                                            : (COLOR_TIPO[m.tipo] || COLOR_TIPO.observacion),
                                    }}
                                />

                                <div className="flex-1 min-w-[12rem]">
                                    <p className="text-sm font-medium text-slate-800">
                                        {m.zona_legible}
                                        <span className="ml-2 font-normal text-slate-500">{m.tipo_legible}</span>
                                    </p>
                                    {m.descripcion && (
                                        <p className="text-xs text-slate-600 mt-0.5">{m.descripcion}</p>
                                    )}
                                    <p className="text-xs text-slate-400 mt-0.5">
                                        {fmt(m.fecha)}
                                        {m.estado === 'resuelta' && ` · resuelta ${fmt(m.fecha_resolucion)}`}
                                    </p>
                                </div>

                                {puedeEditar && (
                                    <div className="flex items-center gap-3">
                                        <button
                                            onClick={() => cambiarEstado(m)}
                                            title={m.estado === 'activa' ? 'Dar por resuelta' : 'Reabrir'}
                                            className={m.estado === 'activa'
                                                ? 'text-emerald-600 hover:text-emerald-800'
                                                : 'text-slate-400 hover:text-slate-700'}
                                        >
                                            {m.estado === 'activa'
                                                ? <Check className="w-4 h-4" />
                                                : <RotateCcw className="w-4 h-4" />}
                                        </button>
                                        <button
                                            onClick={() => borrar(m)}
                                            title="Eliminar (solo el mismo día)"
                                            className="text-red-400 hover:text-red-600"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </button>
                                    </div>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </div>
    );
}
