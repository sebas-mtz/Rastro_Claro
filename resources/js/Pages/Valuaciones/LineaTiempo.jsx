import React from 'react';
import { formatMXN } from '@/utils/currency';

const ETIQUETA_CATEGORIA = {
    gestacion: 'Gestación',
    nacimiento: 'Nacimiento',
    inicial: 'Nacimiento o adquisición',
    sanitario: 'Sanidad',
    alimentacion: 'Alimentación',
    registro: 'Registro de pureza',
    mano_obra: 'Mano de obra',
    transporte: 'Transporte',
    otros: 'Otros',
};

const COLOR_CATEGORIA = {
    gestacion: 'bg-purple-500',
    nacimiento: 'bg-emerald-600',
    inicial: 'bg-emerald-500',
    sanitario: 'bg-teal-500',
    alimentacion: 'bg-amber-500',
    registro: 'bg-blue-500',
    mano_obra: 'bg-slate-500',
    transporte: 'bg-orange-500',
    otros: 'bg-slate-400',
};

function fmtFecha(fecha) {
    if (!fecha) return '—';
    return new Date(fecha).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

export default function LineaTiempo({ hitos = [], precioEstimado }) {
    return (
        <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div className="px-5 py-4 border-b border-slate-200">
                <h2 className="text-sm font-semibold text-slate-900">Línea de tiempo</h2>
                <p className="text-xs text-slate-500">De la gestación al precio actual.</p>
            </div>

            {hitos.length === 0 ? (
                <p className="px-5 py-8 text-center text-sm text-slate-400">
                    No hay eventos con fecha registrados todavía.
                </p>
            ) : (
                <ol className="px-5 py-4 space-y-0">
                    {hitos.map((hito, i) => (
                        <li key={i} className="relative flex gap-4 pb-5 last:pb-0">
                            {/* Línea vertical */}
                            {i !== hitos.length - 1 && (
                                <span className="absolute left-[7px] top-4 bottom-0 w-px bg-slate-200" aria-hidden="true" />
                            )}

                            <span
                                className={`relative z-10 mt-1 w-[15px] h-[15px] rounded-full border-2 border-white shrink-0 ${
                                    COLOR_CATEGORIA[hito.categoria] || 'bg-slate-400'
                                }`}
                                aria-hidden="true"
                            />

                            <div className="min-w-0 flex-1 flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                                <div className="min-w-0">
                                    <p className="text-xs text-slate-400">
                                        {fmtFecha(hito.fecha)} · {ETIQUETA_CATEGORIA[hito.categoria] || hito.categoria}
                                    </p>
                                    <p className="text-sm text-slate-800">{hito.concepto}</p>
                                    {hito.metodo && (
                                        <p className="text-xs text-slate-400 mt-0.5">{hito.metodo}</p>
                                    )}
                                </div>
                                {hito.costo != null && (
                                    <span className="text-sm font-medium text-slate-700 tabular-nums shrink-0">
                                        {formatMXN(hito.costo)}
                                    </span>
                                )}
                            </div>
                        </li>
                    ))}

                    {/* Cierre: cotización actual */}
                    <li className="relative flex gap-4">
                        <span className="relative z-10 mt-1 w-[15px] h-[15px] rounded-full border-2 border-white bg-emerald-700 shrink-0" aria-hidden="true" />
                        <div className="flex flex-wrap items-baseline justify-between gap-x-4 flex-1">
                            <div>
                                <p className="text-xs text-slate-400">Hoy</p>
                                <p className="text-sm font-semibold text-emerald-800">Cotización actual</p>
                            </div>
                            <span className="text-sm font-bold text-emerald-700 tabular-nums">
                                {formatMXN(precioEstimado)}
                            </span>
                        </div>
                    </li>
                </ol>
            )}
        </div>
    );
}
