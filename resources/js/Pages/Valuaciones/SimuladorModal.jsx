import React, { useEffect, useState } from 'react';
import { X, Sliders, RotateCcw, Save, AlertTriangle } from 'lucide-react';
import { router } from '@inertiajs/react';
import axios from 'axios';
import { formatMXN } from '@/utils/currency';

const ETIQUETA_ESTADO = {
    joven_sin_edad_reproductiva: 'Borrega joven, sin edad reproductiva',
    abierta: 'Borrega abierta o vacía',
    cargada_semental_comercial: 'Cargada por semental comercial',
    cargada_semental_registro: 'Cargada por semental de registro',
    con_cria_al_pie: 'Con cría al pie',
    con_cria_hembra_al_pie: 'Con cría hembra al pie',
    con_cria_macho_al_pie: 'Con cría macho al pie',
    parto_multiple: 'Con parto múltiple',
    otro: 'Otro estado reproductivo',
};

export default function SimuladorModal({
    show, onClose, animal, calculo, estadosReproductivos = [], puedeMargenExtendido = false,
}) {
    const valoresBase = {
        porcentaje_margen_genetico: Number(calculo?.porcentaje_margen_genetico ?? 0),
        estado_reproductivo_valuacion: calculo?.estado_reproductivo_valuacion || 'abierta',
        plus_reproductivo: Number(calculo?.plus_reproductivo ?? 0),
        ajuste_manual: Number(calculo?.ajuste_manual ?? 0),
        motivo_ajuste: calculo?.motivo_ajuste || '',
    };

    const [valores, setValores] = useState(valoresBase);
    const [simulado, setSimulado] = useState(null);
    const [precioObjetivo, setPrecioObjetivo] = useState('');
    const [cargando, setCargando] = useState(false);
    const [guardando, setGuardando] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        if (show) {
            setValores(valoresBase);
            setSimulado(null);
            setError('');
            setPrecioObjetivo('');
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [show]);

    if (!show) return null;

    const set = (campo, valor) => setValores((prev) => ({ ...prev, [campo]: valor }));

    const margenExcedido = Number(valores.porcentaje_margen_genetico) > 100;
    const requiereMotivo = Number(valores.ajuste_manual) !== 0 || margenExcedido;
    const faltaMotivo = requiereMotivo && !valores.motivo_ajuste.trim();

    const simular = async () => {
        setCargando(true);
        setError('');
        try {
            const { data } = await axios.post(route('valuaciones.simular', animal.id), valores);
            setSimulado(data.calculo);
        } catch (e) {
            setError(e?.response?.data?.message || 'No se pudo simular la cotización.');
        } finally {
            setCargando(false);
        }
    };

    const restablecer = () => {
        setValores(valoresBase);
        setSimulado(null);
        setError('');
    };

    const guardar = (estado) => {
        if (faltaMotivo) {
            setError('Escribe una justificación para aplicar este ajuste.');
            return;
        }

        setGuardando(true);
        router.post(route('valuaciones.guardar', animal.id), { ...valores, estado }, {
            preserveScroll: true,
            onSuccess: () => { setGuardando(false); onClose(); },
            onError: (errs) => {
                setGuardando(false);
                setError(Object.values(errs)[0] || 'No se pudo guardar la cotización.');
            },
        });
    };

    const precioActual = Number(calculo?.precio_estimado ?? 0);
    const precioSimulado = simulado ? Number(simulado.precio_estimado) : null;
    const diferencia = precioSimulado != null ? precioSimulado - precioActual : null;
    const objetivo = parseFloat(precioObjetivo);

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div className="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div className="flex justify-between items-center border-b p-6 sticky top-0 bg-white">
                    <div className="flex items-center gap-3">
                        <Sliders className="w-6 h-6 text-emerald-600" />
                        <div>
                            <h2 className="text-xl font-bold text-gray-800">Simular cotización</h2>
                            <p className="text-xs text-gray-500">
                                Los cambios no afectan la información real hasta que guardes.
                            </p>
                        </div>
                    </div>
                    <button onClick={onClose} className="text-gray-400 hover:text-gray-600" aria-label="Cerrar">
                        <X className="w-6 h-6" />
                    </button>
                </div>

                <div className="p-6 space-y-4">
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Margen genético (%)
                            </label>
                            <input
                                type="number"
                                value={valores.porcentaje_margen_genetico}
                                onChange={(e) => set('porcentaje_margen_genetico', e.target.value)}
                                min="0"
                                max="500"
                                step="0.01"
                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                disabled={guardando}
                            />
                            {margenExcedido && (
                                <p className={`mt-1 text-xs flex items-start gap-1 ${puedeMargenExtendido ? 'text-amber-600' : 'text-red-600'}`}>
                                    <AlertTriangle className="w-3 h-3 mt-0.5 shrink-0" />
                                    {puedeMargenExtendido
                                        ? 'Superar 100 % requiere justificación escrita.'
                                        : 'Solo un administrador puede aplicar más de 100 % de margen.'}
                                </p>
                            )}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Estado reproductivo
                            </label>
                            <select
                                value={valores.estado_reproductivo_valuacion}
                                onChange={(e) => set('estado_reproductivo_valuacion', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                disabled={guardando}
                            >
                                {estadosReproductivos.map((estado) => (
                                    <option key={estado} value={estado}>
                                        {ETIQUETA_ESTADO[estado] || estado}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Plus reproductivo (MXN)
                            </label>
                            <input
                                type="number"
                                value={valores.plus_reproductivo}
                                onChange={(e) => set('plus_reproductivo', e.target.value)}
                                min="0"
                                step="0.01"
                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                disabled={guardando}
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Ajuste manual (MXN)
                            </label>
                            <input
                                type="number"
                                value={valores.ajuste_manual}
                                onChange={(e) => set('ajuste_manual', e.target.value)}
                                step="0.01"
                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                disabled={guardando}
                            />
                        </div>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Justificación {requiereMotivo && <span className="text-red-600">*</span>}
                        </label>
                        <textarea
                            value={valores.motivo_ajuste}
                            onChange={(e) => set('motivo_ajuste', e.target.value)}
                            rows="2"
                            placeholder="Explica por qué aplicas este ajuste..."
                            className={`w-full border rounded-lg px-3 py-2 ${faltaMotivo ? 'border-red-300' : 'border-gray-300'}`}
                            disabled={guardando}
                        />
                        {faltaMotivo && (
                            <p className="mt-1 text-xs text-red-600">
                                El ajuste manual solo se aplica con una justificación escrita.
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Precio objetivo (opcional)
                        </label>
                        <input
                            type="number"
                            value={precioObjetivo}
                            onChange={(e) => setPrecioObjetivo(e.target.value)}
                            step="0.01"
                            placeholder="¿A cuánto quieres llegar?"
                            className="w-full border border-gray-300 rounded-lg px-3 py-2"
                            disabled={guardando}
                        />
                    </div>

                    <button
                        onClick={simular}
                        className="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium px-4 py-2 rounded-lg disabled:opacity-50"
                        disabled={cargando || guardando}
                    >
                        {cargando ? 'Calculando...' : 'Calcular simulación'}
                    </button>

                    {error && (
                        <div className="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">
                            {error}
                        </div>
                    )}

                    {simulado && (
                        <div className="bg-emerald-50 border border-emerald-200 rounded-lg p-4 space-y-2">
                            <div className="flex justify-between text-sm">
                                <span className="text-slate-600">Costo de producción</span>
                                <span className="font-semibold tabular-nums">{formatMXN(simulado.costo_total_produccion)}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-slate-600">Valor del margen</span>
                                <span className="font-semibold tabular-nums">{formatMXN(simulado.valor_margen_genetico)}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-slate-600">Plus reproductivo</span>
                                <span className="font-semibold tabular-nums">{formatMXN(simulado.plus_reproductivo)}</span>
                            </div>
                            <div className="flex justify-between items-baseline border-t border-emerald-200 pt-2">
                                <span className="text-sm font-bold text-emerald-900">Precio simulado</span>
                                <span className="text-lg font-bold text-emerald-700 tabular-nums">
                                    {formatMXN(simulado.precio_estimado)}
                                </span>
                            </div>

                            <p className="text-xs text-slate-500">
                                Cotización actual: {formatMXN(precioActual)}
                                {diferencia != null && (
                                    <span className={diferencia >= 0 ? ' text-emerald-700' : ' text-red-600'}>
                                        {' '}({diferencia >= 0 ? '+' : ''}{formatMXN(diferencia)})
                                    </span>
                                )}
                            </p>

                            {!isNaN(objetivo) && precioObjetivo !== '' && (
                                <p className="text-xs text-slate-600">
                                    Frente a tu precio objetivo de {formatMXN(objetivo)}:{' '}
                                    <span className={precioSimulado >= objetivo ? 'text-emerald-700 font-medium' : 'text-amber-700 font-medium'}>
                                        {precioSimulado >= objetivo
                                            ? `lo alcanzas con ${formatMXN(precioSimulado - objetivo)} de margen`
                                            : `te faltan ${formatMXN(objetivo - precioSimulado)}`}
                                    </span>
                                </p>
                            )}
                        </div>
                    )}
                </div>

                <div className="flex flex-wrap justify-end gap-3 p-6 border-t sticky bottom-0 bg-white">
                    <button
                        onClick={restablecer}
                        className="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 flex items-center gap-2 disabled:opacity-50"
                        disabled={guardando}
                    >
                        <RotateCcw className="w-4 h-4" /> Restablecer valores
                    </button>
                    <button
                        onClick={() => guardar('borrador')}
                        className="px-4 py-2 text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50 disabled:opacity-50"
                        disabled={guardando || faltaMotivo}
                    >
                        Guardar como borrador
                    </button>
                    <button
                        onClick={() => guardar('activa')}
                        className="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 flex items-center gap-2 disabled:opacity-50"
                        disabled={guardando || faltaMotivo}
                    >
                        <Save className="w-4 h-4" />
                        {guardando ? 'Guardando...' : 'Guardar cotización'}
                    </button>
                </div>
            </div>
        </div>
    );
}
