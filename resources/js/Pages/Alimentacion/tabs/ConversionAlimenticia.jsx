import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function ConversionAlimenticia() {
    const { porAnimal = [], porLote = [], fechaInicio='', fechaFin='', flash = {} } = usePage().props;

    const [tab, setTab]                   = useState('animal'); // 'animal' | 'lote'
    const [loteAbierto, setLoteAbierto]   = useState(null);
    const [fechas, setFechas]             = useState({ inicio: fechaInicio|| '', fin: fechaFin|| '' });

    // ─── Filtrar fechas ───────────────────────────────────────────────────────
    const handleFiltrar = (e) => {
        e.preventDefault();
        router.get(route('conversion.index'), {
            fecha_inicio: fechas.inicio,
            fecha_fin:    fechas.fin,
        }, { preserveState: true });
    };

    // ─── Helpers de formato ───────────────────────────────────────────────────
    const fmt     = (v, dec = 2) => (v != null ? Number(v).toFixed(dec) : '—');
    const fmtPeso = (v)          => (v != null ? `${fmt(v)} kg` : '—');
    const fmtCost = (v)          => (v != null ? `$${fmt(v)}` : '—');

    const badgeCA = (ca) => {
        if (ca == null) return 'bg-gray-100 text-gray-500 border-gray-200';
        if (ca <= 6)    return 'bg-green-50 text-green-700 border-green-200';
        if (ca <= 10)   return 'bg-yellow-50 text-yellow-700 border-yellow-200';
        return 'bg-red-50 text-red-700 border-red-200';
    };

    const badgeGanancia = (g) => {
        if (g == null) return null;
        if (g > 0)  return 'text-green-700';
        if (g < 0)  return 'text-red-600';
        return 'text-gray-500';
    };

    // ─── Render ───────────────────────────────────────────────────────────────
    return (
        <div className="space-y-5">

            {/* Encabezado */}
            <div>
           
                <h2 className="text-xl font-semibold text-gray-800">Conversión Alimenticia</h2>
                <p className="text-sm text-gray-500">
                    Relación entre alimento consumido y ganancia de peso por animal o lote.
                </p>
                <p className="text-sm text-gray-500 mb-4">
                Kg de alimento por animal al mes (menor es mejor).
            </p>
            </div>

            {/* Flash */}
            {flash?.success && (
                <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {flash.success}
                </div>
            )}

            {/* Filtro de fechas */}
            <form
                onSubmit={handleFiltrar}
                className="flex flex-wrap items-end gap-3 rounded-xl border border-blue-100 bg-white p-4 shadow-sm"
            >
                <div>
                    <label className="mb-1 block text-xs font-medium text-gray-600">Fecha inicio</label>
                    <input
                        type="date"
                        className="rounded-md border border-gray-300 text-sm focus:border-blue-300 focus:ring-blue-200"
                        value={fechas.inicio|| ''}
                        onChange={e => setFechas(f => ({ ...f, inicio: e.target.value }))}
                    />
                </div>
                <div>
                    <label className="mb-1 block text-xs font-medium text-gray-600">Fecha fin</label>
                    <input
                        type="date"
                        className="rounded-md border border-gray-300 text-sm focus:border-blue-300 focus:ring-blue-200"
                        value={fechas.fin|| ''}
                        onChange={e => setFechas(f => ({ ...f, fin: e.target.value }))}
                    />
                </div>
                <button
                    type="submit"
                    className="rounded-md bg-blue-600 px-4 py-2 text-xs text-white hover:bg-blue-700"
                >
                    Aplicar
                </button>
            </form>

            {/* Aviso sin pesajes */}
            {porAnimal.every(a => !a.tiene_datos_peso) && porAnimal.length > 0 && (
                <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    No hay pesajes registrados en este período. La conversión alimenticia (CA) requiere al menos
                    dos pesajes por animal. Puedes registrarlos en el módulo de <strong>Pesajes</strong>.
                </div>
            )}

            {/* Tabs */}
            <div className="flex border-b border-gray-200">
                {[
                    { key: 'animal', label: `Por animal (${porAnimal.length})` },
                    { key: 'lote',   label: `Por lote (${porLote.length})` },
                ].map(t => (
                    <button
                        key={t.key}
                        type="button"
                        onClick={() => setTab(t.key)}
                        className={`px-5 py-2.5 text-sm font-medium border-b-2 transition-colors ${
                            tab === t.key
                                ? 'border-blue-600 text-blue-700'
                                : 'border-transparent text-gray-500 hover:text-gray-700'
                        }`}
                    >
                        {t.label}
                    </button>
                ))}
            </div>

            {/* ── Tab: Por Animal ────────────────────────────────────────────── */}
            {tab === 'animal' && (
                <div className="space-y-3">
                    {porAnimal.length === 0 ? (
                        <p className="text-sm text-gray-400">No hay consumos registrados en este período.</p>
                    ) : (
                        <div className="overflow-x-auto rounded-xl border border-blue-50 shadow-sm">
                            <table className="w-full text-xs">
                                <thead className="bg-gray-50 text-left text-gray-500">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">Animal</th>
                                        <th className="px-4 py-3 font-medium">Lote</th>
                                        <th className="px-4 py-3 font-medium text-right">Kg consumidos</th>
                                        <th className="px-4 py-3 font-medium text-right">Costo total</th>
                                        <th className="px-4 py-3 font-medium text-right">Peso inicio</th>
                                        <th className="px-4 py-3 font-medium text-right">Peso fin</th>
                                        <th className="px-4 py-3 font-medium text-right">Ganancia</th>
                                        <th className="px-4 py-3 font-medium text-center">CA</th>
                                        <th className="px-4 py-3 font-medium text-right">$/kg ganancia</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 bg-white">
                                    {porAnimal.map((row, i) => (
                                        <tr key={i} className="hover:bg-gray-50">
                                            <td className="px-4 py-3">
                                                <span className="font-semibold text-gray-800">{row.animal.arete}</span>
                                                {row.animal.alias && (
                                                    <span className="ml-1 text-gray-400">({row.animal.alias})</span>
                                                )}
                                                <div className="text-[11px] text-gray-400">
                                                    {row.animal.especie}{row.animal.raza ? ` · ${row.animal.raza}` : ''}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-gray-500">
                                                {row.animal.lote ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 text-right text-gray-700">
                                                {fmtPeso(row.kg_total)}
                                                {row.kg_lote > 0 && (
                                                    <div className="text-[11px] text-gray-400">
                                                        directo: {fmt(row.kg_directo)} · lote: {fmt(row.kg_lote)}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-right text-gray-700">
                                                {fmtCost(row.costo_total)}
                                            </td>
                                            <td className="px-4 py-3 text-right text-gray-600">
                                                {fmtPeso(row.peso_inicio)}
                                            </td>
                                            <td className="px-4 py-3 text-right text-gray-600">
                                                {fmtPeso(row.peso_fin)}
                                            </td>
                                            <td className={`px-4 py-3 text-right font-medium ${badgeGanancia(row.ganancia_peso)}`}>
                                                {row.ganancia_peso != null
                                                    ? `${row.ganancia_peso >= 0 ? '+' : ''}${fmt(row.ganancia_peso)} kg`
                                                    : <span className="text-gray-300 font-normal">Sin pesajes</span>
                                                }
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                {row.conversion != null ? (
                                                    <span className={`rounded-full border px-2 py-0.5 text-[11px] font-medium ${badgeCA(row.conversion)}`}>
                                                        {fmt(row.conversion)}
                                                    </span>
                                                ) : (
                                                    <span className="text-gray-300 text-[11px]">—</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-right text-gray-700">
                                                {fmtCost(row.costo_kg_ganancia)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {/* Leyenda CA */}
                    <div className="flex flex-wrap gap-3 text-[11px] text-gray-500">
                        <span>CA = kg alimento / kg ganancia de peso</span>
                        <span className="rounded-full border border-green-200 bg-green-50 px-2 py-0.5 text-green-700">≤ 6 Excelente</span>
                        <span className="rounded-full border border-yellow-200 bg-yellow-50 px-2 py-0.5 text-yellow-700">7–10 Aceptable</span>
                        <span className="rounded-full border border-red-200 bg-red-50 px-2 py-0.5 text-red-700"> 10 Revisar</span>
                    </div>
                </div>
            )}

            {/* ── Tab: Por Lote ──────────────────────────────────────────────── */}
            {tab === 'lote' && (
                <div className="space-y-3">
                    {porLote.length === 0 ? (
                        <p className="text-sm text-gray-400">No hay consumos por lote en este período.</p>
                    ) : (
                        porLote.map((row, i) => {
                            const abierto = loteAbierto === row.lote.id;

                            return (
                                <div key={i} className="rounded-xl border border-blue-50 bg-white shadow-sm overflow-hidden">

                                    {/* Cabecera del lote */}
                                    <div className="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
                                        <div className="space-y-1">
                                            <div className="flex items-center gap-2 flex-wrap">
                                                <h3 className="text-sm font-semibold text-gray-900">{row.lote.nombre}</h3>
                                                <span className="text-xs text-gray-400">
                                                    {row.num_animales} animal(es) · {row.animales_con_pesaje} con pesajes
                                                </span>
                                            </div>

                                            {/* Métricas del lote */}
                                            <div className="flex flex-wrap gap-4 text-xs text-gray-600">
                                                <span>
                                                    Consumo total: <strong className="text-gray-800">{fmtPeso(row.kg_total)}</strong>
                                                    {row.kg_lote_directo > 0 && (
                                                        <span className="ml-1 text-gray-400">
                                                            (lote: {fmt(row.kg_lote_directo)} · individual: {fmt(row.kg_individual)})
                                                        </span>
                                                    )}
                                                </span>
                                                <span>
                                                    Costo: <strong className="text-gray-800">{fmtCost(row.costo_total)}</strong>
                                                </span>
                                                {row.ganancia_total !== 0 && (
                                                    <span className={badgeGanancia(row.ganancia_total)}>
                                                        Ganancia total: <strong>{row.ganancia_total >= 0 ? '+' : ''}{fmt(row.ganancia_total)} kg</strong>
                                                    </span>
                                                )}
                                            </div>
                                        </div>

                                        {/* Badges CA */}
                                        <div className="flex items-center gap-2 flex-wrap">
                                            {row.conversion != null && (
                                                <div className="text-center">
                                                    <div className="text-[10px] text-gray-400 mb-0.5">CA lote</div>
                                                    <span className={`rounded-full border px-3 py-1 text-xs font-semibold ${badgeCA(row.conversion)}`}>
                                                        {fmt(row.conversion)}
                                                    </span>
                                                </div>
                                            )}
                                            {row.costo_kg_ganancia != null && (
                                                <div className="text-center">
                                                    <div className="text-[10px] text-gray-400 mb-0.5">$/kg ganancia</div>
                                                    <span className="rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-medium text-gray-700">
                                                        {fmtCost(row.costo_kg_ganancia)}
                                                    </span>
                                                </div>
                                            )}
                                            <button
                                                type="button"
                                                onClick={() => setLoteAbierto(abierto ? null : row.lote.id)}
                                                className="rounded-md border border-gray-200 px-4 py-2 text-xs text-gray-600 hover:bg-gray-50"
                                            >
                                                {abierto ? 'Ocultar detalle' : 'Ver por animal'}
                                            </button>
                                        </div>
                                    </div>

                                    {/* Detalle por animal dentro del lote */}
                                    {abierto && (
                                        <div className="border-t border-gray-100 bg-gray-50 overflow-x-auto">
                                            <table className="w-full text-xs">
                                                <thead className="text-left text-gray-400 border-b border-gray-200">
                                                    <tr>
                                                        <th className="px-4 py-2 font-medium">Animal</th>
                                                        <th className="px-4 py-2 font-medium text-right">Kg consumidos</th>
                                                        <th className="px-4 py-2 font-medium text-right">Costo</th>
                                                        <th className="px-4 py-2 font-medium text-right">Peso inicio</th>
                                                        <th className="px-4 py-2 font-medium text-right">Peso fin</th>
                                                        <th className="px-4 py-2 font-medium text-right">Ganancia</th>
                                                        <th className="px-4 py-2 font-medium text-center">CA</th>
                                                        <th className="px-4 py-2 font-medium text-right">$/kg ganancia</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-gray-100">
                                                    {row.detalle_animales.map((a, j) => (
                                                        <tr key={j} className="bg-white hover:bg-gray-50">
                                                            <td className="px-4 py-2">
                                                                <span className="font-medium text-gray-800">{a.arete}</span>
                                                                {a.alias && <span className="ml-1 text-gray-400">({a.alias})</span>}
                                                            </td>
                                                            <td className="px-4 py-2 text-right text-gray-700">{fmtPeso(a.kg_total)}</td>
                                                            <td className="px-4 py-2 text-right text-gray-700">{fmtCost(a.costo_total)}</td>
                                                            <td className="px-4 py-2 text-right text-gray-500">{fmtPeso(a.peso_inicio)}</td>
                                                            <td className="px-4 py-2 text-right text-gray-500">{fmtPeso(a.peso_fin)}</td>
                                                            <td className={`px-4 py-2 text-right font-medium ${badgeGanancia(a.ganancia)}`}>
                                                                {a.ganancia != null
                                                                    ? `${a.ganancia >= 0 ? '+' : ''}${fmt(a.ganancia)} kg`
                                                                    : <span className="text-gray-300 font-normal">Sin pesajes</span>
                                                                }
                                                            </td>
                                                            <td className="px-4 py-2 text-center">
                                                                {a.conversion != null ? (
                                                                    <span className={`rounded-full border px-2 py-0.5 text-[11px] font-medium ${badgeCA(a.conversion)}`}>
                                                                        {fmt(a.conversion)}
                                                                    </span>
                                                                ) : (
                                                                    <span className="text-gray-300">—</span>
                                                                )}
                                                            </td>
                                                            <td className="px-4 py-2 text-right text-gray-700">{fmtCost(a.costo_kg_ganancia)}</td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    )}
                                </div>
                            );
                        })
                    )}

                    {/* Leyenda */}
                    <div className="flex flex-wrap gap-3 text-[11px] text-gray-500">
                        <span>CA = kg alimento / kg ganancia de peso</span>
                        <span className="rounded-full border border-green-200 bg-green-50 px-2 py-0.5 text-green-700">≤ 6 Excelente</span>
                        <span className="rounded-full border border-yellow-200 bg-yellow-50 px-2 py-0.5 text-yellow-700">7-10 Aceptable</span>
                        <span className="rounded-full border border-red-200 bg-red-50 px-2 py-0.5 text-red-700"> 10 Revisar</span>
                    </div>
                </div>
            )}
        </div>
    );
}