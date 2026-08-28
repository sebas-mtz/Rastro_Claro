import React, { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { X, Calculator } from 'lucide-react';
import { formatMXN } from '@/utils/currency';

const VACIO = {
    trabajador_id: '',
    tipo_actividad: '',
    animal_id: '',
    lote_id: '',
    fecha: new Date().toISOString().slice(0, 10),
    hora_inicio: '',
    hora_fin: '',
    modalidad_pago: 'hora',
    horas_trabajadas: '',
    jornadas: '',
    costo_hora: '',
    costo_jornada: '',
    distribuir_entre_animales: false,
    descripcion: '',
    observaciones: '',
};

export default function ActividadModal({
    show,
    onClose,
    trabajadorFijo = null,
    trabajadores = [],
    tiposActividad = {},
    modalidadesPago = {},
    animales = [],
    lotes = [],
    puedeVerCostos = false,
}) {
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        ...VACIO,
        trabajador_id: trabajadorFijo?.id || '',
    });

    // Vista previa del importe. El valor que se guarda lo recalcula el backend.
    const [previa, setPrevia] = useState(null);
    const [calculando, setCalculando] = useState(false);

    useEffect(() => {
        if (show) {
            setData({ ...VACIO, trabajador_id: trabajadorFijo?.id || '' });
            setPrevia(null);
            clearErrors();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [show, trabajadorFijo?.id]);

    const calcular = async () => {
        if (!data.trabajador_id) return;

        setCalculando(true);

        try {
            const respuesta = await fetch(route('actividades-trabajador.calcular'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    trabajador_id: data.trabajador_id,
                    modalidad_pago: data.modalidad_pago,
                    hora_inicio: data.hora_inicio || null,
                    hora_fin: data.hora_fin || null,
                    horas_trabajadas: data.horas_trabajadas || null,
                    jornadas: data.jornadas || null,
                    costo_hora: data.costo_hora || null,
                    costo_jornada: data.costo_jornada || null,
                    animal_id: data.animal_id || null,
                    lote_id: data.lote_id || null,
                    distribuir_entre_animales: data.distribuir_entre_animales,
                }),
            });

            if (!respuesta.ok) {
                setPrevia(null);
                return;
            }

            setPrevia(await respuesta.json());
        } catch {
            // Sin conexión no se muestra una cifra inventada: simplemente no hay previa.
            setPrevia(null);
        } finally {
            setCalculando(false);
        }
    };

    const enviar = (e) => {
        e.preventDefault();

        post(route('actividades-trabajador.store'), {
            preserveScroll: true,
            onSuccess: () => { reset(); setPrevia(null); onClose(); },
        });
    };

    const porJornada = data.modalidad_pago === 'jornada';

    if (!show) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-start justify-center bg-black/50 overflow-y-auto p-4">
            <div className="bg-white rounded-2xl shadow-xl w-full max-w-2xl my-8">
                <div className="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                    <h3 className="text-lg font-semibold text-slate-800">Registrar actividad</h3>
                    <button onClick={onClose} className="text-slate-400 hover:text-slate-700" aria-label="Cerrar">
                        <X className="w-5 h-5" />
                    </button>
                </div>

                <form onSubmit={enviar} className="px-6 py-5 space-y-4">
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Trabajador *</label>
                            {trabajadorFijo ? (
                                <p className="px-3 py-2 rounded-lg bg-slate-50 border border-slate-200 text-sm text-slate-700">
                                    {trabajadorFijo.nombre_completo}
                                </p>
                            ) : (
                                <select
                                    value={data.trabajador_id}
                                    onChange={(e) => setData('trabajador_id', e.target.value)}
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                                    required
                                >
                                    <option value="">Selecciona…</option>
                                    {trabajadores.filter((t) => t.activo).map((t) => (
                                        <option key={t.id} value={t.id}>
                                            {[t.nombre, t.apellido_paterno, t.apellido_materno].filter(Boolean).join(' ')}
                                        </option>
                                    ))}
                                </select>
                            )}
                            {errors.trabajador_id && <p className="text-xs text-red-600 mt-1">{errors.trabajador_id}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Actividad *</label>
                            <select
                                value={data.tipo_actividad}
                                onChange={(e) => setData('tipo_actividad', e.target.value)}
                                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                                required
                            >
                                <option value="">Selecciona…</option>
                                {Object.entries(tiposActividad).map(([valor, etiqueta]) => (
                                    <option key={valor} value={valor}>{etiqueta}</option>
                                ))}
                            </select>
                            {errors.tipo_actividad && <p className="text-xs text-red-600 mt-1">{errors.tipo_actividad}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Ejemplar (opcional)</label>
                            <select
                                value={data.animal_id}
                                onChange={(e) => setData('animal_id', e.target.value)}
                                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                            >
                                <option value="">Ninguno en particular</option>
                                {animales.map((a) => (
                                    <option key={a.id} value={a.id}>
                                        {a.arete}{a.alias ? ` — ${a.alias}` : ''}
                                    </option>
                                ))}
                            </select>
                            {errors.animal_id && <p className="text-xs text-red-600 mt-1">{errors.animal_id}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Lote (opcional)</label>
                            <select
                                value={data.lote_id}
                                onChange={(e) => setData('lote_id', e.target.value)}
                                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                            >
                                <option value="">Ninguno</option>
                                {lotes.map((l) => (
                                    <option key={l.id} value={l.id}>{l.nombre}</option>
                                ))}
                            </select>
                            {errors.lote_id && <p className="text-xs text-red-600 mt-1">{errors.lote_id}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Fecha *</label>
                            <input
                                type="date"
                                value={data.fecha}
                                onChange={(e) => setData('fecha', e.target.value)}
                                max={new Date().toISOString().slice(0, 10)}
                                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                                required
                            />
                            {errors.fecha && <p className="text-xs text-red-600 mt-1">{errors.fecha}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Forma de pago *</label>
                            <select
                                value={data.modalidad_pago}
                                onChange={(e) => setData('modalidad_pago', e.target.value)}
                                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                            >
                                {Object.entries(modalidadesPago).map(([valor, etiqueta]) => (
                                    <option key={valor} value={valor}>{etiqueta}</option>
                                ))}
                            </select>
                        </div>
                    </div>

                    {/* Tiempo */}
                    {porJornada ? (
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Jornadas</label>
                                <input
                                    type="number" step="0.5" min="0" max="31"
                                    value={data.jornadas}
                                    onChange={(e) => setData('jornadas', e.target.value)}
                                    placeholder="1"
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                                />
                                {errors.jornadas && <p className="text-xs text-red-600 mt-1">{errors.jornadas}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">
                                    Costo por jornada
                                </label>
                                <input
                                    type="number" step="0.01" min="0"
                                    value={data.costo_jornada}
                                    onChange={(e) => setData('costo_jornada', e.target.value)}
                                    placeholder="Se toma de la ficha del trabajador"
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                                />
                                {errors.costo_jornada && <p className="text-xs text-red-600 mt-1">{errors.costo_jornada}</p>}
                            </div>
                        </div>
                    ) : (
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Hora inicio</label>
                                <input
                                    type="time"
                                    value={data.hora_inicio}
                                    onChange={(e) => setData('hora_inicio', e.target.value)}
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                                />
                                {errors.hora_inicio && <p className="text-xs text-red-600 mt-1">{errors.hora_inicio}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Hora fin</label>
                                <input
                                    type="time"
                                    value={data.hora_fin}
                                    onChange={(e) => setData('hora_fin', e.target.value)}
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                                />
                                {errors.hora_fin && <p className="text-xs text-red-600 mt-1">{errors.hora_fin}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Horas</label>
                                <input
                                    type="number" step="0.25" min="0" max="24"
                                    value={data.horas_trabajadas}
                                    onChange={(e) => setData('horas_trabajadas', e.target.value)}
                                    placeholder="o el reloj"
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                                />
                                {errors.horas_trabajadas && <p className="text-xs text-red-600 mt-1">{errors.horas_trabajadas}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Costo/hora</label>
                                <input
                                    type="number" step="0.01" min="0"
                                    value={data.costo_hora}
                                    onChange={(e) => setData('costo_hora', e.target.value)}
                                    placeholder="ficha"
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                                />
                                {errors.costo_hora && <p className="text-xs text-red-600 mt-1">{errors.costo_hora}</p>}
                            </div>
                        </div>
                    )}

                    {/* Distribución */}
                    {data.lote_id && (
                        <label className="flex items-start gap-2 text-sm text-slate-700 bg-slate-50 border border-slate-200 rounded-lg p-3">
                            <input
                                type="checkbox"
                                checked={data.distribuir_entre_animales}
                                onChange={(e) => setData('distribuir_entre_animales', e.target.checked)}
                                className="mt-0.5"
                            />
                            <span>
                                Repartir el costo entre los ejemplares del lote.
                                <span className="block text-xs text-slate-500">
                                    Cada ejemplar recibirá su parte proporcional en su valuación.
                                </span>
                            </span>
                        </label>
                    )}

                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">Descripción</label>
                        <textarea
                            value={data.descripcion}
                            onChange={(e) => setData('descripcion', e.target.value)}
                            rows={2}
                            maxLength={2000}
                            className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                        />
                        {errors.descripcion && <p className="text-xs text-red-600 mt-1">{errors.descripcion}</p>}
                    </div>

                    {/* Vista previa del cálculo */}
                    {puedeVerCostos && (
                        <div className="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-2">
                            <button
                                type="button"
                                onClick={calcular}
                                disabled={calculando || !data.trabajador_id}
                                className="text-sm text-emerald-700 hover:text-emerald-900 font-medium flex items-center gap-1 disabled:opacity-50"
                            >
                                <Calculator className="w-4 h-4" />
                                {calculando ? 'Calculando…' : 'Calcular costo de mano de obra'}
                            </button>

                            {previa && (
                                <div className="text-sm text-slate-700 space-y-1">
                                    <p className="flex justify-between">
                                        <span>
                                            {previa.modalidad_pago === 'jornada'
                                                ? `${previa.jornadas ?? 0} jornada(s) × ${formatMXN(previa.costo_jornada)}`
                                                : `${previa.horas_trabajadas ?? 0} hora(s) × ${formatMXN(previa.costo_hora)}`}
                                        </span>
                                        <span className="font-semibold tabular-nums">{formatMXN(previa.costo_total)}</span>
                                    </p>

                                    {previa.animales_atendidos > 1 && (
                                        <p className="flex justify-between text-xs text-slate-500">
                                            <span>Entre {previa.animales_atendidos} ejemplares</span>
                                            <span className="tabular-nums">{formatMXN(previa.costo_por_animal)} c/u</span>
                                        </p>
                                    )}

                                    {previa.metodo_distribucion && (
                                        <p className="text-xs text-slate-500">{previa.metodo_distribucion}</p>
                                    )}

                                    <p className="text-xs text-slate-400 pt-1 border-t border-slate-200">
                                        Vista previa. El importe definitivo lo calcula el servidor al guardar.
                                    </p>
                                </div>
                            )}
                        </div>
                    )}

                    <div className="flex justify-end gap-3 pt-2 border-t border-slate-200">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium disabled:opacity-60"
                        >
                            {processing ? 'Guardando…' : 'Registrar actividad'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
