import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { BarChart3, Info, AlertTriangle, Bell } from 'lucide-react';
import { formatMXN } from '@/utils/currency';

/** Indicador con su fórmula visible, para que el número sea auditable. */
function Indicador({ etiqueta, valor, sufijo = '', formula, ausente = 'Sin datos' }) {
    const [verFormula, setVerFormula] = useState(false);
    const hayValor = valor !== null && valor !== undefined;

    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-4">
            <div className="flex items-start justify-between gap-2">
                <p className="text-xs font-medium text-slate-500">{etiqueta}</p>
                {formula && (
                    <button
                        onClick={() => setVerFormula((v) => !v)}
                        className="text-slate-300 hover:text-slate-500 shrink-0"
                        aria-label={`Ver fórmula de ${etiqueta}`}
                    >
                        <Info className="w-3.5 h-3.5" />
                    </button>
                )}
            </div>

            <p className={`mt-2 text-2xl font-semibold tabular-nums ${hayValor ? 'text-slate-900' : 'text-slate-300'}`}>
                {hayValor ? `${valor}${sufijo}` : ausente}
            </p>

            {verFormula && formula && (
                <p className="mt-2 text-[11px] text-slate-500 bg-slate-50 rounded-lg p-2">{formula}</p>
            )}
        </div>
    );
}

function Seccion({ titulo, children }) {
    return (
        <div className="space-y-3">
            <h2 className="text-sm font-semibold text-slate-900">{titulo}</h2>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">{children}</div>
        </div>
    );
}

const COLOR_NIVEL = {
    critica: 'bg-red-50 border-red-200 text-red-800',
    atencion: 'bg-amber-50 border-amber-200 text-amber-800',
    informativa: 'bg-slate-50 border-slate-200 text-slate-700',
};

export default function ReportesOvinos({ auth, resumen, costosPorLote = [], alertas = [], etapas = {}, filtros = {}, formulas = {} }) {
    const [locales, setLocales] = useState({ desde: filtros.desde || '', hasta: filtros.hasta || '' });

    const aplicar = (cambios) => {
        const nuevos = { ...locales, ...cambios };
        setLocales(nuevos);
        router.get(route('reportes.ovinos'), nuevos, { preserveState: true, preserveScroll: true, replace: true });
    };

    const inv = resumen?.inventario || {};
    const rep = resumen?.reproduccion || {};
    const des = resumen?.desarrollo || {};
    const eco = resumen?.economico || {};
    const sal = resumen?.salidas || {};

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800">Reportes del rebaño</h2>}
        >
            <Head title="Reportes ovinos" />

            <div className="py-8 px-4 sm:px-6 max-w-6xl mx-auto space-y-8">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <BarChart3 className="w-7 h-7 text-emerald-600" />
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900">Indicadores del rebaño</h1>
                            <p className="text-sm text-slate-600">
                                Calculados en el servidor. Toca el ícono de información para ver cada fórmula.
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <input
                            type="date"
                            value={locales.desde}
                            onChange={(e) => aplicar({ desde: e.target.value })}
                            className="border border-slate-300 rounded-lg px-2 py-2 text-sm"
                            aria-label="Desde"
                        />
                        <input
                            type="date"
                            value={locales.hasta}
                            onChange={(e) => aplicar({ hasta: e.target.value })}
                            className="border border-slate-300 rounded-lg px-2 py-2 text-sm"
                            aria-label="Hasta"
                        />
                        <button
                            onClick={() => { setLocales({ desde: '', hasta: '' }); router.get(route('reportes.ovinos'), {}, { preserveState: true, replace: true }); }}
                            className="text-sm text-slate-500 hover:text-slate-800 underline"
                        >
                            Todo el historial
                        </button>
                    </div>
                </div>

                {/* Alertas operativas */}
                {alertas.length > 0 && (
                    <div className="space-y-2">
                        <h2 className="text-sm font-semibold text-slate-900 flex items-center gap-2">
                            <Bell className="w-4 h-4" /> Trabajo pendiente en el rebaño
                        </h2>
                        {alertas.map((a, i) => (
                            <div key={i} className={`rounded-xl border px-4 py-3 flex items-start gap-3 ${COLOR_NIVEL[a.nivel]}`}>
                                <AlertTriangle className="w-4 h-4 shrink-0 mt-0.5" />
                                <div className="min-w-0 flex-1">
                                    <p className="text-sm font-medium">
                                        {a.titulo} <span className="font-bold">({a.cantidad})</span>
                                    </p>
                                    <p className="text-xs opacity-90">{a.detalle}</p>
                                </div>
                                {a.ruta && (
                                    <Link href={a.ruta} className="text-xs underline shrink-0 self-center">Ir →</Link>
                                )}
                            </div>
                        ))}
                    </div>
                )}

                <Seccion titulo="Inventario del rebaño">
                    <Indicador etiqueta="Ovinos activos" valor={inv.total_activos} />
                    <Indicador etiqueta="Borregas reproductoras" valor={inv.borregas_reproductoras} />
                    <Indicador etiqueta="Sementales" valor={inv.sementales} />
                    <Indicador etiqueta="Sin identificación" valor={inv.sin_identificador} />
                </Seccion>

                <Seccion titulo="Reproducción">
                    <Indicador etiqueta="Gestaciones activas" valor={rep.gestaciones_activas} />
                    <Indicador etiqueta="Partos próximos (21 días)" valor={rep.partos_proximos} />
                    <Indicador etiqueta="Partos ocurridos" valor={rep.partos_ocurridos} />
                    <Indicador etiqueta="Crías nacidas" valor={rep.crias_nacidas} />
                    <Indicador etiqueta="Crías vivas" valor={rep.crias_vivas} />
                    <Indicador etiqueta="Crías muertas" valor={rep.crias_muertas} />
                    <Indicador
                        etiqueta="Prolificidad"
                        valor={rep.prolificidad}
                        sufijo=" crías/parto"
                        formula={formulas.prolificidad}
                    />
                    <Indicador
                        etiqueta="Fertilidad"
                        valor={rep.porcentaje_fertilidad}
                        sufijo=" %"
                        formula={formulas.porcentaje_fertilidad}
                    />
                    <Indicador
                        etiqueta="Gestación"
                        valor={rep.porcentaje_gestacion}
                        sufijo=" %"
                        formula={formulas.porcentaje_gestacion}
                        ausente="Sin diagnósticos"
                    />
                    <Indicador
                        etiqueta="Supervivencia de crías"
                        valor={rep.porcentaje_supervivencia_crias}
                        sufijo=" %"
                        formula={formulas.porcentaje_supervivencia_crias}
                    />
                </Seccion>

                <Seccion titulo="Desarrollo corporal">
                    <Indicador etiqueta="Peso promedio" valor={des.peso_promedio} sufijo=" kg" />
                    <Indicador
                        etiqueta="Ganancia diaria promedio"
                        valor={des.ganancia_diaria_promedio}
                        sufijo=" kg/día"
                        formula={formulas.ganancia_diaria_promedio}
                    />
                    <Indicador etiqueta="Con pesaje registrado" valor={des.ejemplares_con_pesaje} />
                    <Indicador etiqueta="Sin pesaje" valor={des.sin_pesaje} />
                </Seccion>

                {des.peso_promedio_por_etapa && Object.keys(des.peso_promedio_por_etapa).length > 0 && (
                    <div className="bg-white rounded-2xl border border-slate-200 p-5">
                        <h3 className="text-sm font-semibold text-slate-900 mb-3">Peso promedio por etapa</h3>
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead>
                                    <tr className="text-slate-500 text-xs">
                                        <th className="text-left py-2">Etapa</th>
                                        <th className="text-right py-2">Ejemplares</th>
                                        <th className="text-right py-2">Peso promedio</th>
                                    </tr>
                                </thead>
                                <tbody className="text-slate-700">
                                    {Object.entries(des.peso_promedio_por_etapa).map(([etapa, datos]) => (
                                        <tr key={etapa} className="border-t border-slate-100">
                                            <td className="py-2">{etapas[etapa] || etapa}</td>
                                            <td className="py-2 text-right tabular-nums">{datos.ejemplares}</td>
                                            <td className="py-2 text-right tabular-nums font-medium">{datos.promedio} kg</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                <Seccion titulo="Salidas del rebaño">
                    <Indicador etiqueta="Salidas en el periodo" valor={sal.bajas_periodo} />
                    <Indicador etiqueta="Fallecimientos" valor={sal.fallecimientos} />
                    <Indicador
                        etiqueta="Mortalidad"
                        valor={sal.porcentaje_mortalidad}
                        sufijo=" %"
                        formula={formulas.porcentaje_mortalidad}
                    />
                    <Indicador etiqueta="Ingresos por salidas" valor={sal.ingresos_por_salidas != null ? formatMXN(sal.ingresos_por_salidas) : null} />
                </Seccion>

                <Seccion titulo="Económico">
                    <Indicador etiqueta="Costos totales" valor={eco.costos_totales != null ? formatMXN(eco.costos_totales) : null} />
                    <Indicador
                        etiqueta="Costo por ejemplar"
                        valor={eco.costo_promedio_por_ejemplar != null ? formatMXN(eco.costo_promedio_por_ejemplar) : null}
                        formula={formulas.costo_promedio_por_ejemplar}
                    />
                    <Indicador etiqueta="Ingresos por ventas" valor={eco.ingresos != null ? formatMXN(eco.ingresos) : null} />
                    <Indicador
                        etiqueta="Utilidad"
                        valor={eco.utilidad != null ? formatMXN(eco.utilidad) : null}
                        formula={formulas.porcentaje_utilidad}
                    />
                    <Indicador etiqueta="Precio estimado del rebaño" valor={eco.precio_estimado_rebano != null ? formatMXN(eco.precio_estimado_rebano) : null} />
                </Seccion>

                {costosPorLote.length > 0 && (
                    <div className="bg-white rounded-2xl border border-slate-200 p-5">
                        <h3 className="text-sm font-semibold text-slate-900 mb-3">Costos por lote</h3>
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead>
                                    <tr className="text-slate-500 text-xs">
                                        <th className="text-left py-2">Lote</th>
                                        <th className="text-right py-2">Movimientos</th>
                                        <th className="text-right py-2">Total</th>
                                    </tr>
                                </thead>
                                <tbody className="text-slate-700">
                                    {costosPorLote.map((fila, i) => (
                                        <tr key={i} className="border-t border-slate-100">
                                            <td className="py-2">{fila.lote || 'Sin lote'}</td>
                                            <td className="py-2 text-right tabular-nums">{fila.movimientos}</td>
                                            <td className="py-2 text-right tabular-nums font-medium">{formatMXN(fila.total)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                <p className="text-xs text-slate-400">
                    Los indicadores que aparecen como «Sin datos» no se calcularon porque falta información
                    de base. El sistema no rellena esos huecos con ceros.
                </p>
            </div>
        </AuthenticatedLayout>
    );
}
