import React, { useEffect, useRef, useState, useMemo } from "react";
import {
  TrendingUp,
  Baby,
  Heart,
  AlertTriangle,
  Activity,
  Calendar,
  ChevronDown,
} from "lucide-react";

/* ─── helpers ────────────────────────────────────────────────────────────── */

function buildChartScript(cb) {
  if (window.Chart) return cb(window.Chart);
  const s = document.createElement("script");
  s.src = "https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js";
  s.onload = () => cb(window.Chart);
  document.head.appendChild(s);
}

function destroyChart(ref) {
  if (ref.current) {
    ref.current.destroy();
    ref.current = null;
  }
}

/* ─── paleta ─────────────────────────────────────────────────────────────── */
const PALETTE = {
  verde:     "#16a34a",
  verdeClaro:"#4ade80",
  rojo:      "#dc2626",
  rojoClaro: "#f87171",
  naranja:   "#ea580c",
  naranjaClaro:"#fb923c",
  azul:      "#2563eb",
  azulClaro: "#60a5fa",
  gris:      "#6b7280",
  grisClaro: "#d1d5db",
  limon:     "#ca8a04",
  limonClaro:"#fbbf24",
};

/* ─── derivar estadísticas desde los eventos (prop) ─────────────────────── */
function derivarEstadisticas(eventos = []) {
  const servicios    = eventos.filter(e => e.tipo_evento === "servicio");
  const diagnosticos = eventos.filter(e => e.tipo_evento === "diagnostico");
  const partos       = eventos.filter(e => e.tipo_evento === "parto");

  const positivos = diagnosticos.filter(e => e.diagnostico?.resultado === "positivo");
  const negativos = diagnosticos.filter(e => e.diagnostico?.resultado === "negativo");
  const repetir   = diagnosticos.filter(e => e.diagnostico?.resultado === "repetir");

  const totalCrias = partos.reduce(
    (acc, e) => acc + (e.parto?.crias?.length ?? 0), 0
  );
  const criasVivas = partos.reduce(
    (acc, e) =>
      acc + (e.parto?.crias?.filter(c => c.condicion === "vivo").length ?? 0),
    0
  );

  const tiposParto = partos.reduce(
    (acc, e) => {
      const t = e.parto?.tipo_parto ?? "normal";
      acc[t] = (acc[t] ?? 0) + 1;
      return acc;
    },
    {}
  );

  // Días promedio entre partos por hembra
  const partosPorHembra = {};
  for (const e of partos) {
    if (!e.hembra_id) continue;
    if (!partosPorHembra[e.hembra_id]) partosPorHembra[e.hembra_id] = [];
    partosPorHembra[e.hembra_id].push(new Date(e.fecha));
  }
  let sumaIntervalos = 0, cuentaIntervalos = 0;
  for (const fechas of Object.values(partosPorHembra)) {
    fechas.sort((a, b) => a - b);
    for (let i = 1; i < fechas.length; i++) {
      sumaIntervalos += (fechas[i] - fechas[i - 1]) / 86400000;
      cuentaIntervalos++;
    }
  }
  const diasPromedioEntrePartos = cuentaIntervalos > 0
    ? Math.round(sumaIntervalos / cuentaIntervalos)
    : null;

  // Eventos por mes (últimos 6 meses)
  const ahora = new Date();
  const meses = Array.from({ length: 6 }, (_, i) => {
    const d = new Date(ahora.getFullYear(), ahora.getMonth() - 5 + i, 1);
    return {
      key:   `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`,
      label: d.toLocaleString("es-MX", { month: "short", year: "2-digit" }),
      servicios: 0, diagnosticos: 0, partos: 0,
    };
  });
  for (const e of eventos) {
    const key = e.fecha?.slice(0, 7);
    const mes = meses.find(m => m.key === key);
    if (!mes) continue;
    if (e.tipo_evento === "servicio")    mes.servicios++;
    if (e.tipo_evento === "diagnostico") mes.diagnosticos++;
    if (e.tipo_evento === "parto")       mes.partos++;
  }

  const fertilidad = servicios.length > 0
    ? Math.round((positivos.length / servicios.length) * 100)
    : 0;

  const supervivencia = totalCrias > 0
    ? Math.round((criasVivas / totalCrias) * 100)
    : 100;

  return {
    totalServicios: servicios.length,
    gestantes:      positivos.length,
    totalPartos:    partos.length,
    fertilidad,
    supervivencia,
    totalCrias,
    criasVivas,
    tiposParto,
    diagnosticoResultados: {
      positivo: positivos.length,
      negativo: negativos.length,
      repetir:  repetir.length,
    },
    meses,
    diasPromedioEntrePartos,
  };
}

/* ─── KPI card ───────────────────────────────────────────────────────────── */
function KpiCard({ label, value, suffix = "", sub, color = PALETTE.verde, icon: Icon }) {
  return (
    <div className="bg-white rounded-2xl border border-gray-100 p-5 flex flex-col gap-2 shadow-sm hover:shadow-md transition-shadow">
      <div className="flex items-center justify-between">
        <span className="text-xs font-semibold uppercase tracking-widest text-gray-400">{label}</span>
        {Icon && (
          <span className="w-8 h-8 rounded-xl flex items-center justify-center" style={{ background: color + "18" }}>
            <Icon size={16} style={{ color }} />
          </span>
        )}
      </div>
      <div className="flex items-end gap-1">
        <span className="text-3xl font-bold tracking-tight text-gray-900">{value}</span>
        {suffix && <span className="text-base font-semibold mb-1" style={{ color }}>{suffix}</span>}
      </div>
      {sub && <p className="text-xs text-gray-400 leading-snug">{sub}</p>}
    </div>
  );
}

/* ─── Gauge SVG ──────────────────────────────────────────────────────────── */
function Gauge({ pct = 0, label, color = PALETTE.verde }) {
  const r = 54, cx = 70, cy = 70;
  const circumference = Math.PI * r; // semicircle
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
          style={{ transition: "stroke-dashoffset 1s ease" }}
        />
        <text x={cx} y={cy - 4} textAnchor="middle"
          className="text-2xl font-bold" style={{ fontFamily: "system-ui", fill: "#111827", fontSize: 22, fontWeight: 700 }}>
          {pct}%
        </text>
      </svg>
      <span className="text-xs text-gray-500 font-medium">{label}</span>
    </div>
  );
}

/* ─── Chart wrappers ─────────────────────────────────────────────────────── */
function BarTimeline({ meses }) {
  const canvasRef = useRef(null);
  const chartRef  = useRef(null);

  useEffect(() => {
    buildChartScript(Chart => {
      destroyChart(chartRef);
      const ctx = canvasRef.current?.getContext("2d");
      if (!ctx) return;
      chartRef.current = new Chart(ctx, {
        type: "bar",
        data: {
          labels: meses.map(m => m.label),
          datasets: [
            {
              label: "Servicios",
              data:  meses.map(m => m.servicios),
              backgroundColor: PALETTE.azul + "cc",
              borderRadius: 6,
            },
            {
              label: "Diagnósticos",
              data:  meses.map(m => m.diagnosticos),
              backgroundColor: PALETTE.limon + "cc",
              borderRadius: 6,
            },
            {
              label: "Partos",
              data:  meses.map(m => m.partos),
              backgroundColor: PALETTE.verde + "cc",
              borderRadius: 6,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: "bottom",
              labels: { boxWidth: 10, padding: 16, font: { size: 11 } },
            },
            tooltip: { mode: "index", intersect: false },
          },
          scales: {
            x: { grid: { display: false }, border: { display: false } },
            y: {
              beginAtZero: true,
              ticks: { stepSize: 1, precision: 0 },
              grid: { color: "#f3f4f6" },
              border: { display: false },
            },
          },
        },
      });
    });
    return () => destroyChart(chartRef);
  }, [meses]);

  return <canvas ref={canvasRef} />;
}

function DonutDiagnosticos({ positivo, negativo, repetir }) {
  const canvasRef = useRef(null);
  const chartRef  = useRef(null);

  useEffect(() => {
    buildChartScript(Chart => {
      destroyChart(chartRef);
      const ctx = canvasRef.current?.getContext("2d");
      if (!ctx) return;
      chartRef.current = new Chart(ctx, {
        type: "doughnut",
        data: {
          labels: ["Positivo", "Negativo", "Repetir"],
          datasets: [{
            data: [positivo, negativo, repetir],
            backgroundColor: [PALETTE.verde, PALETTE.rojo, PALETTE.naranja],
            borderWidth: 2,
            borderColor: "#fff",
            hoverOffset: 6,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: "70%",
          plugins: {
            legend: {
              position: "bottom",
              labels: { boxWidth: 10, padding: 14, font: { size: 11 } },
            },
          },
        },
      });
    });
    return () => destroyChart(chartRef);
  }, [positivo, negativo, repetir]);

  return <canvas ref={canvasRef} />;
}

function HorizontalTiposParto({ tiposParto }) {
  const canvasRef = useRef(null);
  const chartRef  = useRef(null);

  const labels = Object.keys(tiposParto).map(k =>
    k === "normal" ? "Normal" : k === "distocico" ? "Distócico" : "Cesárea"
  );
  const data = Object.values(tiposParto);
  const colors = [PALETTE.verde, PALETTE.naranja, PALETTE.rojo];

  useEffect(() => {
    buildChartScript(Chart => {
      destroyChart(chartRef);
      const ctx = canvasRef.current?.getContext("2d");
      if (!ctx) return;
      chartRef.current = new Chart(ctx, {
        type: "bar",
        data: {
          labels,
          datasets: [{
            data,
            backgroundColor: colors.slice(0, labels.length),
            borderRadius: 8,
            barThickness: 28,
          }],
        },
        options: {
          indexAxis: "y",
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: ctx => ` ${ctx.raw} partos`,
              },
            },
          },
          scales: {
            x: {
              beginAtZero: true,
              ticks: { stepSize: 1, precision: 0 },
              grid: { color: "#f3f4f6" },
              border: { display: false },
            },
            y: { grid: { display: false }, border: { display: false } },
          },
        },
      });
    });
    return () => destroyChart(chartRef);
  }, [JSON.stringify(tiposParto)]);

  return <canvas ref={canvasRef} />;
}

/* ─── Selector de filtro (cosmético) ─────────────────────────────────────── */
function FiltroSelect({ label, options, value, onChange }) {
  return (
    <div className="relative">
      <select
        value={value}
        onChange={e => onChange(e.target.value)}
        className="appearance-none pl-3 pr-8 py-1.5 text-xs font-medium rounded-lg border border-gray-200 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-500 cursor-pointer"
      >
        {options.map(o => (
          <option key={o.value} value={o.value}>{o.label}</option>
        ))}
      </select>
      <ChevronDown size={12} className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
    </div>
  );
}

/* ─── Componente principal ───────────────────────────────────────────────── */
export default function Estadisticas({ animales = [], lotes = [], eventos = [] }) {
  const [filtroEspecie, setFiltroEspecie] = useState("todas");
  const [filtroLote,    setFiltroLote]    = useState("todos");

  // Filtrar eventos según los selectores
  const eventosFiltrados = useMemo(() => {
    return eventos.filter(e => {
      const animal = animales.find(a => a.id === e.hembra_id);
      if (!animal) return true;
      if (filtroEspecie !== "todas" && animal.especie !== filtroEspecie) return false;
      if (filtroLote    !== "todos"  && String(animal.lote_id) !== filtroLote) return false;
      return true;
    });
  }, [eventos, animales, filtroEspecie, filtroLote]);

  const stats = useMemo(() => derivarEstadisticas(eventosFiltrados), [eventosFiltrados]);

  const especiesUnicas = useMemo(() => {
    const s = new Set(animales.map(a => a.especie).filter(Boolean));
    return [{ value: "todas", label: "Todas las especies" },
      ...Array.from(s).map(e => ({ value: e, label: e.charAt(0).toUpperCase() + e.slice(1) }))];
  }, [animales]);

  const lotesOpts = useMemo(() => {
    return [{ value: "todos", label: "Todos los lotes" },
      ...lotes.map(l => ({ value: String(l.id), label: l.nombre }))];
  }, [lotes]);

  const sinDatos = eventosFiltrados.length === 0;

  return (
    <div className="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

      {/* ── Header ── */}
      <div className="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="text-base font-bold text-gray-900 flex items-center gap-2">
            <Activity size={18} className="text-green-600" />
            Estadísticas reproductivas
          </h2>
          <p className="text-xs text-gray-400 mt-0.5">
            Basado en {eventosFiltrados.length} eventos registrados
          </p>
        </div>
        <div className="flex gap-2 flex-wrap">
          <FiltroSelect
            label="Especie"
            options={especiesUnicas}
            value={filtroEspecie}
            onChange={setFiltroEspecie}
          />
          <FiltroSelect
            label="Lote"
            options={lotesOpts}
            value={filtroLote}
            onChange={setFiltroLote}
          />
        </div>
      </div>

      {sinDatos ? (
        <div className="p-10 text-center text-sm text-gray-400">
          No hay eventos con estos filtros
        </div>
      ) : (
        <div className="p-5 space-y-6">

          {/* ── KPIs ── */}
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <KpiCard
              label="Servicios"
              value={stats.totalServicios}
              icon={Heart}
              color={PALETTE.azul}
              sub="Total aplicados"
            />
            <KpiCard
              label="Gestantes"
              value={stats.gestantes}
              icon={Baby}
              color={PALETTE.verde}
              sub="Diagnóstico positivo"
            />
            <KpiCard
              label="Partos"
              value={stats.totalPartos}
              icon={Calendar}
              color={PALETTE.naranja}
              sub={`${stats.totalCrias} crías registradas`}
            />
            <KpiCard
              label="Ints. promedio"
              value={stats.diasPromedioEntrePartos ?? "—"}
              suffix={stats.diasPromedioEntrePartos ? "d" : ""}
              icon={TrendingUp}
              color={PALETTE.limon}
              sub="Días entre partos"
            />
          </div>

          {/* ── Gauges ── */}
          <div className="grid grid-cols-2 gap-4 bg-gray-50 rounded-2xl p-5">
            <div className="flex flex-col items-center">
              <span className="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-3">
                Tasa de fertilidad
              </span>
              <Gauge
                pct={stats.fertilidad}
                label={`${stats.gestantes} de ${stats.totalServicios} servicios`}
                color={stats.fertilidad >= 60 ? PALETTE.verde : stats.fertilidad >= 40 ? PALETTE.naranja : PALETTE.rojo}
              />
            </div>
            <div className="flex flex-col items-center">
              <span className="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-3">
                Supervivencia de crías
              </span>
              <Gauge
                pct={stats.supervivencia}
                label={`${stats.criasVivas} de ${stats.totalCrias} crías`}
                color={stats.supervivencia >= 80 ? PALETTE.verde : stats.supervivencia >= 60 ? PALETTE.naranja : PALETTE.rojo}
              />
            </div>
          </div>

          {/* ── Gráficas principales ── */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-5">

            {/* Eventos por mes */}
            <div className="rounded-xl border border-gray-100 p-4">
              <h3 className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                Actividad últimos 6 meses
              </h3>
              <div style={{ height: 200 }}>
                <BarTimeline meses={stats.meses} />
              </div>
            </div>

            {/* Resultados de diagnóstico */}
            <div className="rounded-xl border border-gray-100 p-4">
              <h3 className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                Resultados de diagnóstico
              </h3>
              {(stats.diagnosticoResultados.positivo +
                stats.diagnosticoResultados.negativo +
                stats.diagnosticoResultados.repetir) === 0 ? (
                <div className="h-48 flex items-center justify-center text-sm text-gray-400">
                  Sin diagnósticos registrados
                </div>
              ) : (
                <div style={{ height: 200 }}>
                  <DonutDiagnosticos {...stats.diagnosticoResultados} />
                </div>
              )}
            </div>

          </div>

          {/* Tipos de parto */}
          {Object.keys(stats.tiposParto).length > 0 && (
            <div className="rounded-xl border border-gray-100 p-4">
              <h3 className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                Tipos de parto
              </h3>
              <div style={{ height: Math.max(80, Object.keys(stats.tiposParto).length * 52) }}>
                <HorizontalTiposParto tiposParto={stats.tiposParto} />
              </div>
            </div>
          )}

          {/* ── Alertas rápidas ── */}
          {(stats.totalServicios > 0 || stats.totalPartos > 0) && (
            <div className="rounded-xl border border-amber-100 bg-amber-50 p-4 flex gap-3">
              <AlertTriangle size={16} className="text-amber-500 shrink-0 mt-0.5" />
              <div className="text-xs text-amber-800 space-y-0.5">
                <p className="font-semibold">Puntos de atención</p>
                {stats.fertilidad < 50 && (
                  <p>• Tasa de fertilidad baja ({stats.fertilidad}%) — revisar protocolo de servicios.</p>
                )}
                {stats.supervivencia < 80 && (
                  <p>• Supervivencia de crías al {stats.supervivencia}% — revisar condición de partos.</p>
                )}
                {stats.tiposParto["cesarea"] > 0 && (
                  <p>• {stats.tiposParto["cesarea"]} cesárea(s) registrada(s) — monitorear condición materna.</p>
                )}
                {stats.fertilidad >= 50 && stats.supervivencia >= 80 && !stats.tiposParto["cesarea"] && (
                  <p>Todos los indicadores dentro de rangos saludables ✓</p>
                )}
              </div>
            </div>
          )}

        </div>
      )}
    </div>
  );
}