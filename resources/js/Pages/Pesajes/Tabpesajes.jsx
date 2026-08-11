import React, { useEffect, useMemo, useRef, useState } from "react";
import { useForm } from "@inertiajs/react";
import {
    Search, Plus, Scale, ClipboardList, CalendarDays, StickyNote,
    CheckCircle2, X,
} from "lucide-react";
import HistorialPesajesModal from "./HistorialPesajesModal";
import { usePreferences } from "@/Contexts/PreferencesContext";

// ─── Helpers de fecha (comparaciones como string "YYYY-MM-DD") ───────────────

function soloFecha(valor) {
    return valor ? String(valor).slice(0, 10) : null;
}

function sumarDias(fechaStr, dias) {
    const base = soloFecha(fechaStr);
    if (!base) return null;
    const [y, m, d] = base.split("-").map(Number);
    const ms = Date.UTC(y, m - 1, d) + dias * 86400000;
    const dt = new Date(ms);
    const yy = dt.getUTCFullYear();
    const mm = String(dt.getUTCMonth() + 1).padStart(2, "0");
    const dd = String(dt.getUTCDate()).padStart(2, "0");
    return `${yy}-${mm}-${dd}`;
}

function formatFechaLegible(fechaStr) {
    const f = soloFecha(fechaStr);
    if (!f) return "";
    const [y, m, d] = f.split("-");
    return `${d}/${m}/${y}`;
}

function TabPesajes({ animales = [], setTab }) {
    const { formatWeight, weightUnit, toKilograms } = usePreferences();

    const formRef     = useRef(null);
    const pesoInputRef = useRef(null);
    const animalesRef = useRef(animales);
    useEffect(() => { animalesRef.current = animales; }, [animales]);

    const [busqueda, setBusqueda]             = useState("");
    const [limiteVisibles, setLimiteVisibles] = useState(8);
    const [modalHistorialOpen, setModalHistorialOpen] = useState(false);
    const [animalHistorial, setAnimalHistorial]       = useState(null);

    const hoy = new Date().toISOString().split("T")[0];

    const { data, setData, post, processing, errors, reset, transform, delete: destroy } = useForm({
        animal_id: "",
        fecha:     hoy,
        peso:      "",
        notas:     "",
    });

    const inputClass = "w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-400 focus:ring focus:ring-blue-100";

    const fmtPeso        = (v) => formatWeight(v);
    const fmtPesoDiario  = (v) => v == null ? "—" : `${formatWeight(v)}/día`;
    const round2         = (n) => Math.round(Number(n) * 100) / 100;
    const preventWheelChange = (e) => e.target.blur();

    const badgeGanancia = (valor) => {
        if (valor > 0) return "border-emerald-200 bg-emerald-50 text-emerald-700";
        if (valor < 0) return "border-red-200 bg-red-50 text-red-700";
        return "border-gray-200 bg-gray-50 text-gray-600";
    };

    const animalSeleccionado = useMemo(
        () => animales.find((a) => String(a.id) === String(data.animal_id)) ?? null,
        [animales, data.animal_id]
    );

    // ── Siguiente pesaje pendiente para el animal seleccionado ────────────────
    // Se recalcula solo con lo que ya trae `animales`, así que cuando Inertia
    // recarga los props tras guardar un pesaje, esto avanza automáticamente.
    const proximaFechaInfo = useMemo(() => {
        if (!animalSeleccionado) return null;

        const pesajesOrdenados = [...(animalSeleccionado.pesajes || [])]
            .sort((a, b) => a.fecha.localeCompare(b.fecha));

        let fechaBase = null;
         if (pesajesOrdenados.length) {
        fechaBase = pesajesOrdenados[pesajesOrdenados.length - 1].fecha;
    }
        const siguiente = sumarDias(fechaBase, 1);
        if (!siguiente || siguiente > hoy) return { alDia: true, fecha: null };

        const yaExiste = pesajesOrdenados.some((p) => soloFecha(p.fecha) === siguiente);
        if (yaExiste) return { alDia: true, fecha: null };

        return { alDia: false, fecha: siguiente };
    }, [animalSeleccionado, hoy]);

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
        transform((formData) => ({
            ...formData,
            peso: toKilograms(formData.peso),
        }));
        post(route("pesajes.store"), {
            preserveScroll: true,
            onSuccess: () => {
                // Mantenemos el animal seleccionado y solo limpiamos peso/notas,
                // así el usuario puede seguir registrando día por día sin
                // volver a buscar el animal en el desplegable.
                setData((prev) => ({
                    ...prev,
                    peso: "",
                    notas: "",
                }));
            },
        });
    };

    const usarFechaSugerida = (fecha) => {
        if (!fecha) return;
        setData((prev) => ({
            ...prev,
            fecha,
            peso: "",
            notas: "",
        }));
        setTimeout(() => pesoInputRef.current?.focus(), 50);
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
        setTab?.("animales");
        setTimeout(() => formRef.current?.scrollIntoView({ behavior: "smooth", block: "start" }), 100);
    };

    const abrirHistorial  = (animal) => { setAnimalHistorial(animal); setModalHistorialOpen(true); };
    const cerrarHistorial = ()       => { setModalHistorialOpen(false); setAnimalHistorial(null); };

    return (
        <>
            {/* FORMULARIO */}
            <div
                ref={formRef}
                className="rounded-2xl border border-gray-100 bg-white p-5 shadow"
            >
                <div className="mb-5 flex items-start justify-between gap-3">
                    <div>
                        <h3 className="text-base font-semibold text-gray-800">
                            Registrar pesaje
                        </h3>
                        <p className="text-sm text-gray-500">
                            Captura el peso del animal y guarda observaciones si aplica.
                        </p>
                    </div>

                    {data.animal_id && (
                        <button
                            type="button"
                            onClick={clearForm}
                            className="flex items-center gap-1 text-xs text-gray-400 hover:text-gray-600 transition"
                        >
                            <X size={13} /> Elegir otro animal
                        </button>
                    )}
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
                            max={hoy}
                            onChange={(e) => setData("fecha", e.target.value)}
                        />
                        {errors.fecha && (
                            <p className="mt-1 text-xs text-red-500">{errors.fecha}</p>
                        )}
                    </div>

                    <div>
                        <label className="mb-1 flex items-center gap-1 text-xs font-medium text-gray-600">
                            <Scale size={14} className="text-blue-600" /> Peso ({weightUnit}) *
                        </label>
                        <input
                            ref={pesoInputRef}
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
                                            {animalSeleccionado.ganancia_total >= 0 ? "+" : "-"}
                                            {formatWeight(Math.abs(animalSeleccionado.ganancia_total))}
                                        </strong>
                                    </span>
                                )}

                                {animalSeleccionado.ganancia_diaria != null && (
                                    <span>
                                        GDP:{" "}
                                        <strong>
                                            {fmtPesoDiario(animalSeleccionado.ganancia_diaria)}
                                        </strong>
                                    </span>
                                )}
                            </div>

                            {/* SIGUIENTE PESAJE PENDIENTE */}
                            {proximaFechaInfo && (
                                proximaFechaInfo.alDia ? (
                                    <p className="mt-3 flex items-center gap-1.5 text-xs text-emerald-600">
                                        <CheckCircle2 size={14} />
                                        Este animal ya tiene pesajes registrados hasta hoy.
                                    </p>
                                ) : (
                                    <div className="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-blue-100 bg-blue-50/70 px-3 py-2">
                                        <p className="text-xs text-blue-700">
                                            Siguiente pesaje pendiente:{" "}
                                            <strong>{formatFechaLegible(proximaFechaInfo.fecha)}</strong>
                                        </p>
                                        <button
                                            type="button"
                                            onClick={() => usarFechaSugerida(proximaFechaInfo.fecha)}
                                            className="flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700 transition"
                                        >
                                            <Plus size={14} /> Usar esta fecha
                                        </button>
                                    </div>
                                )
                            )}
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
                                                    ? `${animal.ganancia_total >= 0 ? "+" : "-"}${formatWeight(Math.abs(animal.ganancia_total))}`
                                                    : "—"}
                                            </p>
                                        </div>

                                        <div className="rounded-xl border border-gray-100 bg-gray-50 p-3">
                                            <p className="text-[11px] text-gray-500">GDP</p>
                                            <p className="text-sm font-medium text-gray-800">
                                                {animal.ganancia_diaria != null
                                                    ? fmtPesoDiario(animal.ganancia_diaria)
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

            <HistorialPesajesModal
                open={modalHistorialOpen}
                animal={animalHistorial}
                onClose={cerrarHistorial}
                handleDelete={handleDelete}
                round2={round2}
                badgeGanancia={badgeGanancia}
            />
        </>
    );
}

export default TabPesajes;