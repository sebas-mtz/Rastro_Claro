import { useEffect, useRef, useState } from 'react';
import axios from 'axios';
import {
    ClipboardList, CheckCircle2, Clock, AlertTriangle,
    Pill, Syringe, Stethoscope, Activity,
} from 'lucide-react';

/* ─── Chart.js loader (mismo patrón que Reproducción) ───────────────────── */
function buildChartScript(cb) {
    if (window.Chart) return cb(window.Chart);
    const existing = document.getElementById('chartjs-cdn');
    if (existing) {
        existing.addEventListener('load', () => cb(window.Chart));
        return;
    }
    const s = document.createElement('script');
    s.id = 'chartjs-cdn';
    s.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js';
    s.onload = () => cb(window.Chart);
    document.head.appendChild(s);
}

function destroyChart(ref) {
    if (ref.current) {
        ref.current.destroy();
        ref.current = null;
    }
}

/* ─── Paleta semántica ───────────────────────────────────────────────────── */
const PALETTE = {
    aplicada:   '#16a34a',
    pendiente:  '#ca8a04',
    vencida:    '#dc2626',
    vacunacion: '#2563eb',
    consulta:   '#7c3aed',
    revision:   '#0891b2',
    emergencia: '#ea580c',
    gris:       '#9ca3af',
};

const TIPO_LABEL = {
    vacunacion: 'Vacunación',
    consulta:   'Consulta',
    revision:   'Revisión',
    emergencia: 'Emergencia',
};

const ESTADO_LABEL = {
    aplicada:  'Aplicada',
    pendiente: 'Pendiente',
    vencida:   'Vencida',
};

/* ─── Tarjeta KPI (idéntica a Reproducción) ─────────────────────────────── */
function KpiCard({ label, value, suffix = '', sub, color = PALETTE.aplicada, icon: Icon, alert }) {
    return (
        <div className={`bg-white rounded-2xl border p-5 flex flex-col gap-2 shadow-sm hover:shadow-md transition-shadow ${alert ? 'border-red-200' : 'border-gray-100'}`}>
            <div className="flex items-center justify-between">
                <span className="text-xs font-semibold uppercase tracking-widest text-gray-400">{label}</span>
                {Icon && (
                    <span className="w-8 h-8 rounded-xl flex items-center justify-center" style={{ background: color + '18' }}>
                        <Icon size={16} style={{ color }} />
                    </span>
                )}
            </div>
            <div className="flex items-end gap-1">
                <span className="text-3xl font-bold tracking-tight text-gray-900">{value}</span>
                {suffix && <span className="text-base font-semibold mb-1" style={{ color }}>{suffix}</span>}
            </div>
            {sub && <p className={`text-xs leading-snug ${alert ? 'text-red-500 font-medium' : 'text-gray-400'}`}>{sub}</p>}
        </div>
    );
}

/* ─── Gauge SVG (idéntico a Reproducción) ───────────────────────────────── */
function Gauge({ pct = 0, label, color = PALETTE.aplicada }) {
    const r = 54, cx = 70, cy = 70;
    const circumference = Math.PI * r;
    const offset = circumference * (1 - pct / 100);
    return (
        <div className="flex flex-col items-center gap-1">
            <svg width="140" height="80" viewBox="0 0 140 80">
                <path
                    d={`M ${cx - r} ${cy} A ${r} ${r} 0 0 1 ${cx + r} ${cy}`}
                    fill="none" stroke="#f3f4f6" strokeWidth="12" strokeLinecap="round"
                />
                <path
                    d={`M ${cx - r} ${cy} A ${r} ${r} 0 0 1 ${cx + r} ${cy}`}
                    fill="none" stroke={color} strokeWidth="12" strokeLinecap="round"
                    strokeDasharray={circumference}
                    strokeDashoffset={offset}
                    style={{ transition: 'stroke-dashoffset 1s ease' }}
                />
                <text x={cx} y={cy - 4} textAnchor="middle"
                    style={{ fontFamily: 'system-ui', fill: '#111827', fontSize: 22, fontWeight: 700 }}>
                    {pct}%
                </text>
            </svg>
            <span className="text-xs text-gray-500 font-medium text-center">{label}</span>
        </div>
    );
}

/* ─── Doughnut: eventos por estado ──────────────────────────────────────── */
function DonutEstado({ por_estado }) {
    const canvasRef = useRef(null);
    const chartRef  = useRef(null);

    useEffect(() => {
        buildChartScript(Chart => {
            destroyChart(chartRef);
            const ctx = canvasRef.current?.getContext('2d');
            if (!ctx) return;
            const entries = Object.entries(por_estado);
            chartRef.current = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: entries.map(([k]) => ESTADO_LABEL[k] ?? k),
                    datasets: [{
                        data: entries.map(([, v]) => v),
                        backgroundColor: entries.map(([k]) => PALETTE[k] ?? PALETTE.gris),
                        borderWidth: 2,
                        borderColor: '#fff',
                        hoverOffset: 6,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 10, padding: 14, font: { size: 11 } },
                        },
                    },
                },
            });
        });
        return () => destroyChart(chartRef);
    }, [JSON.stringify(por_estado)]);

    return <canvas ref={canvasRef} />;
}

/* ─── Bar: eventos por tipo ──────────────────────────────────────────────── */
function BarTipo({ por_tipo }) {
    const canvasRef = useRef(null);
    const chartRef  = useRef(null);

    useEffect(() => {
        buildChartScript(Chart => {
            destroyChart(chartRef);
            const ctx = canvasRef.current?.getContext('2d');
            if (!ctx) return;
            const entries = Object.entries(por_tipo);
            chartRef.current = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: entries.map(([k]) => TIPO_LABEL[k] ?? k),
                    datasets: [{
                        data: entries.map(([, v]) => v),
                        backgroundColor: entries.map(([k]) => PALETTE[k] ?? PALETTE.gris),
                        borderRadius: 6,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, border: { display: false } },
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, precision: 0 },
                            grid: { color: '#f3f4f6' },
                            border: { display: false },
                        },
                    },
                },
            });
        });
        return () => destroyChart(chartRef);
    }, [JSON.stringify(por_tipo)]);

    return <canvas ref={canvasRef} />;
}

/* ─── Line: tendencia mensual ────────────────────────────────────────────── */
function LineTendencia({ tendencia_mensual }) {
    const canvasRef = useRef(null);
    const chartRef  = useRef(null);

    useEffect(() => {
        buildChartScript(Chart => {
            destroyChart(chartRef);
            const ctx = canvasRef.current?.getContext('2d');
            if (!ctx) return;
            chartRef.current = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: tendencia_mensual.map(m => m.label),
                    datasets: [
                        {
                            label: 'Aplicadas',
                            data: tendencia_mensual.map(m => m.aplicadas),
                            borderColor: PALETTE.aplicada,
                            backgroundColor: PALETTE.aplicada + '22',
                            tension: 0.35,
                            fill: false,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                        },
                        {
                            label: 'Pendientes',
                            data: tendencia_mensual.map(m => m.pendientes),
                            borderColor: PALETTE.pendiente,
                            backgroundColor: PALETTE.pendiente + '22',
                            tension: 0.35,
                            fill: false,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                        },
                        {
                            label: 'Vencidas',
                            data: tendencia_mensual.map(m => m.vencidas),
                            borderColor: PALETTE.vencida,
                            backgroundColor: PALETTE.vencida + '22',
                            tension: 0.35,
                            fill: false,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 10, padding: 16, font: { size: 11 } },
                        },
                    },
                    scales: {
                        x: { grid: { display: false }, border: { display: false } },
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, precision: 0 },
                            grid: { color: '#f3f4f6' },
                            border: { display: false },
                        },
                    },
                },
            });
        });
        return () => destroyChart(chartRef);
    }, [JSON.stringify(tendencia_mensual)]);

    return <canvas ref={canvasRef} />;
}

/* ─── Componente principal ────────────────────────────────────────────────── */
export default function TabEstadisticas() {
    const [data,    setData]    = useState(null);
    const [loading, setLoading] = useState(true);
    const [error,   setError]   = useState(null);

    useEffect(() => {
        let cancelado = false;
        axios.get(route('salud.estadisticas'))
            .then(r  => { if (!cancelado) setData(r.data); })
            .catch(() => { if (!cancelado) setError('No se pudieron cargar las estadísticas.'); })
            .finally(() => { if (!cancelado) setLoading(false); });
        return () => { cancelado = true; };
    }, []);

    if (loading) {
        return (
            <div className="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div className="px-6 py-4 border-b border-gray-100">
                    <h2 className="text-base font-bold text-gray-900">Estadísticas</h2>
                    <p className="text-xs text-gray-400 mt-0.5">Resumen de salud del hato</p>
                </div>
                <div className="flex items-center justify-center gap-3 py-20 text-sm text-gray-400">
                    <span className="text-xl">⏳</span>
                    Cargando estadísticas…
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div className="px-6 py-4 border-b border-gray-100">
                    <h2 className="text-base font-bold text-gray-900">Estadísticas</h2>
                </div>
                <div className="flex items-center justify-center gap-2 py-16 text-sm text-red-500">
                    ⚠️ {error}
                </div>
            </div>
        );
    }

    const {
        kpis,
        por_estado,
        por_tipo,
        tendencia_mensual,
        cobertura_vacunacion,
        top_diagnosticos,
    } = data;

    const maxDiag = top_diagnosticos[0]?.total ?? 1;
    const totalEstados = Object.values(por_estado).reduce((a, b) => a + b, 0);
    const totalTipos   = Object.values(por_tipo).reduce((a, b) => a + b, 0);

    return (
        <div className="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

            {/* ── Header ── */}
            <div className="px-6 py-4 border-b border-gray-100">
                <h2 className="text-base font-bold text-gray-900 flex items-center gap-2">
                    <Activity size={18} className="text-green-600" />
                    Estadísticas
                </h2>
                <p className="text-xs text-gray-400 mt-0.5">Resumen de salud del hato</p>
            </div>

            <div className="p-5 space-y-6">

                {/* ── KPIs ── */}
                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <KpiCard label="Total eventos" value={kpis.total_eventos}
                        icon={ClipboardList} color="#2563eb" sub="Registrados" />
                    <KpiCard label="Aplicados" value={kpis.aplicados}
                        icon={CheckCircle2} color={PALETTE.aplicada} sub="Completados" />
                    <KpiCard label="Pendientes" value={kpis.pendientes}
                        icon={Clock} color={PALETTE.pendiente} sub="Por aplicar" />
                    <KpiCard label="Vencidos" value={kpis.vencidos}
                        icon={AlertTriangle} color={PALETTE.vencida}
                        sub={kpis.vencidos > 0 ? '¡Requiere atención!' : 'Sin atrasos'}
                        alert={kpis.vencidos > 0} />
                    <KpiCard label="Tratamientos activos" value={kpis.tratamientos_activos}
                        icon={Pill} color={PALETTE.consulta} sub="En curso" />
                    <KpiCard label="Vacunas del mes" value={kpis.vacunaciones_mes}
                        icon={Syringe} color={PALETTE.vacunacion} sub="Este mes" />
                </div>

                {/* ── Cobertura de vacunación (gauge, sin librería de charts) ── */}
                <div className="grid grid-cols-1 sm:grid-cols-[auto_1fr] gap-4 items-center bg-gray-50 rounded-2xl p-5">
                    <Gauge
                        pct={cobertura_vacunacion.porcentaje}
                        label={`${cobertura_vacunacion.vacunados} de ${cobertura_vacunacion.total} animales`}
                        color={
                            cobertura_vacunacion.porcentaje >= 80 ? PALETTE.aplicada
                            : cobertura_vacunacion.porcentaje >= 50 ? PALETTE.pendiente
                            : PALETTE.vencida
                        }
                    />
                    <div>
                        <span className="text-xs font-semibold uppercase tracking-widest text-gray-400">
                            Cobertura de vacunación · últimos 12 meses
                        </span>
                        <p className="text-xs text-gray-400 mt-1">
                            Solo incluye animales individuales con vacunación registrada (no lotes).
                        </p>
                    </div>
                </div>

                {/* ── Distribución de eventos ── */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div className="rounded-xl border border-gray-100 p-4">
                        <h3 className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                            Por estado
                        </h3>
                        {totalEstados === 0 ? (
                            <div className="h-48 flex items-center justify-center text-sm text-gray-400">
                                Sin datos registrados
                            </div>
                        ) : (
                            <div style={{ height: 200 }}>
                                <DonutEstado por_estado={por_estado} />
                            </div>
                        )}
                    </div>

                    <div className="rounded-xl border border-gray-100 p-4">
                        <h3 className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                            Por tipo
                        </h3>
                        {totalTipos === 0 ? (
                            <div className="h-48 flex items-center justify-center text-sm text-gray-400">
                                Sin datos registrados
                            </div>
                        ) : (
                            <div style={{ height: 200 }}>
                                <BarTipo por_tipo={por_tipo} />
                            </div>
                        )}
                    </div>
                </div>

                {/* ── Tendencia mensual ── */}
                {tendencia_mensual.length > 0 && (
                    <div className="rounded-xl border border-gray-100 p-4">
                        <h3 className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                            Tendencia mensual · últimos 12 meses
                        </h3>
                        <div style={{ height: 220 }}>
                            <LineTendencia tendencia_mensual={tendencia_mensual} />
                        </div>
                    </div>
                )}

                {/* ── Top diagnósticos ── */}
                {top_diagnosticos.length > 0 && (
                    <div className="rounded-xl border border-gray-100 p-4">
                        <h3 className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                            Top 5 diagnósticos
                        </h3>
                        <div className="flex flex-col gap-3.5">
                            {top_diagnosticos.map((d, i) => (
                                <div key={i}>
                                    <div className="flex justify-between items-baseline text-sm mb-1">
                                        <span className="text-gray-700 truncate pr-3" title={d.nombre}>
                                            {d.nombre.length > 48 ? d.nombre.slice(0, 46) + '…' : d.nombre}
                                        </span>
                                        <span className="font-semibold text-purple-600 whitespace-nowrap text-xs">
                                            {d.total} caso{d.total !== 1 ? 's' : ''}
                                        </span>
                                    </div>
                                    <div className="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                        <div
                                            className="h-full bg-purple-500 rounded-full transition-all duration-700"
                                            style={{ width: `${(d.total / maxDiag) * 100}%` }}
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

            </div>
        </div>
    );
}