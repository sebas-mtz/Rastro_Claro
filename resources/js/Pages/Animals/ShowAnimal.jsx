import React, { useState } from "react";
import { Head, Link } from "@inertiajs/react";
import { ArrowLeft, PawPrint, Edit, PlusCircle, Eye, Camera, Scale, Utensils, GitBranch } from "lucide-react";
import {
    LineChart, Line, XAxis, YAxis, CartesianGrid,
    Tooltip, ResponsiveContainer,
} from "recharts";
import EditModal from "./Edit";
import ProduccionModal from "../Producciones/ProduccionModal";
import ShowProduccionModal from "../Producciones/ShowProduccionModal";
import ProduccionEditModal from "../Producciones/ProduccionEditModal";

export default function ShowAnimal({
    animal,
    lotes,
    especies,
    razasPorEspecie,
    estadosProductivos,
}) {
    const [selectedAnimal, setSelectedAnimal] = useState(null);
    const [isEditOpen, setIsEditOpen]         = useState(false);
    const [showAddProduccion, setShowAddProduccion] = useState(false);
    const [showProduccionList, setShowProduccionList] = useState(false);
    const [editProduccion, setEditProduccion] = useState(null);

    function calcularEdad(fechaNac) {
        if (!fechaNac) return "N/D";
        const nacimiento = new Date(fechaNac);
        const hoy = new Date();
        let años = hoy.getFullYear() - nacimiento.getFullYear();
        const mes = hoy.getMonth() - nacimiento.getMonth();
        if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) años--;
        return `${años} año${años !== 1 ? "s" : ""}`;
    }

    const fmtFecha = (f) => f ? new Date(f).toLocaleDateString("es-MX") : "N/D";
    const fmtPeso  = (v) => v != null ? `${Number(v).toFixed(2)} kg` : "—";

    const pesajes        = animal.pesajes ?? [];
    const alimentaciones = animal.alimentaciones ?? [];

    const chartDataPeso = pesajes.map((p) => ({
        fecha: p.fecha,
        peso:  parseFloat(p.peso),
    }));

    const pesoInicial  = pesajes.length > 0 ? parseFloat(pesajes[0].peso) : null;
    const pesoActual   = pesajes.length > 0 ? parseFloat(pesajes[pesajes.length - 1].peso) : null;
    const gananciaPeso = pesoInicial != null && pesoActual != null
        ? Math.round((pesoActual - pesoInicial) * 100) / 100
        : null;

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col items-center py-10 px-4">
            <Head title={`Animal ${animal.arete}`} />

            <div className="w-full max-w-4xl space-y-6">

                {/* ── Card principal ───────────────────────────────────────── */}
                <div className="bg-white shadow-xl rounded-2xl p-8 border border-gray-200">

                    {/* Header */}
                    <div className="flex items-center justify-between mb-6">
                        <div className="flex items-center gap-3">
                            <PawPrint className="text-green-600 w-7 h-7" />
                            <h1 className="text-2xl font-semibold text-gray-800">
                                {animal.alias || animal.especie}
                            </h1>
                        </div>
                        <a href="/animales" className="flex items-center text-sm text-green-700 hover:text-green-800 transition">
                            <ArrowLeft className="w-4 h-4 mr-1" /> Volver
                        </a>
                    </div>

                    {/* Barra de acciones */}
                    <div className="flex justify-between items-center mb-4">
                        <h2 className="text-lg font-semibold text-gray-700">Datos Generales</h2>
                        <div className="flex gap-2">
                            <button
                                onClick={() => setShowProduccionList(true)}
                                className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition"
                            >
                                <Eye className="w-4 h-4" /> Ver Producción Diaria
                            </button>

                            {/* ── Genealogía ── */}
                            <Link
                                href={route('genealogias.show', animal.id)}
                                className="flex items-center gap-2 px-4 py-2 bg-amber-500 text-white text-sm rounded-lg hover:bg-amber-600 transition"
                            >
                                <GitBranch className="w-4 h-4" /> Genealogía
                            </Link>

                            <button
                                onClick={() => { setSelectedAnimal(animal); setIsEditOpen(true); }}
                                className="flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition"
                            >
                                <Edit className="w-4 h-4" /> Editar Datos
                            </button>
                        </div>
                    </div>

                    <div className="grid md:grid-cols-2 gap-8">
                        <div className="flex justify-center items-center">
                            <div className="relative">
                                <div className="w-48 h-48 rounded-full border-2 border-dashed border-gray-300 bg-gray-50 flex flex-col items-center justify-center hover:bg-gray-100 transition cursor-pointer">
                                    <Camera className="w-12 h-12 text-gray-400 mb-2" />
                                    <span className="text-sm text-gray-500 text-center px-4">Agregar imagen</span>
                                </div>
                                <div className="absolute -bottom-2 left-1/2 transform -translate-x-1/2 bg-green-600 text-white px-3 py-1 rounded-full text-xs font-medium">
                                    {animal.especie}
                                </div>
                            </div>
                        </div>

                        <div className="space-y-3">
                            <Data label="Especie"             value={animal.especie} />
                            <Data label="Raza"                value={animal.raza || "N/D"} />
                            <Data label="Sexo"                value={animal.sexo === "M" ? "Macho" : "Hembra"} />
                            <Data label="Arete"               value={animal.arete} />
                            <Data label="Estado Productivo"   value={animal.estado_productivo || "N/D"} />
                            <Data label="Fecha de Nacimiento" value={fmtFecha(animal.fecha_nac)} />
                            <Data label="Edad"                value={calcularEdad(animal.fecha_nac)} />
                            <Data label="Peso actual"         value={pesoActual != null ? fmtPeso(pesoActual) : (animal.peso ? fmtPeso(animal.peso) : "N/D")} />
                            <Data label="BCS"                 value={animal.BCS || "N/D"} />
                            <Data label="Lote"                value={animal.lote?.nombre || "Sin lote"} />
                            <Data label="Fecha de Registro"   value={fmtFecha(animal.created_at)} />

                            {/* Madre — solo si está registrada */}
                            {animal.madre_id && (
                                <div className="flex justify-between border-b border-gray-100 py-1">
                                    <span className="text-gray-600 font-medium">Madre</span>
                                    <Link
                                        href={route('animales.show', animal.madre_id)}
                                        className="text-rose-600 hover:underline text-sm font-medium"
                                    >
                                        {animal.madre?.arete ?? `#${animal.madre_id}`}
                                    </Link>
                                </div>
                            )}

                            {/* Padre — solo si está registrado */}
                            {animal.padre_id && (
                                <div className="flex justify-between border-b border-gray-100 py-1">
                                    <span className="text-gray-600 font-medium">Padre</span>
                                    <Link
                                        href={route('animales.show', animal.padre_id)}
                                        className="text-sky-600 hover:underline text-sm font-medium"
                                    >
                                        {animal.padre?.arete ?? `#${animal.padre_id}`}
                                    </Link>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* ── Card: Historial de Peso ──────────────────────────────── */}
                <div className="bg-white shadow-xl rounded-2xl p-6 border border-gray-200">
                    <div className="flex items-center justify-between mb-4">
                        <div className="flex items-center gap-2">
                            <Scale className="w-5 h-5 text-blue-600" />
                            <h2 className="text-lg font-semibold text-gray-700">Historial de Peso</h2>
                        </div>
                        <Link href={route("pesajes.index")} className="text-sm text-blue-600 hover:text-blue-800 transition">
                            Ir a Pesajes →
                        </Link>
                    </div>

                    {pesajes.length === 0 ? (
                        <div className="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-6 text-center">
                            <Scale className="mx-auto mb-2 h-8 w-8 text-gray-300" />
                            <p className="text-sm text-gray-500">No hay pesajes registrados para este animal.</p>
                            <Link href={route("pesajes.index")} className="mt-2 inline-block text-xs text-blue-600 hover:underline">
                                Registrar primer pesaje
                            </Link>
                        </div>
                    ) : (
                        <>
                            <div className="mb-5 grid grid-cols-3 gap-3">
                                <div className="rounded-xl border border-gray-100 bg-gray-50 p-3 text-center">
                                    <p className="text-[11px] text-gray-500">Peso inicial</p>
                                    <p className="text-base font-semibold text-gray-800">{fmtPeso(pesoInicial)}</p>
                                    <p className="text-[10px] text-gray-400">{pesajes[0].fecha}</p>
                                </div>
                                <div className="rounded-xl border border-gray-100 bg-gray-50 p-3 text-center">
                                    <p className="text-[11px] text-gray-500">Peso actual</p>
                                    <p className="text-base font-semibold text-blue-700">{fmtPeso(pesoActual)}</p>
                                    <p className="text-[10px] text-gray-400">{pesajes[pesajes.length - 1].fecha}</p>
                                </div>
                                <div className={`rounded-xl border p-3 text-center ${
                                    gananciaPeso > 0 ? "border-emerald-100 bg-emerald-50" :
                                    gananciaPeso < 0 ? "border-red-100 bg-red-50" :
                                    "border-gray-100 bg-gray-50"
                                }`}>
                                    <p className="text-[11px] text-gray-500">Ganancia total</p>
                                    <p className={`text-base font-semibold ${
                                        gananciaPeso > 0 ? "text-emerald-700" :
                                        gananciaPeso < 0 ? "text-red-600" : "text-gray-600"
                                    }`}>
                                        {gananciaPeso != null ? `${gananciaPeso >= 0 ? "+" : ""}${gananciaPeso} kg` : "—"}
                                    </p>
                                    <p className="text-[10px] text-gray-400">{pesajes.length} pesaje(s)</p>
                                </div>
                            </div>

                            {chartDataPeso.length > 1 && (
                                <div className="mb-5">
                                    <ResponsiveContainer width="100%" height={200}>
                                        <LineChart data={chartDataPeso} margin={{ top: 5, right: 10, left: 0, bottom: 5 }}>
                                            <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                                            <XAxis
                                                dataKey="fecha" tick={{ fontSize: 10 }}
                                                tickFormatter={(f) => { const [,m,d] = f.split("-"); return `${d}/${m}`; }}
                                            />
                                            <YAxis tick={{ fontSize: 10 }} tickFormatter={(v) => `${v} kg`} width={60} />
                                            <Tooltip
                                                formatter={(v) => [`${Number(v).toFixed(2)} kg`, "Peso"]}
                                                labelFormatter={(l) => `Fecha: ${l}`}
                                            />
                                            <Line type="monotone" dataKey="peso" stroke="#3b82f6" strokeWidth={2} dot={{ r: 4 }} activeDot={{ r: 6 }} />
                                        </LineChart>
                                    </ResponsiveContainer>
                                </div>
                            )}

                            <table className="w-full text-sm border border-gray-100 rounded-xl overflow-hidden">
                                <thead className="bg-gray-50 text-gray-600">
                                    <tr>
                                        <th className="p-2 text-left font-medium">Fecha</th>
                                        <th className="p-2 text-right font-medium">Peso</th>
                                        <th className="p-2 text-right font-medium">Δ</th>
                                        <th className="p-2 text-left font-medium">Notas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {[...pesajes]
                                        .sort((a, b) => b.fecha.localeCompare(a.fecha))
                                        .slice(0, 4)
                                        .map((p, idx, arr) => {
                                            const siguiente = arr[idx + 1];
                                            const delta = siguiente
                                                ? Math.round((parseFloat(p.peso) - parseFloat(siguiente.peso)) * 100) / 100
                                                : null;
                                            return (
                                                <tr key={p.id} className="border-t border-gray-100 hover:bg-gray-50">
                                                    <td className="p-2 text-gray-700">{p.fecha}</td>
                                                    <td className="p-2 text-right font-medium text-gray-800">{fmtPeso(p.peso)}</td>
                                                    <td className="p-2 text-right">
                                                        {delta != null ? (
                                                            <span className={`text-xs font-medium ${delta > 0 ? "text-emerald-600" : delta < 0 ? "text-red-500" : "text-gray-400"}`}>
                                                                {delta >= 0 ? "+" : ""}{delta} kg
                                                            </span>
                                                        ) : <span className="text-gray-300 text-xs">—</span>}
                                                    </td>
                                                    <td className="p-2 text-gray-400 text-xs">{p.notas || "—"}</td>
                                                </tr>
                                            );
                                        })}
                                </tbody>
                            </table>

                            {pesajes.length > 4 && (
                                <div className="mt-2 text-right">
                                    <Link href={route("pesajes.index")} className="text-xs text-blue-600 hover:underline">
                                        Ver historial completo ({pesajes.length} pesajes) →
                                    </Link>
                                </div>
                            )}
                        </>
                    )}
                </div>

                {/* ── Card: Dieta ──────────────────────────────────────────── */}
                <div className="bg-white shadow-xl rounded-2xl p-6 border border-gray-200">
                    <div className="flex items-center justify-between mb-4">
                        <div className="flex items-center gap-2">
                            <Utensils className="w-5 h-5 text-green-600" />
                            <h2 className="text-lg font-semibold text-gray-700">Dieta y Alimentación</h2>
                        </div>
                        <Link href={route("alimentacion.index")} className="text-sm text-blue-600 hover:text-blue-800 transition">
                            Ir a Alimentación →
                        </Link>
                    </div>

                    {alimentaciones.length === 0 ? (
                        <div className="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-6 text-center">
                            <Utensils className="mx-auto mb-2 h-8 w-8 text-gray-300" />
                            <p className="text-sm text-gray-500">No hay registros de alimentación para este animal.</p>
                        </div>
                    ) : (
                        <>
                            <div className="mb-4 flex flex-wrap gap-3 text-xs text-gray-600">
                                <span className="rounded-full border border-gray-200 bg-gray-50 px-3 py-1">
                                    {alimentaciones.length} registro(s) recientes
                                </span>
                                {(() => {
                                    const porRacion = {};
                                    alimentaciones.forEach((a) => {
                                        const nombre = a.racion?.nombre ?? "Sin ración";
                                        porRacion[nombre] = (porRacion[nombre] ?? 0) + 1;
                                    });
                                    const [racMasUsada] = Object.entries(porRacion).sort((a, b) => b[1] - a[1]);
                                    return racMasUsada ? (
                                        <span className="rounded-full border border-green-200 bg-green-50 px-3 py-1 text-green-700">
                                            Ración más usada: <strong>{racMasUsada[0]}</strong>
                                        </span>
                                    ) : null;
                                })()}
                            </div>

                            <table className="w-full text-sm border border-gray-100 rounded-xl overflow-hidden">
                                <thead className="bg-gray-50 text-gray-600">
                                    <tr>
                                        <th className="p-2 text-left font-medium">Fecha</th>
                                        <th className="p-2 text-left font-medium">Ración</th>
                                        <th className="p-2 text-right font-medium">Cantidad</th>
                                        <th className="p-2 text-left font-medium">Notas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {alimentaciones.map((a) => (
                                        <tr key={a.id} className="border-t border-gray-100 hover:bg-gray-50">
                                            <td className="p-2 text-gray-700">{a.fecha}</td>
                                            <td className="p-2 text-gray-800 font-medium">
                                                {a.racion?.nombre ?? <span className="text-gray-400 text-xs">Sin ración</span>}
                                            </td>
                                            <td className="p-2 text-right text-gray-700">{a.cantidad} {a.unidad}</td>
                                            <td className="p-2 text-gray-400 text-xs">{a.notas || "—"}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>

                            <div className="mt-2 text-right">
                                <Link href={route("alimentacion.index")} className="text-xs text-blue-600 hover:underline">
                                    Ver historial completo en Alimentación →
                                </Link>
                            </div>
                        </>
                    )}
                </div>

                {/* ── Card: Producción ─────────────────────────────────────── */}
                <div className="bg-white shadow-xl rounded-2xl p-6 border border-gray-200">
                    <div className="flex justify-between items-center mb-4">
                        <h2 className="text-lg font-semibold text-gray-700">Últimos Registros de Producción</h2>
                        <button
                            onClick={() => setShowAddProduccion(true)}
                            className="flex items-center gap-2 px-3 py-2 bg-green-500 text-white text-sm rounded-lg hover:bg-green-600 transition"
                        >
                            <PlusCircle className="w-4 h-4" /> Agregar Registro
                        </button>
                    </div>

                    {animal.producciones && animal.producciones.length > 0 ? (
                        <table className="w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
                            <thead className="bg-gray-100 text-gray-700">
                                <tr>
                                    <th className="p-2">Fecha</th>
                                    <th className="p-2">Tipo</th>
                                    <th className="p-2">Valor</th>
                                    <th className="p-2">Unidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                {animal.producciones.slice(0, 3).map((p) => (
                                    <tr key={p.id} className="border-t text-center hover:bg-gray-50">
                                        <td className="p-2">{fmtFecha(p.fecha)}</td>
                                        <td className="p-2 capitalize">{p.tipo}</td>
                                        <td className="p-2">{p.valor ?? "N/D"}</td>
                                        <td className="p-2">{p.unidad ?? "N/D"}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    ) : (
                        <p className="text-gray-500 text-sm">No hay registros de producción.</p>
                    )}
                </div>
            </div>

            {/* Modales */}
            {isEditOpen && selectedAnimal && (
                <EditModal
                    animal={selectedAnimal}
                    lotes={lotes}
                    especies={especies}
                    razasPorEspecie={razasPorEspecie}
                    estadosProductivos={estadosProductivos}
                    onClose={() => setIsEditOpen(false)}
                />
            )}
            {showAddProduccion && (
                <ProduccionModal show={showAddProduccion} onClose={() => setShowAddProduccion(false)} animal={animal} />
            )}
            {showProduccionList && (
                <ShowProduccionModal
                    producciones={animal.producciones}
                    onClose={() => setShowProduccionList(false)}
                    onEdit={(prod) => { setEditProduccion(prod); setShowProduccionList(false); }}
                />
            )}
            {editProduccion && (
                <ProduccionEditModal produccion={editProduccion} onClose={() => setEditProduccion(null)} />
            )}
        </div>
    );
}

function Data({ label, value }) {
    return (
        <div className="flex justify-between border-b border-gray-100 py-1">
            <span className="text-gray-600 font-medium">{label}</span>
            <span className="text-gray-800">{value}</span>
        </div>
    );
}