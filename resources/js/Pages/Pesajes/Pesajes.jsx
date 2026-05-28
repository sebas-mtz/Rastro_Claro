import React, { useEffect, useMemo, useRef, useState } from "react";
import { useForm, usePage } from "@inertiajs/react";
import AppLayout from "@/Layouts/AppLayout";
import {
    Search, Plus, Scale, ClipboardList, CalendarDays,
    StickyNote, TrendingUp, Filter,
} from "lucide-react";
import HistorialPesajesModal from "./HistorialPesajesModal";
import {
    LineChart, Line, XAxis, YAxis, CartesianGrid,
    Tooltip, Legend, ResponsiveContainer,
} from "recharts";

const CHART_COLORS = [
    "#3b82f6","#10b981","#f59e0b","#ef4444",
    "#8b5cf6","#06b6d4","#f97316","#84cc16",
];

// ─── Helpers puros ────────────────────────────────────────────────────────────

function calcularGananciaEnRango(pesajes, fechaInicio, fechaFin) {
    const sorted = [...(pesajes || [])].sort((a, b) => a.fecha.localeCompare(b.fecha));
    const antesInicio = sorted.filter((p) => p.fecha <= fechaInicio);
    const antesFin    = sorted.filter((p) => p.fecha <= fechaFin);
    if (!antesInicio.length || !antesFin.length) return null;

    const primerPesaje = antesInicio[antesInicio.length - 1];
    const ultimoPesaje = antesFin[antesFin.length - 1];
    const pesoInicio   = parseFloat(primerPesaje.peso);
    const pesoFin      = parseFloat(ultimoPesaje.peso);
    const ganancia     = Math.round((pesoFin - pesoInicio) * 100) / 100;
    const dias         = Math.round(
        (new Date(ultimoPesaje.fecha) - new Date(primerPesaje.fecha)) / 86400000
    );
    const gdp = dias > 0 ? Math.round((ganancia / dias) * 1000) / 1000 : null;
    return { pesoInicio, pesoFin, ganancia, gdp, dias };
}

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

// ─── Componente ───────────────────────────────────────────────────────────────

function Pesajes() {
    const { animales = [], flash = {} } = usePage().props;

    const formRef     = useRef(null);
    const animalesRef = useRef(animales);
    useEffect(() => { animalesRef.current = animales; }, [animales]);

    const [tab, setTab] = useState("animales");

    // Estado pestaña Animales
    const [busqueda, setBusqueda]             = useState("");
    const [limiteVisibles, setLimiteVisibles] = useState(8);
    const [modalHistorialOpen, setModalHistorialOpen] = useState(false);
    const [animalHistorial, setAnimalHistorial]       = useState(null);

    // Estado pestaña Ganancia
    const hoy    = new Date().toISOString().split("T")[0];
    const hace30 = new Date(Date.now() - 30 * 86400000).toISOString().split("T")[0];
    const [gFechaInicio, setGFechaInicio] = useState(hace30);
    const [gFechaFin,    setGFechaFin]    = useState(hoy);
    const [gEspecie,     setGEspecie]     = useState("");
    const [gRaza,        setGRaza]        = useState("");
    const [gLote,        setGLote]        = useState("");
    const [gAnimal,      setGAnimal]      = useState("");

    const { data, setData, post, processing, errors, reset, delete: destroy } = useForm({
        animal_id: "",
        fecha:     hoy,
        peso:      "",
        notas:     "",
    });

    const inputClass  = "w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-400 focus:ring focus:ring-blue-100";
    const filterClass = "rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring focus:ring-blue-100";

    const fmtNum  = (v, d = 2) => v == null ? "—" : Number(v).toFixed(d);
    const fmtPeso = (v)        => v == null ? "—" : `${Number(v).toFixed(2)} kg`;
    const round2  = (n)        => Math.round(Number(n) * 100) / 100;
    const preventWheelChange = (e) => e.target.blur();

    const badgeGanancia = (valor) => {
        if (valor > 0) return "border-emerald-200 bg-emerald-50 text-emerald-700";
        if (valor < 0) return "border-red-200 bg-red-50 text-red-700";
        return "border-gray-200 bg-gray-50 text-gray-600";
    };
    const rangoFechasInvalido = gFechaInicio && gFechaFin && gFechaInicio > gFechaFin;
    // ── Memos pestaña Animales ────────────────────────────────────────────────
    const animalSeleccionado = useMemo(
        () => animales.find((a) => String(a.id) === String(data.animal_id)) ?? null,
        [animales, data.animal_id]
    );

    const animalesFiltrados = useMemo(() => {
        const q = busqueda.trim().toLowerCase();
        if (!q) return animales;
        return animales.filter((a) =>
            [a.arete, a.alias, a.especie, a.raza, a.sexo]
                .filter(Boolean)
                .some((c) => String(c).toLowerCase().includes(q))
        );
    }, [animales, busqueda]);

    const animalesVisibles = useMemo(
        () => animalesFiltrados.slice(0, limiteVisibles),
        [animalesFiltrados, limiteVisibles]
    );

    useEffect(() => { setLimiteVisibles(8); }, [busqueda]);

    // ── Memos pestaña Ganancia ────────────────────────────────────────────────
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
            .map((a) => ({ animal: a, ...calcularGananciaEnRango(a.pesajes, gFechaInicio, gFechaFin) }))
            .filter((r) => r.ganancia !== undefined)
            .sort((a, b) => (b.ganancia ?? -Infinity) - (a.ganancia ?? -Infinity)),
    [animalesFiltradosGanancia, gFechaInicio, gFechaFin]);

    const sinDatos = useMemo(
        () => animalesFiltradosGanancia.filter(
            (a) => !calcularGananciaEnRango(a.pesajes, gFechaInicio, gFechaFin)
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

    // ── Handlers ──────────────────────────────────────────────────────────────
    const handleSelectAnimal = (id) => setData("animal_id", id);

    const clearForm = () => {
        reset();
        setData({
            animal_id: "",
            fecha: new Date().toISOString().split("T")[0],
            peso: "",
            notas: "",
        });
    };

    const handleCreate = (e) => {
        e.preventDefault();
        post(route("pesajes.store"), { preserveScroll: true, onSuccess: clearForm });
    };

    const handleDelete = (pesaje) => {
        if (!window.confirm("¿Seguro que deseas eliminar este pesaje?")) return;
        destroy(route("pesajes.destroy", pesaje.id), {
            preserveScroll: true,
            onSuccess: () => {
                const actualizado = animalesRef.current?.find(
                    (a) => String(a.id) === String(animalHistorial?.id)
                );
                if (actualizado) setAnimalHistorial(actualizado);
            },
        });
    };

    const handleNuevoPesaje = (animalId) => {
        handleSelectAnimal(animalId);
        setTab("animales");
        setTimeout(() => formRef.current?.scrollIntoView({ behavior: "smooth", block: "start" }), 100);
    };

    const abrirHistorial  = (animal) => { setAnimalHistorial(animal); setModalHistorialOpen(true); };
    const cerrarHistorial = ()       => { setModalHistorialOpen(false); setAnimalHistorial(null); };

    // ── Render ────────────────────────────────────────────────────────────────
    return (
        <div className="py-8 px-6 max-w-7xl mx-auto">
            {/* ENCABEZADO */}
            <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                    <h2 className="text-2xl font-bold text-gray-800">Pesajes</h2>
                    <p className="mt-1 text-gray-600">
                        Registro y seguimiento del peso de tus animales a lo largo del tiempo.
                    </p>
                </div>
    
                <div className="flex flex-wrap gap-3 mt-5">
      <button
        type="button"
        onClick={() => setTab("animales")}
        className="h-10 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-medium px-4 rounded-lg flex items-center gap-2 transition"
    >
        <Scale size={18} className="text-blue-600" />
        Animales
    </button>

    <button
        type="button"
        onClick={() => setTab("ganancia")}
        className="h-10 bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 rounded-lg flex items-center gap-2 transition"
    >
        <TrendingUp size={18} />
        Ganancia por período
    </button>
                </div>
            </div>
    
            {/* TABS */}
            <div className="flex gap-6 border-b mt-2 pt-2 pb-4 text-gray-600 overflow-x-auto">
           {[
                { key: "animales", label: "Animales", Icon: Scale },
                { key: "ganancia", label: "Ganancia por período", Icon: TrendingUp },
            ].map(({ key, label, Icon }) => (
                <button
                    key={key}
                    type="button"
                    onClick={() => setTab(key)}
                    className={`flex items-center gap-2 pb-2 whitespace-nowrap transition ${
                        tab === key
                            ? "border-b-2 border-blue-600 text-blue-600 font-semibold"
                            : "hover:text-blue-600"
                    }`}
                >
                    <Icon size={17} />
                    {label}
                </button>
            ))}
        </div>

        {/* ════════════ Tab: Animales ════════════ */}
        {tab === "animales" && (
            <>
                {/* FORMULARIO */}
                <div
                    ref={formRef}
                    className="rounded-2xl border border-gray-100 bg-white p-5 shadow"
                >
                    <div className="mb-5">
                        <h3 className="text-base font-semibold text-gray-800">
                            Registrar pesaje
                        </h3>
                        <p className="text-sm text-gray-500">
                            Captura el peso del animal y guarda observaciones si aplica.
                        </p>
                    </div>

                    <form
                        onSubmit={handleCreate}
                        className="grid grid-cols-1 gap-4 text-sm md:grid-cols-3"
                    >
                        <div className="md:col-span-3">
                            <label className="mb-1 block text-xs font-medium text-gray-600">
                                Animal *
                            </label>
                            <select
                                className={inputClass}
                                value={data.animal_id}
                                onChange={(e) => handleSelectAnimal(e.target.value)}
                            >
                                <option value="">Selecciona un animal</option>
                                {animales.map((a) => (
                                    <option key={a.id} value={a.id}>
                                        {a.arete}
                                        {a.alias ? ` — ${a.alias}` : ""} · {a.especie}
                                        {a.raza ? ` (${a.raza})` : ""}
                                    </option>
                                ))}
                            </select>
                            {errors.animal_id && (
                                <p className="mt-1 text-xs text-red-500">{errors.animal_id}</p>
                            )}
                        </div>

                        <div>
                            <label className="mb-1 flex items-center gap-1 text-xs font-medium text-gray-600">
                                <CalendarDays size={14} className="text-blue-600" /> Fecha *
                            </label>
                            <input
                                type="date"
                                className={inputClass}
                                value={data.fecha}
                                onChange={(e) => setData("fecha", e.target.value)}
                            />
                            {errors.fecha && (
                                <p className="mt-1 text-xs text-red-500">{errors.fecha}</p>
                            )}
                        </div>

                        <div>
                            <label className="mb-1 flex items-center gap-1 text-xs font-medium text-gray-600">
                                <Scale size={14} className="text-blue-600" /> Peso (kg) *
                            </label>
                            <input
                                type="number"
                                step="0.01"
                                min="0.01"
                                inputMode="decimal"
                                onWheel={preventWheelChange}
                                className={inputClass}
                                placeholder="Ej. 450.5"
                                value={data.peso}
                                onChange={(e) => setData("peso", e.target.value)}
                            />
                            {errors.peso && (
                                <p className="mt-1 text-xs text-red-500">{errors.peso}</p>
                            )}
                        </div>

                        <div>
                            <label className="mb-1 flex items-center gap-1 text-xs font-medium text-gray-600">
                                <StickyNote size={14} className="text-blue-600" /> Notas
                            </label>
                            <input
                                type="text"
                                className={inputClass}
                                placeholder="Ej. Post-parto, ayuno previo..."
                                value={data.notas}
                                onChange={(e) => setData("notas", e.target.value)}
                            />
                        </div>

                        {animalSeleccionado && (
                            <div className="md:col-span-3 rounded-2xl border-l-4 border-blue-500 bg-white shadow-sm p-4">
                                <div className="flex flex-wrap gap-4 text-xs text-gray-600">
                                    <span>
                                        Peso actual:{" "}
                                        <strong>
                                            {fmtPeso(
                                                animalSeleccionado.peso_actual ??
                                                    animalSeleccionado.peso
                                            )}
                                        </strong>
                                    </span>

                                    {animalSeleccionado.ganancia_total != null && (
                                        <span>
                                            Ganancia total:{" "}
                                            <strong>
                                                {fmtNum(
                                                    animalSeleccionado.ganancia_total,
                                                    2
                                                )}{" "}
                                                kg
                                            </strong>
                                        </span>
                                    )}

                                    {animalSeleccionado.ganancia_diaria != null && (
                                        <span>
                                            GDP:{" "}
                                            <strong>
                                                {fmtNum(
                                                    animalSeleccionado.ganancia_diaria,
                                                    3
                                                )}{" "}
                                                kg/día
                                            </strong>
                                        </span>
                                    )}
                                </div>
                            </div>
                        )}

                        <div className="md:col-span-3 flex justify-end pt-1">
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-lg bg-blue-600 px-4 py-2 text-xs text-white hover:bg-blue-700 disabled:opacity-50 transition"
                            >
                                {processing ? "Guardando..." : "Registrar pesaje"}
                            </button>
                        </div>
                    </form>
                </div>

                {/* BUSCADOR */}
                <div className="flex flex-wrap items-center justify-between gap-4 mt-6">
                    <div className="relative w-full max-w-md">
                        <Search
                            className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
                            size={18}
                        />
                        <input
                            type="text"
                            className="w-full rounded-xl border border-gray-300 bg-white py-3 pl-10 pr-4 text-sm focus:border-blue-400 focus:ring focus:ring-blue-100"
                            placeholder="Buscar por arete, alias, especie o raza..."
                            value={busqueda}
                            onChange={(e) => setBusqueda(e.target.value)}
                        />
                    </div>
                    <span className="text-sm text-gray-500">
                        {animalesFiltrados.length} animal(es)
                    </span>
                </div>

                {/* CARDS */}
                <div className="space-y-6 mt-5">
                <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-8">
                       {animalesVisibles.map((animal) => {
                            const tieneHistorial = animal.pesajes?.length > 0;

                            return (
                                <div
                                    key={animal.id}
                                    className="bg-white rounded-2xl shadow p-6 border-l-4 border-blue-500 transition hover:shadow-md"
                                   >
                                    <div className="mb-4 flex items-start justify-between">
                                        <div className="min-w-0">
                                            <h3 className="truncate text-base font-semibold text-gray-800">
                                                {animal.arete}
                                            </h3>

                                            <div className="mt-1 flex flex-wrap items-center gap-2">
                                                {animal.alias && (
                                                    <span className="text-xs text-gray-400">
                                                        {animal.alias}
                                                    </span>
                                                )}

                                                <span className="rounded-full border border-blue-100 bg-blue-50 px-2 py-0.5 text-[11px] text-blue-700">
                                                    {animal.especie}
                                                    {animal.raza
                                                        ? ` · ${animal.raza}`
                                                        : ""}
                                                </span>

                                                <span className="text-[11px] text-gray-400">
                                                    {animal.sexo}
                                                </span>
                                            </div>
                                        </div>

                                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                            <Scale size={18} />
                                        </div>
                                    </div>

                                    <div className="space-y-3 text-sm">
                                        <div className="rounded-xl border border-gray-100 bg-gray-50 p-3">
                                            <p className="text-xs text-gray-500">Peso actual</p>
                                            <p className="text-base font-semibold text-gray-800">
                                                {fmtPeso(animal.peso_actual ?? animal.peso)}
                                            </p>
                                        </div>

                                        <div className="grid grid-cols-2 gap-2">
                                            <div className="rounded-xl border border-gray-100 bg-gray-50 p-3">
                                                <p className="text-[11px] text-gray-500">
                                                    Ganancia
                                                </p>
                                                <p className="text-sm font-medium text-gray-800">
                                                    {animal.ganancia_total != null
                                                        ? `${
                                                              animal.ganancia_total >= 0
                                                                  ? "+"
                                                                  : ""
                                                          }${fmtNum(
                                                              animal.ganancia_total,
                                                              2
                                                          )} kg`
                                                        : "—"}
                                                </p>
                                            </div>

                                            <div className="rounded-xl border border-gray-100 bg-gray-50 p-3">
                                                <p className="text-[11px] text-gray-500">GDP</p>
                                                <p className="text-sm font-medium text-gray-800">
                                                    {animal.ganancia_diaria != null
                                                        ? `${fmtNum(
                                                              animal.ganancia_diaria,
                                                              3
                                                          )} kg/día`
                                                        : "—"}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="rounded-xl border border-gray-100 bg-gray-50 p-3">
                                            <p className="text-[11px] text-gray-500">
                                                Seguimiento
                                            </p>
                                            <p className="text-sm font-medium text-gray-800">
                                                {animal.dias_seguimiento != null
                                                    ? `${animal.dias_seguimiento} días`
                                                    : "Sin seguimiento"}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="mt-4 flex flex-col gap-2">
                                        <button
                                            type="button"
                                            onClick={() => handleNuevoPesaje(animal.id)}
                                            className="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 transition"
                                        >
                                            <Plus size={16} /> Pesaje
                                        </button>

                                        <button
                                            type="button"
                                            onClick={() => abrirHistorial(animal)}
                                            className="flex w-full items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-medium text-blue-700 hover:bg-blue-100 transition"
                                        >
                                            <ClipboardList size={16} />
                                            Ver historial
                                            {tieneHistorial ? ` (${animal.pesajes.length})` : ""}
                                        </button>
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {animalesFiltrados.length > limiteVisibles && (
                        <div className="flex justify-center">
                            <button
                                type="button"
                                onClick={() => setLimiteVisibles((p) => p + 8)}
                                className="rounded-xl border border-blue-200 bg-blue-50 px-5 py-3 text-sm font-medium text-blue-700 hover:bg-blue-100 transition"
                            >
                                Ver más animales
                            </button>
                        </div>
                    )}

                    {animalesFiltrados.length === 0 && (
                        <div className="rounded-2xl border border-gray-100 bg-white p-8 text-center shadow">
                            <p className="text-sm text-gray-400">
                                {busqueda
                                    ? "No se encontraron animales con esa búsqueda."
                                    : "No hay animales registrados."}
                            </p>
                        </div>
                    )}
                </div>
            </>
        )}

        {/* ════════════ Tab: Ganancia por período ════════════ */}
        {tab === "ganancia" && (
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
                                        ? `${fmtNum(promedioGdp, 3)} kg/día`
                                        : "—",
                                sub: "ganancia diaria promedio",
                                border: "border-emerald-500",
                            },
                            {
                                label: "Mejor ganancia",
                                value: mejorAnimal
                                    ? `+${fmtNum(mejorAnimal.ganancia, 2)} kg`
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
                        <div className="mb-3 flex items-center justify-between">
                            <h3 className="text-sm font-semibold text-gray-700">
                                Curva de peso en el período
                            </h3>

                            {animalesParaChart.length === 8 && (
                                <span className="text-[11px] text-gray-400">
                                    Primeros 8 animales · filtra por animal para ver individualmente
                                </span>
                            )}
                        </div>

                        <ResponsiveContainer width="100%" height={280}>
                            <LineChart
                                data={chartData}
                                margin={{ top: 5, right: 20, left: 0, bottom: 5 }}
                            >
                                <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                                <XAxis
                                    dataKey="fecha"
                                    tick={{ fontSize: 11 }}
                                    tickFormatter={(f) => {
                                        const [, m, d] = f.split("-");
                                        return `${d}/${m}`;
                                    }}
                                />
                                <YAxis
                                    tick={{ fontSize: 11 }}
                                    tickFormatter={(v) => `${v} kg`}
                                    width={68}
                                />
                                <Tooltip
                                    formatter={(v, name) => [
                                        `${Number(v).toFixed(2)} kg`,
                                        name,
                                    ]}
                                    labelFormatter={(l) => `Fecha: ${l}`}
                                />
                                <Legend />
                                {animalesParaChart.map((a, i) => (
                                    <Line
                                        key={a.id}
                                        type="monotone"
                                        dataKey={a.arete}
                                        stroke={CHART_COLORS[i % CHART_COLORS.length]}
                                        strokeWidth={2}
                                        dot={{ r: 4 }}
                                        activeDot={{ r: 6 }}
                                        connectNulls={false}
                                    />
                                ))}
                            </LineChart>
                        </ResponsiveContainer>
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
                                                {fmtNum(row.ganancia, 2)} kg
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-right text-gray-700">
                                            {row.gdp != null
                                                ? `${fmtNum(row.gdp, 3)} kg/día`
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
        )}

            <HistorialPesajesModal
                open={modalHistorialOpen}
                animal={animalHistorial}
                onClose={cerrarHistorial}
                handleDelete={handleDelete}
                round2={round2}
                badgeGanancia={badgeGanancia}
            />
        </div>
    );
}

Pesajes.layout = (page) => <AppLayout>{page}</AppLayout>;
export default Pesajes;