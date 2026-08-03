import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { History, TrendingUp, TrendingDown, Minus } from 'lucide-react';
import { formatMXN } from '@/utils/currency';

const ETIQUETA_TIPO = {
    creacion: 'Creación',
    recalculo: 'Recálculo',
    nuevo_gasto: 'Nuevo gasto',
    cambio_margen: 'Cambio de margen',
    cambio_reproductivo: 'Cambio reproductivo',
    ajuste_manual: 'Ajuste manual',
    confirmacion_venta: 'Confirmación de venta',
};

function fmtFechaHora(valor) {
    if (!valor) return '—';
    return new Date(valor).toLocaleString('es-MX', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

export default function HistorialPrecio({ animal, historial = [], tiposMovimiento = [], filtros = {} }) {
    const [locales, setLocales] = useState({
        desde: filtros.desde || '',
        hasta: filtros.hasta || '',
        tipo_movimiento: filtros.tipo_movimiento || '',
    });

    const aplicar = (cambios) => {
        const nuevos = { ...locales, ...cambios };
        setLocales(nuevos);
        router.get(route('valuaciones.show', animal.id), nuevos, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const limpiar = () => {
        setLocales({ desde: '', hasta: '', tipo_movimiento: '' });
        router.get(route('valuaciones.show', animal.id), {}, { preserveState: true, preserveScroll: true, replace: true });
    };

    return (
        <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div className="px-5 py-4 border-b border-slate-200 flex items-center gap-2">
                <History className="w-4 h-4 text-slate-500" />
                <div>
                    <h2 className="text-sm font-semibold text-slate-900">Historial del precio</h2>
                    <p className="text-xs text-slate-500">
                        Cada cambio se conserva como un movimiento nuevo; nada se sobrescribe.
                    </p>
                </div>
            </div>

            <div className="px-5 py-3 border-b border-slate-100 grid grid-cols-2 sm:grid-cols-4 gap-2">
                <input
                    type="date"
                    value={locales.desde}
                    onChange={(e) => aplicar({ desde: e.target.value })}
                    className="border border-slate-300 rounded-lg px-2 py-1.5 text-xs text-slate-700"
                    aria-label="Desde"
                />
                <input
                    type="date"
                    value={locales.hasta}
                    onChange={(e) => aplicar({ hasta: e.target.value })}
                    className="border border-slate-300 rounded-lg px-2 py-1.5 text-xs text-slate-700"
                    aria-label="Hasta"
                />
                <select
                    value={locales.tipo_movimiento}
                    onChange={(e) => aplicar({ tipo_movimiento: e.target.value })}
                    className="border border-slate-300 rounded-lg px-2 py-1.5 text-xs text-slate-700"
                    aria-label="Tipo de movimiento"
                >
                    <option value="">Todos los movimientos</option>
                    {tiposMovimiento.map((t) => (
                        <option key={t} value={t}>{ETIQUETA_TIPO[t] || t}</option>
                    ))}
                </select>
                <button
                    onClick={limpiar}
                    className="text-xs text-slate-500 hover:text-slate-800 underline"
                >
                    Limpiar filtros
                </button>
            </div>

            {historial.length === 0 ? (
                <p className="px-5 py-8 text-center text-sm text-slate-400">
                    Todavía no hay movimientos de precio para este animal.
                </p>
            ) : (
                <ul className="divide-y divide-slate-100">
                    {historial.map((mov) => {
                        const diferencia = Number(mov.diferencia ?? 0);
                        const Icono = diferencia > 0 ? TrendingUp : diferencia < 0 ? TrendingDown : Minus;
                        const color = diferencia > 0 ? 'text-emerald-600' : diferencia < 0 ? 'text-red-600' : 'text-slate-400';

                        return (
                            <li key={mov.id} className="px-5 py-4">
                                <div className="flex flex-wrap items-start justify-between gap-2">
                                    <div className="min-w-0">
                                        <p className="text-xs text-slate-400">{fmtFechaHora(mov.created_at)}</p>
                                        <p className="text-sm font-medium text-slate-800">
                                            {ETIQUETA_TIPO[mov.tipo_movimiento] || mov.tipo_movimiento}
                                            {mov.concepto && <span className="text-slate-500"> — {mov.concepto}</span>}
                                        </p>
                                        {mov.motivo && (
                                            <p className="text-xs text-slate-500 mt-0.5">{mov.motivo}</p>
                                        )}
                                        <p className="text-xs text-slate-400 mt-1">
                                            Modificado por: {mov.usuario?.name || 'Sistema'}
                                        </p>
                                    </div>

                                    <div className="text-right shrink-0">
                                        <p className="text-xs text-slate-400">
                                            {mov.precio_anterior != null ? formatMXN(mov.precio_anterior) : '—'}
                                            {' → '}
                                        </p>
                                        <p className="text-sm font-semibold text-slate-900 tabular-nums">
                                            {formatMXN(mov.precio_nuevo)}
                                        </p>
                                        <p className={`text-xs font-medium flex items-center justify-end gap-1 ${color}`}>
                                            <Icono className="w-3 h-3" />
                                            {diferencia >= 0 ? '+' : ''}{formatMXN(diferencia)}
                                        </p>
                                    </div>
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}
        </div>
    );
}
