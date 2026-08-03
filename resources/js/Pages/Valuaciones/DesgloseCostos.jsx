import React, { useState } from 'react';
import { ChevronDown, ChevronRight, Info } from 'lucide-react';
import { formatMXN } from '@/utils/currency';

const BUCKETS = [
    { key: 'costo_gestacion', categoria: 'gestacion', label: 'Costo de gestación asignado' },
    { key: 'costo_inicial', categoria: 'inicial', label: 'Costo de nacimiento o adquisición' },
    { key: 'costo_sanitario', categoria: 'sanitario', label: 'Costos sanitarios' },
    { key: 'costo_alimentacion', categoria: 'alimentacion', label: 'Alimentación' },
    { key: 'costo_registro', categoria: 'registro', label: 'Registro de pureza' },
    { key: 'costo_mano_obra', categoria: 'mano_obra', label: 'Mano de obra' },
    { key: 'costo_transporte', categoria: 'transporte', label: 'Transporte' },
    { key: 'otros_costos', categoria: 'otros', label: 'Otros costos' },
];

function fmtFecha(fecha) {
    if (!fecha) return '—';
    return new Date(fecha).toLocaleDateString('es-MX');
}

export default function DesgloseCostos({ calculo }) {
    const [abierto, setAbierto] = useState({});
    const buckets = calculo?.buckets || {};
    const detalles = calculo?.detalles || [];

    const toggle = (categoria) => setAbierto((prev) => ({ ...prev, [categoria]: !prev[categoria] }));

    const detallesDe = (categoria) => detalles.filter((d) => d.categoria === categoria);

    return (
        <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div className="px-5 py-4 border-b border-slate-200">
                <h2 className="text-sm font-semibold text-slate-900">Desglose del precio</h2>
                <p className="text-xs text-slate-500">
                    Abre cualquier concepto para ver los movimientos que lo componen.
                </p>
            </div>

            <div className="divide-y divide-slate-100">
                {BUCKETS.map((bucket) => {
                    const monto = Number(buckets[bucket.key] ?? 0);
                    const lineas = detallesDe(bucket.categoria);
                    const expandible = lineas.length > 0;
                    const estaAbierto = abierto[bucket.categoria];

                    return (
                        <div key={bucket.key}>
                            <button
                                type="button"
                                onClick={() => expandible && toggle(bucket.categoria)}
                                disabled={!expandible}
                                className={`w-full flex items-center justify-between px-5 py-3 text-left transition ${
                                    expandible ? 'hover:bg-slate-50 cursor-pointer' : 'cursor-default'
                                }`}
                            >
                                <span className="flex items-center gap-2 text-sm text-slate-700">
                                    {expandible ? (
                                        estaAbierto ? <ChevronDown className="w-4 h-4 text-slate-400" />
                                                    : <ChevronRight className="w-4 h-4 text-slate-400" />
                                    ) : (
                                        <span className="w-4" />
                                    )}
                                    {bucket.label}
                                    {expandible && (
                                        <span className="text-xs text-slate-400">({lineas.length})</span>
                                    )}
                                </span>
                                <span className="text-sm font-semibold text-slate-900 tabular-nums">
                                    {formatMXN(monto)}
                                </span>
                            </button>

                            {estaAbierto && expandible && (
                                <div className="bg-slate-50 px-5 pb-4 overflow-x-auto">
                                    <table className="min-w-full text-xs">
                                        <thead>
                                            <tr className="text-slate-500">
                                                <th className="text-left py-2 pr-4 font-medium">Fecha</th>
                                                <th className="text-left py-2 pr-4 font-medium">Concepto</th>
                                                <th className="text-right py-2 pr-4 font-medium">Cantidad</th>
                                                <th className="text-right py-2 pr-4 font-medium">Costo unitario</th>
                                                <th className="text-right py-2 pr-4 font-medium">Total</th>
                                                <th className="text-left py-2 font-medium">Cómo se calculó</th>
                                            </tr>
                                        </thead>
                                        <tbody className="text-slate-700">
                                            {lineas.map((linea, i) => (
                                                <tr key={i} className="border-t border-slate-200">
                                                    <td className="py-2 pr-4 whitespace-nowrap">{fmtFecha(linea.fecha)}</td>
                                                    <td className="py-2 pr-4">
                                                        {linea.concepto}
                                                        {linea.descripcion && (
                                                            <span className="block text-slate-400">{linea.descripcion}</span>
                                                        )}
                                                    </td>
                                                    <td className="py-2 pr-4 text-right tabular-nums">
                                                        {linea.cantidad != null
                                                            ? `${Number(linea.cantidad).toFixed(2)} ${linea.unidad || ''}`
                                                            : '—'}
                                                    </td>
                                                    <td className="py-2 pr-4 text-right tabular-nums">
                                                        {linea.costo_unitario != null ? formatMXN(linea.costo_unitario) : '—'}
                                                    </td>
                                                    <td className="py-2 pr-4 text-right tabular-nums font-semibold">
                                                        {formatMXN(linea.costo_total)}
                                                    </td>
                                                    <td className="py-2 text-slate-500">
                                                        {linea.metodo_distribucion || linea.observaciones || '—'}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>

            {/* Totales */}
            <div className="border-t-2 border-slate-200 px-5 py-3 flex items-center justify-between">
                <span className="text-sm font-semibold text-slate-900">Costo total de producción</span>
                <span className="text-sm font-bold text-slate-900 tabular-nums">
                    {formatMXN(calculo?.costo_total_produccion)}
                </span>
            </div>

            <div className="divide-y divide-slate-100">
                <div className="px-5 py-3 flex items-center justify-between">
                    <span className="text-sm text-slate-700">
                        Margen genético
                        <span className="ml-2 text-xs text-slate-400">
                            {Number(calculo?.porcentaje_margen_genetico ?? 0).toFixed(2)} %
                        </span>
                    </span>
                    <span className="text-sm font-semibold text-slate-900 tabular-nums">
                        {formatMXN(calculo?.valor_margen_genetico)}
                    </span>
                </div>
                <div className="px-5 py-3 flex items-center justify-between">
                    <span className="text-sm text-slate-700">
                        Plus reproductivo
                        {calculo?.estado_reproductivo_valuacion && (
                            <span className="ml-2 text-xs text-slate-400">
                                {calculo.estado_reproductivo_valuacion.replaceAll('_', ' ')}
                            </span>
                        )}
                    </span>
                    <span className="text-sm font-semibold text-slate-900 tabular-nums">
                        {formatMXN(calculo?.plus_reproductivo)}
                    </span>
                </div>
                <div className="px-5 py-3 flex items-center justify-between">
                    <span className="text-sm text-slate-700">Ajuste manual</span>
                    <span className="text-sm font-semibold text-slate-900 tabular-nums">
                        {formatMXN(calculo?.ajuste_manual)}
                    </span>
                </div>
            </div>

            {calculo?.motivo_ajuste && (
                <div className="px-5 pb-3 flex gap-2 text-xs text-slate-500">
                    <Info className="w-4 h-4 shrink-0 mt-0.5" />
                    <span>Justificación del ajuste: {calculo.motivo_ajuste}</span>
                </div>
            )}

            <div className="border-t-2 border-emerald-200 bg-emerald-50 px-5 py-4 flex items-center justify-between">
                <span className="text-sm font-bold text-emerald-900">Precio final estimado</span>
                <span className="text-xl font-bold text-emerald-700 tabular-nums">
                    {formatMXN(calculo?.precio_estimado)}
                </span>
            </div>
        </div>
    );
}
