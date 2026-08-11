import React, { useEffect, useMemo, useState } from "react";
import { Filter, Scale, MousePointerClick } from "lucide-react";
import {
    LineChart, Line, XAxis, YAxis, CartesianGrid,
    Tooltip, ResponsiveContainer,
} from "recharts";
import { usePreferences } from "@/Contexts/PreferencesContext";

const CHART_COLORS = [
    "#3b82f6","#10b981","#f59e0b","#ef4444",
    "#8b5cf6","#06b6d4","#f97316","#84cc16",
];

// ─── Helpers puros ────────────────────────────────────────────────────────────

function calcularGananciaEnRango(animal, fechaInicio, fechaFin) {
    const sorted = [...(animal.pesajes || [])].sort((a, b) => a.fecha.localeCompare(b.fecha));
    const antesFin = sorted.filter((p) => p.fecha <= fechaFin);
    if (!antesFin.length) return null;
    
const ultimoPesaje = antesFin[antesFin.length - 1];

const antesInicio = sorted.filter((p) => p.fecha < fechaInicio);
const pesajeInicio = antesInicio[antesInicio.length - 1];

const primerPesaje = sorted[0];
const pesoInicio = pesajeInicio? parseFloat(pesajeInicio.peso): primerPesaje   ? parseFloat(primerPesaje.peso): null;
if (pesoInicio == null || !ultimoPesaje) return null;

const fechaBase = pesajeInicio?.fecha || primerPesaje?.fecha;

const pesoFin = parseFloat(ultimoPesaje.peso);

const ganancia = Math.round((pesoFin - pesoInicio) * 100) / 100;

const dias = fechaBase? Math.max( 0, Math.round( (new Date(ultimoPesaje.fecha) - new Date(fechaBase)) / 86400000    ) ) : 0;

const gdp = dias > 0? Math.round((ganancia / dias) * 1000) / 1000: 0;

return { pesoInicio,pesoFin, ganancia,gdp,dias};
function buildChartData(animals, fechaInicio, fechaFin) {
    const fechas = [...new Set(
        animals.flatMap((a) =>
            (a.pesajes || [])
                .filter((p) => p.fecha >= fechaInicio && p.fecha <= fechaFin)
                .map((p) => p.fecha)
        )
    )].sort();

    return fechas.map((fecha) => {
        const point = { fecha };
        animals.forEach((a) => {
            const p = (a.pesajes || []).find((x) => x.fecha === fecha);
            if (p) point[a.arete] = parseFloat(p.peso);
        });
        return point;
    });
}
}
// ─── Tooltip y leyenda personalizados (más legibles con varias líneas) ───────

function ChartTooltip({ active, payload, label, formatWeight, ocultas }) {
    if (!active || !payload?.length) return null;

    const visibles = payload
        .filter((p) => p.value != null && !ocultas.has(p.dataKey))
        .sort((a, b) => b.value - a.value);

    if (!visibles.length) return null;

    const [y, m, d] = String(label).split("-");

    return (
        <div className="rounded-xl border border-gray-200 bg-white p-3 text-xs shadow-lg">
            <p className="mb-2 font-semibold text-gray-700">{`${d}/${m}/${y}`}</p>
            <div className="space-y-1.5">
                {visibles.map((p) => (
                    <div key={p.dataKey} className="flex items-center justify-between gap-6">
                        <span className="flex items-center gap-1.5 text-gray-600">
                            <span
                                className="h-2 w-2 rounded-full"
                                style={{ backgroundColor: p.color }}
                            />
                            {p.dataKey}
                        </span>
                        <span className="font-medium text-gray-800">
                            {formatWeight(p.value)}
                        </span>
                    </div>
                ))}
            </div>
        </div>
    );
}

function ChartLegend({ animales, colores, ocultas, hover, onToggle, onHover }) {
    return (
        <div className="mt-3 flex flex-wrap gap-2">
            {animales.map((a, i) => {
                const oculto  = ocultas.has(a.arete);
                const activo  = hover === a.arete;
                return (
                    <button
                        key={a.id}
                        type="button"
                        onClick={() => onToggle(a.arete)}
                        onMouseEnter={() => onHover(a.arete)}
                        onMouseLeave={() => onHover(null)}
                        className={`flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] transition ${
                            oculto
                                ? "border-gray-200 bg-gray-50 text-gray-400 line-through"
                                : activo
                                ? "border-gray-300 bg-gray-100 text-gray-800 shadow-sm"
                                : "border-gray-200 bg-white text-gray-600 hover:bg-gray-50"
                        }`}
                    >
                        <span
                            className="h-2.5 w-2.5 rounded-full"
                            style={{ backgroundColor: oculto ? "#d1d5db" : colores[i % colores.length] }}
                        />
                        {a.arete}
                    </button>
                );
            })}
        </div>
    );
}

// ─── Componente ───────────────────────────────────────────────────────────────

function TabGanancia({ animales = [] }) {
    const { formatWeight, weightUnit } = usePreferences();

    const hoy    = new Date().toISOString().split("T")[0];
    const hace30 = new Date(Date.now() - 30 * 86400000).toISOString().split("T")[0];
    const [gFechaInicio, setGFechaInicio] = useState(hace30);
    const [gFechaFin,    setGFechaFin]    = useState(hoy);
    const [gEspecie,     setGEspecie]     = useState("");
    const [gRaza,        setGRaza]        = useState("");
    const [gLote,        setGLote]        = useState("");
    const [gAnimal,      setGAnimal]      = useState("");

    // Estado solo de la gráfica: ocultar series o resaltarlas al pasar el cursor
    const [seriesOcultas, setSeriesOcultas] = useState(() => new Set());
    const [seriesHover,   setSeriesHover]   = useState(null);

    const filterClass = "rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring focus:ring-blue-100";

    const fmtPeso       = (v) => formatWeight(v);
    const fmtPesoDiario = (v) => v == null ? "—" : `${formatWeight(v)}/día`;

    const badgeGanancia = (valor) => {
        if (valor > 0) return "border-emerald-200 bg-emerald-50 text-emerald-700";
        if (valor < 0) return "border-red-200 bg-red-50 text-red-700";
        return "border-gray-200 bg-gray-50 text-gray-600";
    };

    const rangoFechasInvalido = gFechaInicio && gFechaFin && gFechaInicio > gFechaFin;

    const especies = useMemo(
        () => [...new Set(animales.map((a) => a.especie).filter(Boolean))].sort(),
        [animales]
    );
    const razas = useMemo(() => {
        const base = gEspecie ? animales.filter((a) => a.especie === gEspecie) : animales;
        return [...new Set(base.map((a) => a.raza).filter(Boolean))].sort();
    }, [animales, gEspecie]);
    const lotes = useMemo(
        () => [...new Set(animales.map((a) => a.lote?.nombre).filter(Boolean))].sort(),
        [animales]
    );

    const animalesFiltradosGanancia = useMemo(() => animales.filter((a) => {
        if (gEspecie && a.especie !== gEspecie)         return false;
        if (gRaza    && a.raza    !== gRaza)             return false;
        if (gLote    && a.lote?.nombre !== gLote)        return false;
        if (gAnimal  && String(a.id)   !== gAnimal)      return false;
        return true;
    }), [animales, gEspecie, gRaza, gLote, gAnimal]);

    const ganancias = useMemo(() =>
        animalesFiltradosGanancia
            .map((a) => ({ animal: a, ...calcularGananciaEnRango(a, gFechaInicio, gFechaFin) }))
            .filter((r) => r.ganancia != null)
            .sort((a, b) => (b.ganancia ?? -Infinity) - (a.ganancia ?? -Infinity)),
    [animalesFiltradosGanancia, gFechaInicio, gFechaFin]);

    const sinDatos = useMemo(
        () => animalesFiltradosGanancia.filter(
            (a) => !calcularGananciaEnRango(a, gFechaInicio, gFechaFin)
        ),
        [animalesFiltradosGanancia, gFechaInicio, gFechaFin]
    );

    const promedioGdp = useMemo(() => {
        const conGdp = ganancias.filter((r) => r.gdp != null);
        if (!conGdp.length) return null;
        return Math.round((conGdp.reduce((s, r) => s + r.gdp, 0) / conGdp.length) * 1000) / 1000;
    }, [ganancias]);

    const mejorAnimal = useMemo(() => ganancias[0] ?? null, [ganancias]);

    const animalesParaChart = useMemo(
        () => animalesFiltradosGanancia
            .filter((a) => (a.pesajes || []).some((p) => p.fecha >= gFechaInicio && p.fecha <= gFechaFin))
            .slice(0, 8),
        [animalesFiltradosGanancia, gFechaInicio, gFechaFin]
    );

    const chartData = useMemo(
        () => buildChartData(animalesParaChart, gFechaInicio, gFechaFin),
        [animalesParaChart, gFechaInicio, gFechaFin]
    );

    // Si cambian los filtros/rango, la selección de la leyenda ya no aplica
    useEffect(() => {
        setSeriesOcultas(new Set());
        setSeriesHover(null);
    }, [gEspecie, gRaza, gLote, gAnimal, gFechaInicio, gFechaFin]);

    const toggleSerie = (arete) => {
        setSeriesOcultas((prev) => {
            const next = new Set(prev);
            if (next.has(arete)) next.delete(arete);
            else next.add(arete);
            return next;
        });
    };

    return (
        <div className="space-y-5">
            {/* FILTROS */}
            <div className="rounded-2xl border border-gray-100 bg-white p-5 shadow">
                <div className="mb-4 flex items-center gap-2 text-sm font-semibold text-gray-700">
                    <Filter size={15} className="text-blue-600" /> Filtros
                </div>

                <div className="flex flex-wrap gap-3">
                    {[
                        {
                            label: "Fecha inicio",
                            type: "date",
                            value: gFechaInicio,
                            onChange: (v) => setGFechaInicio(v),
                        },
                        {
                            label: "Fecha fin",
                            type: "date",
                            value: gFechaFin,
                            onChange: (v) => setGFechaFin(v),
                        },
                    ].map(({ label, type, value, onChange }) => (
                        <div key={label}>
                            <label className="mb-1 block text-xs font-medium text-gray-500">
                                {label}
                            </label>
                            <input
                                type={type}
                                className={filterClass}
                                value={value}
                                onChange={(e) => onChange(e.target.value)}
                            />
                        </div>
                    ))}

                    <div>
                        <label className="mb-1 block text-xs font-medium text-gray-500">
                            Especie
                        </label>
                        <select
                            className={filterClass}
                            value={gEspecie}
                            onChange={(e) => {
                                setGEspecie(e.target.value);
                                setGRaza("");
                            }}
                        >
                            <option value="">Todas</option>
                            {especies.map((e) => (
                                <option key={e} value={e}>
                                    {e}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="mb-1 block text-xs font-medium text-gray-500">
                            Raza
                        </label>
                        <select
                            className={filterClass}
                            value={gRaza}
                            onChange={(e) => setGRaza(e.target.value)}
                            disabled={!razas.length}
                        >
                            <option value="">Todas</option>
                            {razas.map((r) => (
                                <option key={r} value={r}>
                                    {r}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="mb-1 block text-xs font-medium text-gray-500">
                            Lote
                        </label>
                        <select
                            className={filterClass}
                            value={gLote}
                            onChange={(e) => setGLote(e.target.value)}
                        >
                            <option value="">Todos</option>
                            {lotes.map((l) => (
                                <option key={l} value={l}>
                                    {l}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="mb-1 block text-xs font-medium text-gray-500">
                            Animal específico
                        </label>
                        <select
                            className={filterClass}
                            value={gAnimal}
                            onChange={(e) => setGAnimal(e.target.value)}
                        >
                            <option value="">Todos</option>
                            {animales.map((a) => (
                                <option key={a.id} value={a.id}>
                                    {a.arete}
                                    {a.alias ? ` (${a.alias})` : ""}
                                </option>
                            ))}
                        </select>
                    </div>

                    {(gEspecie || gRaza || gLote || gAnimal) && (
                        <div className="flex items-end">
                            <button
                                type="button"
                                onClick={() => {
                                    setGEspecie("");
                                    setGRaza("");
                                    setGLote("");
                                    setGAnimal("");
                                }}
                                className="rounded-xl border border-gray-200 px-3 py-2 text-xs text-gray-500 hover:bg-gray-50"
                            >
                                Limpiar filtros
                            </button>
                        </div>
                    )}
                </div>
            </div>

            {/* ALERTA RANGO INVÁLIDO */}
            {rangoFechasInvalido && (
                <div className="rounded-xl border border-red-100 bg-red-50 p-4 text-sm text-red-700">
                    La fecha de inicio no puede ser mayor que la fecha fin.
                </div>
            )}

            {/* TARJETAS RESUMEN */}
            {ganancias.length > 0 && (
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {[
                        {
                            label: "Con datos en período",
                            value: `${ganancias.length} / ${animalesFiltradosGanancia.length}`,
                            sub: `${sinDatos.length} sin pesajes`,
                            border: "border-blue-500",
                        },
                        {
                            label: "GDP promedio del grupo",
                            value:
                                promedioGdp != null
                                    ? fmtPesoDiario(promedioGdp)
                                    : "—",
                            sub: "ganancia diaria promedio",
                            border: "border-emerald-500",
                        },
                        {
                            label: "Mejor ganancia",
                            value: mejorAnimal
                                ? `+${formatWeight(mejorAnimal.ganancia)}`
                                : "—",
                            sub: mejorAnimal?.animal.arete ?? "",
                            border: "border-green-500",
                        },
                        {
                            label: "Sin datos",
                            value: sinDatos.length,
                            sub: "animales sin pesajes suficientes",
                            border: "border-amber-500",
                        },
                    ].map((c, i) => (
                        <div
                            key={i}
                            className={`bg-white rounded-2xl shadow p-5 border-l-4 ${c.border}`}
                        >
                            <p className="text-sm text-gray-500">{c.label}</p>
                            <p className="text-2xl font-bold text-gray-800 mt-1">
                                {c.value}
                            </p>
                            {c.sub && (
                                <p className="text-xs text-gray-400 mt-1">{c.sub}</p>
                            )}
                        </div>
                    ))}
                </div>
            )}

            {/* GRÁFICA */}
            {chartData.length > 1 && (
                <div className="rounded-2xl border border-gray-100 bg-white p-5 shadow">
                    <div className="mb-1 flex flex-wrap items-center justify-between gap-2">
                        <h3 className="text-sm font-semibold text-gray-700">
                            Curva de peso en el período
                        </h3>

                        {animalesParaChart.length === 8 && (
                            <span className="text-[11px] text-gray-400">
                                Primeros 8 animales · filtra por animal para ver individualmente
                            </span>
                        )}
                    </div>

                    <p className="mb-3 flex items-center gap-1.5 text-[11px] text-gray-400">
                        <MousePointerClick size={12} />
                        Pasa el cursor sobre un animal en la leyenda para resaltarlo, o haz clic para ocultarlo.
                    </p>

                    <ResponsiveContainer width="100%" height={300}>
                        <LineChart
                            data={chartData}
                            margin={{ top: 5, right: 20, left: 0, bottom: 5 }}
                        >
                            <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                            <XAxis
                                dataKey="fecha"
                                tick={{ fontSize: 11 }}
                                minTickGap={28}
                                tickFormatter={(f) => {
                                    const [, m, d] = f.split("-");
                                    return `${d}/${m}`;
                                }}
                            />
                            <YAxis
                                tick={{ fontSize: 11 }}
                                tickFormatter={(v) => formatWeight(v, { digits: 0 })}
                                width={72}
                                label={{
                                    value: `Peso (${weightUnit})`,
                                    angle: -90,
                                    position: "insideLeft",
                                    style: { fontSize: 11, fill: "#9ca3af" },
                                }}
                            />
                            <Tooltip
                                content={
                                    <ChartTooltip
                                        formatWeight={formatWeight}
                                        ocultas={seriesOcultas}
                                    />
                                }
                            />
                            {animalesParaChart.map((a, i) => {
                                const color     = CHART_COLORS[i % CHART_COLORS.length];
                                const oculta    = seriesOcultas.has(a.arete);
                                const atenuada  = seriesHover && seriesHover !== a.arete;
                                return (
                                    <Line
                                        key={a.id}
                                        type="monotone"
                                        dataKey={a.arete}
                                        stroke={color}
                                        strokeWidth={seriesHover === a.arete ? 3 : 2}
                                        strokeOpacity={atenuada ? 0.15 : 1}
                                        dot={atenuada ? false : { r: 3 }}
                                        activeDot={{ r: 6 }}
                                        connectNulls
                                        hide={oculta}
                                    />
                                );
                            })}
                        </LineChart>
                    </ResponsiveContainer>

                    <ChartLegend
                        animales={animalesParaChart}
                        colores={CHART_COLORS}
                        ocultas={seriesOcultas}
                        hover={seriesHover}
                        onToggle={toggleSerie}
                        onHover={setSeriesHover}
                    />
                </div>
            )}

            {/* TABLA */}
            {ganancias.length > 0 ? (
                <div className="overflow-x-auto rounded-2xl border border-gray-100 bg-white shadow">
                    <table className="w-full text-xs">
                        <thead className="bg-gray-50 text-left text-gray-500">
                            <tr>
                                <th className="px-4 py-3 font-medium">Animal</th>
                                <th className="px-4 py-3 font-medium">Especie / Raza</th>
                                <th className="px-4 py-3 font-medium">Lote</th>
                                <th className="px-4 py-3 font-medium text-right">Peso inicio</th>
                                <th className="px-4 py-3 font-medium text-right">Peso fin</th>
                                <th className="px-4 py-3 font-medium text-right">Ganancia</th>
                                <th className="px-4 py-3 font-medium text-right">GDP</th>
                                <th className="px-4 py-3 font-medium text-right">Días</th>
                            </tr>
                        </thead>

                        <tbody className="divide-y divide-gray-100">
                            {ganancias.map((row, i) => (
                                <tr key={i} className="hover:bg-gray-50">
                                    <td className="px-4 py-3">
                                        <span className="font-semibold text-gray-800">
                                            {row.animal.arete}
                                        </span>
                                        {row.animal.alias && (
                                            <span className="ml-1 text-gray-400">
                                                ({row.animal.alias})
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-gray-500">
                                        {row.animal.especie}
                                        {row.animal.raza ? ` · ${row.animal.raza}` : ""}
                                    </td>
                                    <td className="px-4 py-3 text-gray-500">
                                        {row.animal.lote?.nombre ?? "—"}
                                    </td>
                                    <td className="px-4 py-3 text-right text-gray-700">
                                        {fmtPeso(row.pesoInicio)}
                                    </td>
                                    <td className="px-4 py-3 text-right text-gray-700">
                                        {fmtPeso(row.pesoFin)}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <span
                                            className={`rounded-full border px-2 py-0.5 font-medium ${badgeGanancia(
                                                row.ganancia
                                            )}`}
                                        >
                                            {row.ganancia >= 0 ? "+" : ""}
                                            {formatWeight(row.ganancia)}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-right text-gray-700">
                                        {row.gdp != null
                                            ? fmtPesoDiario(row.gdp)
                                            : "—"}
                                    </td>
                                    <td className="px-4 py-3 text-right text-gray-400">
                                        {row.dias ?? "—"}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : (
                <div className="rounded-2xl border border-gray-100 bg-white p-10 text-center shadow">
                    <Scale size={32} className="mx-auto mb-3 text-gray-300" />
                    <p className="text-sm font-medium text-gray-500">
                        No hay animales con pesajes en este período.
                    </p>
                    <p className="mt-1 text-xs text-gray-400">
                        Ajusta las fechas o registra pesajes en la pestaña Animales.
                    </p>
                </div>
            )}

            {/* SIN DATOS */}
            {sinDatos.length > 0 && (
                <div className="rounded-2xl border border-amber-100 bg-amber-50 p-4">
                    <p className="mb-2 text-xs font-semibold text-amber-700">
                        Sin pesajes suficientes en el período ({sinDatos.length}):
                    </p>
                    <div className="flex flex-wrap gap-2">
                        {sinDatos.map((a) => (
                            <span
                                key={a.id}
                                className="rounded-full border border-amber-200 bg-white px-2 py-0.5 text-xs text-amber-700"
                            >
                                {a.arete}
                                {a.alias ? ` (${a.alias})` : ""}
                            </span>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}

export default TabGanancia;