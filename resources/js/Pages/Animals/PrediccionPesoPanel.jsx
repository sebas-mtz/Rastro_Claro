import React, { useMemo } from 'react';
import {
    ComposedChart, Area, Line, Scatter, XAxis, YAxis,
    CartesianGrid, Tooltip, Legend, ResponsiveContainer, ReferenceLine,
} from 'recharts';
import { TrendingUp, Info, AlertTriangle } from 'lucide-react';

/**
 * Proyección del peso del ejemplar.
 *
 * El cálculo vive en el backend (App\Services\PrediccionPesoService), como el
 * resto de las cuentas del sistema. Aquí solo se presenta.
 */

function fmtFecha(f) {
    if (!f) return '';
    const [a, m, d] = f.split('-');
    return `${d}/${m}/${a.slice(2)}`;
}

function Tarjeta({ etiqueta, valor, detalle, acento = 'text-slate-900' }) {
    return (
        <div className="rounded-xl border border-slate-200 bg-white p-3">
            <p className="text-xs font-medium text-slate-500">{etiqueta}</p>
            <p className={`mt-1 text-xl font-semibold tabular-nums ${acento}`}>{valor}</p>
            {detalle && <p className="text-xs text-slate-400 mt-0.5">{detalle}</p>}
        </div>
    );
}

export default function PrediccionPesoPanel({ prediccion }) {
    // Sin datos suficientes el backend manda null y aquí no se pinta nada:
    // más vale no mostrar la sección que mostrar una curva inventada.
    if (!prediccion) return null;

    const datos = useMemo(() => {
        const historico = prediccion.serie.map((p) => ({
            fecha: p.fecha,
            real: p.real,
            ajuste: p.ajuste,
        }));

        // El último punto real arranca también la proyección, para que las dos
        // líneas se toquen en vez de aparecer separadas.
        const ultimo = historico[historico.length - 1];

        if (ultimo) {
            ultimo.proyeccion = ultimo.ajuste;
            ultimo.banda = [ultimo.ajuste, ultimo.ajuste];
        }

        const futuro = prediccion.futuro.map((p) => ({
            fecha: p.fecha,
            proyeccion: p.peso,
            banda: [p.min, p.max],
        }));

        return [...historico, ...futuro];
    }, [prediccion]);

    const gdp = prediccion.gdp_media;
    const reciente = prediccion.gdp_reciente;

    // Si el último tramo va muy por debajo de su promedio, el animal se está
    // acercando a su techo. Es un dato de manejo, no una alarma.
    const frenando = reciente !== null && gdp > 0 && reciente < gdp * 0.7;

    const corteFecha = prediccion.serie[prediccion.serie.length - 1]?.fecha;

    return (
        <div className="space-y-5">
            <div>
                <h3 className="font-semibold text-slate-800 flex items-center gap-2">
                    <TrendingUp className="w-5 h-5 text-slate-400" /> Proyección de peso
                </h3>
                <p className="text-sm text-slate-600">
                    Estimación a partir de sus {prediccion.n_pesajes} pesajes,
                    tomados a lo largo de {prediccion.periodo_dias} días.
                </p>
            </div>

            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <Tarjeta
                    etiqueta="Peso actual"
                    valor={`${prediccion.peso_actual} kg`}
                />
                <Tarjeta
                    etiqueta="Ganancia media"
                    valor={`${Math.round(gdp * 1000)} g/día`}
                    detalle="de toda su vida"
                />
                <Tarjeta
                    etiqueta="Último tramo"
                    valor={reciente !== null ? `${Math.round(reciente * 1000)} g/día` : '—'}
                    detalle={frenando ? 'se está frenando' : 'cómo va ahora'}
                    acento={frenando ? 'text-amber-600' : 'text-slate-900'}
                />
                <Tarjeta
                    etiqueta="Modelo"
                    valor={prediccion.modelo}
                    detalle={`R² ${prediccion.r2} · ±${prediccion.rmse} kg`}
                />
            </div>

            {prediccion.aviso && (
                <p className="text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-start gap-2">
                    <AlertTriangle className="w-4 h-4 mt-0.5 shrink-0" />
                    <span>{prediccion.aviso}</span>
                </p>
            )}

            <div className="bg-white rounded-2xl border border-slate-200 p-4">
                <ResponsiveContainer width="100%" height={320}>
                    <ComposedChart data={datos} margin={{ top: 10, right: 20, bottom: 5, left: 0 }}>
                        <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" />
                        <XAxis
                            dataKey="fecha"
                            tickFormatter={fmtFecha}
                            tick={{ fontSize: 11, fill: '#64748b' }}
                            minTickGap={28}
                        />
                        <YAxis
                            tick={{ fontSize: 11, fill: '#64748b' }}
                            label={{ value: 'kg', angle: -90, position: 'insideLeft', fontSize: 11, fill: '#64748b' }}
                        />
                        <Tooltip
                            labelFormatter={fmtFecha}
                            formatter={(valor, nombre) => {
                                if (Array.isArray(valor)) {
                                    return [`${valor[0]} – ${valor[1]} kg`, 'Margen'];
                                }
                                return [`${valor} kg`, nombre];
                            }}
                            contentStyle={{ borderRadius: 10, borderColor: '#e2e8f0', fontSize: 12 }}
                        />
                        <Legend wrapperStyle={{ fontSize: 12 }} />

                        {/* Margen de error de la proyección */}
                        <Area
                            dataKey="banda"
                            name="Margen"
                            stroke="none"
                            fill="#10b981"
                            fillOpacity={0.14}
                            connectNulls
                        />

                        {/* Curva ajustada sobre el tramo con datos */}
                        <Line
                            type="monotone"
                            dataKey="ajuste"
                            name={`Modelo ${prediccion.modelo}`}
                            stroke="#0ea5e9"
                            strokeWidth={2}
                            dot={false}
                            connectNulls
                        />

                        {/* Proyección */}
                        <Line
                            type="monotone"
                            dataKey="proyeccion"
                            name="Proyección"
                            stroke="#10b981"
                            strokeWidth={2}
                            strokeDasharray="6 4"
                            dot={false}
                            connectNulls
                        />

                        {/* Pesajes registrados */}
                        <Scatter dataKey="real" name="Pesajes" fill="#1e293b" />

                        {/* La distinción más importante de la gráfica:
                            dónde acaba lo medido y empieza lo proyectado. */}
                        {corteFecha && (
                            <ReferenceLine
                                x={corteFecha}
                                stroke="#94a3b8"
                                strokeDasharray="2 3"
                                label={{ value: 'hoy', position: 'top', fontSize: 10, fill: '#64748b' }}
                            />
                        )}
                    </ComposedChart>
                </ResponsiveContainer>
            </div>

            <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <table className="min-w-full text-sm">
                    <thead className="bg-slate-50">
                        <tr>
                            <th className="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Plazo</th>
                            <th className="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Fecha</th>
                            <th className="px-4 py-2 text-right text-xs font-medium text-slate-500 uppercase">Peso estimado</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {prediccion.hitos.map((h) => (
                            <tr key={h.dias}>
                                <td className="px-4 py-2 text-slate-700">{h.dias} días</td>
                                <td className="px-4 py-2 text-slate-600">{fmtFecha(h.fecha)}</td>
                                <td className="px-4 py-2 text-right font-medium text-slate-800 tabular-nums">
                                    {h.peso} kg
                                    <span className="text-slate-400 font-normal ml-1">±{h.margen}</span>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <p className="text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-lg p-3 flex items-start gap-2">
                <Info className="w-4 h-4 mt-0.5 shrink-0 text-slate-400" />
                <span>
                    Es una proyección estadística sobre los pesajes registrados. No considera
                    cambios de alimentación, sanidad ni clima, y se vuelve menos fiable cuanto
                    más lejos se proyecta — por eso el margen se ensancha.
                </span>
            </p>
        </div>
    );
}
