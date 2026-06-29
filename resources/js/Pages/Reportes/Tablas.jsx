// resources/js/Pages/Reportes/Tablas.jsx
import { Children, useEffect, useState } from "react";
import { Beef, HeartPulse, Syringe, Pill, Scale, UtensilsCrossed, Package } from "lucide-react";

// ─── Badge ────────────────────────────────────────────────────────────────────
export function Badge({ estado }) {
    const map = {
        // Salud / vacunación
        aplicada:   "bg-green-100  text-green-700",
        pendiente:  "bg-amber-100  text-amber-700",
        vencida:    "bg-red-100    text-red-700",
        // Tratamientos
        activo:     "bg-blue-100   text-blue-700",
        completado: "bg-gray-100   text-gray-600",
        // Tipos de evento salud
        consulta:   "bg-indigo-100 text-indigo-700",
        vacunacion: "bg-teal-100   text-teal-700",
        revision:   "bg-sky-100    text-sky-700",
        emergencia: "bg-rose-100   text-rose-700",
        // Alimentación
        diaria:     "bg-blue-100   text-blue-700",
        una_vez:    "bg-gray-100   text-gray-600",
        // Reproducción - tipo evento
        celo:       "bg-pink-100   text-pink-700",
        servicio:   "bg-violet-100 text-violet-700",
        diagnostico:"bg-sky-100    text-sky-700",
        parto:      "bg-emerald-100 text-emerald-700",
        aborto:     "bg-red-100    text-red-700",
        destete:    "bg-orange-100 text-orange-700",
        // Reproducción - tipo servicio
        monta_natural:           "bg-green-100  text-green-700",
        inseminacion_artificial: "bg-blue-100   text-blue-700",
        iatf:                    "bg-purple-100 text-purple-700",
        // Diagnóstico gestación
        positivo: "bg-green-100  text-green-700",
        negativo: "bg-red-100    text-red-700",
        repetir:  "bg-amber-100  text-amber-700",
        // Producción
        leche:  "bg-sky-100    text-sky-700",
        lana:   "bg-yellow-100 text-yellow-700",
        carne:  "bg-red-100    text-red-700",
        canal:  "bg-orange-100 text-orange-700",
        // Ventas - estado
        completada: "bg-green-100 text-green-700",
        cancelada:  "bg-gray-100  text-gray-500",
        // Ventas - estado pago
        parcial:    "bg-amber-100 text-amber-700",
        // Ventas - método pago
        efectivo:      "bg-green-100  text-green-700",
        transferencia: "bg-blue-100   text-blue-700",
        tarjeta:       "bg-violet-100 text-violet-700",
        cheque:        "bg-gray-100   text-gray-600",
        // Booleanos
        true:  "bg-green-100 text-green-700",
        false: "bg-gray-100  text-gray-500",
    };

    const label = estado === true ? "Sí" : estado === false ? "No" : estado;

    // Para labels con guión bajo los mostramos más legibles
    const display = typeof label === "string"
        ? label.replace(/_/g, " ")
        : label;

    return (
        <span className={`inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold capitalize ${map[String(estado)] ?? "bg-gray-100 text-gray-500"}`}>
            {display}
        </span>
    );
}

// ─── SectionHeader ────────────────────────────────────────────────────────────
export function SectionHeader({ icon: Icon, title, count, color = "blue" }) {
    const colors = {
        blue:   "text-blue-600   bg-blue-50",
        teal:   "text-teal-600   bg-teal-50",
        amber:  "text-amber-600  bg-amber-50",
        purple: "text-purple-600 bg-purple-50",
        green:  "text-green-600  bg-green-50",
        orange: "text-orange-600 bg-orange-50",
        red:    "text-red-600    bg-red-50",
    };
    return (
        <div className="flex items-center gap-2 mb-3">
            <span className={`rounded-lg p-1.5 ${colors[color] ?? colors.blue}`}>
                <Icon className="w-4 h-4" />
            </span>
            <h2 className="text-base font-semibold text-gray-800">{title}</h2>
            {count !== undefined && (
                <span className="ml-auto text-xs text-gray-400">{count} registros</span>
            )}
        </div>
    );
}

// ─── StatCard ─────────────────────────────────────────────────────────────────
export function StatCard({ icon: Icon, label, value, color = "blue", sub }) {
    const colors = {
        blue:   "bg-blue-50   text-blue-600   border-blue-100",
        green:  "bg-green-50  text-green-600  border-green-100",
        amber:  "bg-amber-50  text-amber-600  border-amber-100",
        red:    "bg-red-50    text-red-600    border-red-100",
        purple: "bg-purple-50 text-purple-600 border-purple-100",
        orange: "bg-orange-50 text-orange-600 border-orange-100",
        teal:   "bg-teal-50   text-teal-600   border-teal-100",
    };
    return (
        <div className={`rounded-2xl border p-4 flex items-center gap-4 ${colors[color]}`}>
            <div className="rounded-xl bg-white p-2.5 shadow-sm">
                <Icon className="w-5 h-5" />
            </div>
            <div>
                <p className="text-2xl font-bold leading-none">{value ?? "—"}</p>
                <p className="text-xs font-medium mt-0.5 opacity-80">{label}</p>
                {sub && <p className="text-[10px] mt-0.5 opacity-60">{sub}</p>}
            </div>
        </div>
    );
}

// ─── Tabla base ───────────────────────────────────────────────────────────────
function Tabla({ headers, children }) {
    const LIMITE_INICIAL = 10;
    const SEGUNDO_LIMITE = 20;

    const [limite, setLimite] = useState(LIMITE_INICIAL);

    // Convierte los <tr> recibidos en un arreglo seguro y elimina valores vacíos.
    const filas = Children.toArray(children).filter(Boolean);

    // La firma cambia cuando llegan otros registros después de aplicar filtros.
    const firmaRegistros = filas
        .map((fila, indice) => fila.key ?? indice)
        .join("|");

    // Cada vez que cambia el resultado del filtro, vuelve a mostrar solo 10.
    useEffect(() => {
        setLimite(LIMITE_INICIAL);
    }, [firmaRegistros]);

    if (filas.length === 0) {
        return (
            <p className="text-sm text-gray-400 py-6 text-center">
                Sin resultados para los filtros seleccionados.
            </p>
        );
    }

    const filasVisibles = filas.slice(0, limite);
    const hayMas = limite < filas.length;

    function mostrarMas() {
        if (limite < SEGUNDO_LIMITE) {
            setLimite(Math.min(SEGUNDO_LIMITE, filas.length));
            return;
        }

        setLimite(filas.length);
    }

    return (
        <div>
            <div className="overflow-x-auto">
                <table className="w-full text-xs">
                    <thead>
                        <tr className="bg-gray-50 text-gray-500 uppercase text-[10px] tracking-wide">
                            {headers.map(h => (
                                <th key={h} className="px-3 py-2 text-left font-semibold whitespace-nowrap">
                                    {h}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>{filasVisibles}</tbody>
                </table>
            </div>

            <div className="mt-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                <p className="text-xs text-gray-500">
                    Mostrando{" "}
                    <span className="font-semibold text-gray-700">
                        {filasVisibles.length}
                    </span>{" "}
                    de{" "}
                    <span className="font-semibold text-gray-700">
                        {filas.length}
                    </span>{" "}
                    registros
                </p>

                {hayMas && (
                    <button
                        type="button"
                        onClick={mostrarMas}
                        className="rounded-xl border border-blue-200 bg-blue-50 px-4 py-2
                                   text-sm font-semibold text-blue-700 transition
                                   hover:bg-blue-100"
                    >
                        {limite < SEGUNDO_LIMITE ? "Ver más" : "Mostrar todo"}
                    </button>
                )}
            </div>
        </div>
    );
}

// ─── Animales ─────────────────────────────────────────────────────────────────
export function TablaAnimales({ registros }) {
    return (
        <Tabla headers={["Arete","Alias","Especie","Raza","Sexo","Fecha Nac.","Peso","BCS","Estado Productivo","Lote"]}>
            {registros?.map(a => (
                <tr key={a.id} className="border-t border-gray-100 hover:bg-blue-50/40 transition-colors">
                    <td className="px-3 py-2 font-mono font-medium text-gray-800">{a.arete}</td>
                    <td className="px-3 py-2 text-gray-600">{a.alias ?? "—"}</td>
                    <td className="px-3 py-2">{a.especie}</td>
                    <td className="px-3 py-2 text-gray-500">{a.raza ?? "—"}</td>
                    <td className="px-3 py-2">
                        <span className={`rounded-full px-2 py-0.5 text-[10px] font-bold ${a.sexo === "M" ? "bg-blue-100 text-blue-700" : "bg-pink-100 text-pink-700"}`}>
                            {a.sexo === "M" ? "Macho" : "Hembra"}
                        </span>
                    </td>
                    <td className="px-3 py-2 text-gray-500">{a.fecha_nac ?? "—"}</td>
                    <td className="px-3 py-2">{a.peso ? `${a.peso} kg` : "—"}</td>
                    <td className="px-3 py-2">{a.BCS ?? "—"}</td>
                    <td className="px-3 py-2 text-gray-500">{a.estado_productivo ?? "—"}</td>
                    <td className="px-3 py-2 text-gray-500">{a.lote?.nombre ?? "—"}</td>
                </tr>
            ))}
        </Tabla>
    );
}

// ─── Salud ────────────────────────────────────────────────────────────────────
export function TablaSalud({ registros }) {
    return (
        <Tabla headers={["Animal","Especie","Lote","Tipo","F. Programada","F. Aplicación","Estado","Diagnóstico","Responsable"]}>
            {registros?.map(ev => (
                <tr key={ev.id} className="border-t border-gray-100 hover:bg-teal-50/40 transition-colors">
                    <td className="px-3 py-2 font-mono font-medium">
                        {ev.animal?.arete}{ev.animal?.alias ? ` (${ev.animal.alias})` : ""}
                    </td>
                    <td className="px-3 py-2">{ev.animal?.especie}</td>
                    <td className="px-3 py-2 text-gray-500">{ev.animal?.lote?.nombre ?? "—"}</td>
                    <td className="px-3 py-2"><Badge estado={ev.tipo} /></td>
                    <td className="px-3 py-2 text-gray-500">{ev.fecha_programada}</td>
                    <td className="px-3 py-2 text-gray-500">{ev.fecha_aplicacion ?? "—"}</td>
                    <td className="px-3 py-2"><Badge estado={ev.estado} /></td>
                    <td className="px-3 py-2 text-gray-500 max-w-[180px] truncate">{ev.diagnostico ?? "—"}</td>
                    <td className="px-3 py-2 text-gray-500">{ev.responsable ?? "—"}</td>
                </tr>
            ))}
        </Tabla>
    );
}

// ─── Vacunación ───────────────────────────────────────────────────────────────
export function TablaVacunacion({ registros }) {
    return (
        <Tabla headers={["Animal","Lote","Vacuna","Dosis","Lote Vacuna","F. Programada","F. Aplicación","Estado","Responsable"]}>
            {registros?.map(ev => (
                <tr key={ev.id} className="border-t border-gray-100 hover:bg-amber-50/40 transition-colors">
                    <td className="px-3 py-2 font-mono font-medium">
                        {ev.animal?.arete}{ev.animal?.alias ? ` (${ev.animal.alias})` : ""}
                    </td>
                    <td className="px-3 py-2 text-gray-500">{ev.animal?.lote?.nombre ?? "—"}</td>
                    <td className="px-3 py-2 font-medium">{ev.vacuna?.nombre ?? "—"}</td>
                    <td className="px-3 py-2">{ev.dosis ?? "—"}</td>
                    <td className="px-3 py-2 text-gray-500">{ev.lote_vacuna ?? "—"}</td>
                    <td className="px-3 py-2 text-gray-500">{ev.fecha_programada}</td>
                    <td className="px-3 py-2 text-gray-500">{ev.fecha_aplicacion ?? "—"}</td>
                    <td className="px-3 py-2"><Badge estado={ev.estado} /></td>
                    <td className="px-3 py-2 text-gray-500">{ev.responsable ?? "—"}</td>
                </tr>
            ))}
        </Tabla>
    );
}

// ─── Tratamientos ─────────────────────────────────────────────────────────────
export function TablaTratamientos({ registros }) {
    return (
        <Tabla headers={["Animal","Lote","Tratamiento","F. Inicio","F. Fin prevista","Estado","Responsable","Notas"]}>
            {registros?.map(tr => (
                <tr key={tr.id} className="border-t border-gray-100 hover:bg-purple-50/40 transition-colors">
                    <td className="px-3 py-2 font-mono font-medium">
                        {tr.animal?.arete}{tr.animal?.alias ? ` (${tr.animal.alias})` : ""}
                    </td>
                    <td className="px-3 py-2 text-gray-500">{tr.animal?.lote?.nombre ?? "—"}</td>
                    <td className="px-3 py-2 font-medium">{tr.nombre}</td>
                    <td className="px-3 py-2 text-gray-500">{tr.fecha_inicio}</td>
                    <td className="px-3 py-2 text-gray-500">{tr.fecha_fin ?? "—"}</td>
                    <td className="px-3 py-2"><Badge estado={tr.estado} /></td>
                    <td className="px-3 py-2 text-gray-500">{tr.responsable ?? "—"}</td>
                    <td className="px-3 py-2 text-gray-400 max-w-[180px] truncate">{tr.notas ?? "—"}</td>
                </tr>
            ))}
        </Tabla>
    );
}

// ─── Pesajes ──────────────────────────────────────────────────────────────────
// FIX: la variación ahora valida que el registro anterior sea del MISMO animal.
// El backend ordena por animal_id + fecha desc, así que registros[i+1] puede ser
// del mismo animal (siguiente pesaje) o de uno diferente (primer pesaje del grupo).
export function TablaPesajes({ registros }) {
    return (
        <Tabla headers={["Animal","Alias","Especie","Lote","Fecha","Peso (kg)","Variación","Notas"]}>
            {registros?.map((p, i) => {
                const anterior  = registros[i + 1];
                // Solo mostrar variación si el anterior es del mismo animal
                const mismoAnimal = anterior?.animal?.id != null && anterior.animal.id === p.animal?.id;
                const variacion   = mismoAnimal
                    ? (p.peso - anterior.peso).toFixed(2)
                    : null;

                return (
                    <tr key={p.id} className="border-t border-gray-100 hover:bg-green-50/40 transition-colors">
                        <td className="px-3 py-2 font-mono font-medium">{p.animal?.arete ?? "—"}</td>
                        <td className="px-3 py-2 text-gray-500">{p.animal?.alias ?? "—"}</td>
                        <td className="px-3 py-2">{p.animal?.especie ?? "—"}</td>
                        <td className="px-3 py-2 text-gray-500">{p.animal?.lote?.nombre ?? "—"}</td>
                        <td className="px-3 py-2 text-gray-500">{p.fecha}</td>
                        <td className="px-3 py-2 font-semibold">{p.peso} kg</td>
                        <td className="px-3 py-2">
                            {variacion !== null ? (
                                <span className={`font-medium ${parseFloat(variacion) >= 0 ? "text-green-600" : "text-red-500"}`}>
                                    {parseFloat(variacion) >= 0 ? "+" : ""}{variacion} kg
                                </span>
                            ) : "—"}
                        </td>
                        <td className="px-3 py-2 text-gray-400">{p.notas ?? "—"}</td>
                    </tr>
                );
            })}
        </Tabla>
    );
}

// ─── Alimentación ─────────────────────────────────────────────────────────────
// FIX: se leía a.snapshot_nutricion?.MS que no existe en el modelo;
// los campos nutricionales están en la relación `racion`.
export function TablaAlimentacion({ registros }) {
    return (
        <Tabla headers={["Fecha","Hora","Ración","Animal / Lote","Cantidad","Unidad","MS%","PB%","Notas"]}>
            {registros?.map(a => (
                <tr key={a.id} className="border-t border-gray-100 hover:bg-orange-50/40 transition-colors">
                    <td className="px-3 py-2 text-gray-500">{a.fecha}</td>
                    <td className="px-3 py-2 text-gray-400">{a.hora ?? "—"}</td>
                    <td className="px-3 py-2 font-medium">{a.racion?.nombre ?? "—"}</td>
                    <td className="px-3 py-2">
                        {a.animal
                            ? <span className="font-mono">{a.animal.arete}{a.animal.alias ? ` (${a.animal.alias})` : ""}</span>
                            : a.lote
                            ? <span className="text-gray-600">Lote: {a.lote.nombre}</span>
                            : "—"}
                    </td>
                    <td className="px-3 py-2 font-semibold">{a.cantidad}</td>
                    <td className="px-3 py-2 text-gray-500">{a.unidad}</td>
                    {/* FIX: leer desde racion, no desde snapshot_nutricion */}
                    <td className="px-3 py-2 text-gray-500">{a.racion?.MS ?? "—"}</td>
                    <td className="px-3 py-2 text-gray-500">{a.racion?.PB ?? "—"}</td>
                    <td className="px-3 py-2 text-gray-400 max-w-[160px] truncate">{a.notas ?? "—"}</td>
                </tr>
            ))}
        </Tabla>
    );
}

// ─── Inventario ───────────────────────────────────────────────────────────────
export function TablaInventario({ registros }) {
    return (
        <Tabla headers={["Nombre","Tipo","Existencias","Unidad","MS%","PB%","Costo prom.","Activo"]}>
            {registros?.map(inv => (
                <tr key={inv.id} className="border-t border-gray-100 hover:bg-blue-50/40 transition-colors">
                    <td className="px-3 py-2 font-medium">{inv.nombre}</td>
                    <td className="px-3 py-2 text-gray-500">{inv.tipo}</td>
                    <td className="px-3 py-2 font-semibold">{inv.existencias}</td>
                    <td className="px-3 py-2 text-gray-500">{inv.unidad ?? "—"}</td>
                    <td className="px-3 py-2 text-gray-500">{inv.MS ?? "—"}</td>
                    <td className="px-3 py-2 text-gray-500">{inv.PB ?? "—"}</td>
                    <td className="px-3 py-2">{inv.costo_promedio ? `$${inv.costo_promedio}` : "—"}</td>
                    <td className="px-3 py-2"><Badge estado={inv.activo} /></td>
                </tr>
            ))}
        </Tabla>
    );
}

// ─── Reproducción — eventos generales ────────────────────────────────────────
export function TablaEventosReproductivos({ registros }) {
    return (
        <Tabla headers={["Hembra","Especie","Lote","Tipo Evento","Fecha","Costo","Observaciones"]}>
            {registros?.map(ev => (
                <tr key={ev.id} className="border-t border-gray-100 hover:bg-rose-50/40 transition-colors">
                    <td className="px-3 py-2 font-mono font-medium">
                        {ev.hembra?.arete}{ev.hembra?.alias ? ` (${ev.hembra.alias})` : ""}
                    </td>
                    <td className="px-3 py-2">{ev.hembra?.especie ?? "—"}</td>
                    <td className="px-3 py-2 text-gray-500">{ev.lote?.nombre ?? "—"}</td>
                    <td className="px-3 py-2"><Badge estado={ev.tipo_evento} /></td>
                    <td className="px-3 py-2 text-gray-500">{ev.fecha}</td>
                    <td className="px-3 py-2">{ev.costo ? `$${ev.costo}` : "—"}</td>
                    <td className="px-3 py-2 text-gray-400 max-w-[200px] truncate">{ev.observaciones ?? "—"}</td>
                </tr>
            ))}
        </Tabla>
    );
}

// ─── Reproducción — servicios ─────────────────────────────────────────────────
export function TablaServicios({ registros }) {
    return (
        <Tabla headers={["Hembra","Lote","Tipo Servicio","Fecha","# Servicio","Macho / Pajilla","Técnico","Costo"]}>
            {registros?.map(ev => {
                const srv = ev.servicio;
                return (
                    <tr key={ev.id} className="border-t border-gray-100 hover:bg-pink-50/40 transition-colors">
                        <td className="px-3 py-2 font-mono font-medium">
                            {ev.hembra?.arete}{ev.hembra?.alias ? ` (${ev.hembra.alias})` : ""}
                        </td>
                        <td className="px-3 py-2 text-gray-500">{ev.lote?.nombre ?? "—"}</td>
                        <td className="px-3 py-2"><Badge estado={srv?.tipo_servicio} /></td>
                        <td className="px-3 py-2 text-gray-500">{ev.fecha}</td>
                        <td className="px-3 py-2 text-center">{srv?.numero_servicio ?? "—"}</td>
                        <td className="px-3 py-2">
                            {srv?.macho
                                ? <span className="font-mono">{srv.macho.arete}</span>
                                : srv?.pajilla_codigo
                                ? <span className="text-gray-500">🧬 {srv.pajilla_codigo}{srv.pajilla.donador?.raza ? ` (${srv.pajilla.donador.raza})` : ""}</span>
                                : "—"}
                        </td>
                        <td className="px-3 py-2 text-gray-500">
                            {srv?.tecnico?.name ?? srv?.tecnico_externo ?? "—"}
                        </td>
                        <td className="px-3 py-2">{ev.costo ? `$${ev.costo}` : "—"}</td>
                    </tr>
                );
            })}
        </Tabla>
    );
}

// ─── Reproducción — diagnósticos de gestación ────────────────────────────────
export function TablaDiagnosticos({ registros }) {
    return (
        <Tabla headers={["Hembra","Lote","Fecha Diagnóstico","Método","Resultado","Días Gest.","F. Probable Parto","Veterinario"]}>
            {registros?.map(ev => {
                const dx = ev.diagnostico;
                return (
                    <tr key={ev.id} className="border-t border-gray-100 hover:bg-purple-50/40 transition-colors">
                        <td className="px-3 py-2 font-mono font-medium">
                            {ev.hembra?.arete}{ev.hembra?.alias ? ` (${ev.hembra.alias})` : ""}
                        </td>
                        <td className="px-3 py-2 text-gray-500">{ev.lote?.nombre ?? "—"}</td>
                        <td className="px-3 py-2 text-gray-500">{ev.fecha}</td>
                        <td className="px-3 py-2 text-gray-500">{dx?.metodo?.replace(/_/g, " ") ?? "—"}</td>
                        <td className="px-3 py-2">
                            {dx?.resultado ? <Badge estado={dx.resultado} /> : "—"}
                        </td>
                        <td className="px-3 py-2 text-center">{dx?.dias_gestacion_estimados ?? "—"}</td>
                        <td className="px-3 py-2 text-gray-500">{dx?.fecha_probable_parto ?? "—"}</td>
                        <td className="px-3 py-2 text-gray-500">
                            {dx?.veterinario?.name ?? dx?.veterinario_externo ?? "—"}
                        </td>
                    </tr>
                );
            })}
        </Tabla>
    );
}

// ─── Reproducción — partos ────────────────────────────────────────────────────
export function TablaPartos({ registros }) {
    return (
        <Tabla headers={["Hembra","Lote","Fecha Parto","Tipo","# Crías","Asistencia","Complicaciones","Crías (aretes)"]}>
            {registros?.map(ev => {
                const p = ev.parto;
                return (
                    <tr key={ev.id} className="border-t border-gray-100 hover:bg-emerald-50/40 transition-colors">
                        <td className="px-3 py-2 font-mono font-medium">
                            {ev.hembra?.arete}{ev.hembra?.alias ? ` (${ev.hembra.alias})` : ""}
                        </td>
                        <td className="px-3 py-2 text-gray-500">{ev.lote?.nombre ?? "—"}</td>
                        <td className="px-3 py-2 text-gray-500">{ev.fecha}</td>
                        <td className="px-3 py-2"><Badge estado={p?.tipo_parto} /></td>
                        <td className="px-3 py-2 text-center font-semibold">{p?.numero_crias ?? "—"}</td>
                        <td className="px-3 py-2"><Badge estado={p?.asistencia_requerida} /></td>
                        <td className="px-3 py-2"><Badge estado={p?.complicaciones} /></td>
                        <td className="px-3 py-2 text-gray-500">
                            {p?.crias?.map(c => (
                                <span key={c.id} className="inline-block mr-1 font-mono text-[10px] bg-gray-100 rounded px-1">
                                    {c.animal?.arete ?? c.arete_temporal ?? "s/a"} ({c.sexo?.[0]?.toUpperCase() ?? "?"})
                                </span>
                            ))}
                        </td>
                    </tr>
                );
            })}
        </Tabla>
    );
}

// ─── Producción (nuevo) ───────────────────────────────────────────────────────
export function TablaProduccion({ registros }) {
    return (
        <Tabla headers={["Animal","Alias","Especie","Lote","Fecha","Tipo","Valor","Unidad"]}>
            {registros?.map(p => (
                <tr key={p.id} className="border-t border-gray-100 hover:bg-sky-50/40 transition-colors">
                    <td className="px-3 py-2 font-mono font-medium">{p.animal?.arete ?? "—"}</td>
                    <td className="px-3 py-2 text-gray-500">{p.animal?.alias ?? "—"}</td>
                    <td className="px-3 py-2">{p.animal?.especie ?? "—"}</td>
                    <td className="px-3 py-2 text-gray-500">{p.animal?.lote?.nombre ?? "—"}</td>
                    <td className="px-3 py-2 text-gray-500">{p.fecha}</td>
                    <td className="px-3 py-2"><Badge estado={p.tipo} /></td>
                    <td className="px-3 py-2 font-semibold">
                        {p.valor != null ? Number(p.valor).toLocaleString() : "—"}
                    </td>
                    <td className="px-3 py-2 text-gray-500">{p.unidad ?? "—"}</td>
                </tr>
            ))}
        </Tabla>
    );
}

// ─── Ventas (nuevo) ───────────────────────────────────────────────────────────
export function TablaVentas({ registros }) {
    return (
        <Tabla headers={[
            "Fecha","Factura","Tipo","Producto",
            "Cant.","Unidad","P. Unitario","P. Total",
            "Método Pago","Est. Venta","Est. Pago",
            "Comprador","Vendedor","Observaciones"
        ]}>
            {registros?.map(v => (
                <tr key={v.id} className="border-t border-gray-100 hover:bg-green-50/40 transition-colors">
                    <td className="px-3 py-2 text-gray-500 whitespace-nowrap">{v.fecha_venta}</td>
                    <td className="px-3 py-2 font-mono text-gray-500">{v.numero_factura ?? "—"}</td>
                    <td className="px-3 py-2"><Badge estado={v.tipo_venta} /></td>
                    <td className="px-3 py-2 font-medium max-w-[140px] truncate">{v.producto}</td>
                    <td className="px-3 py-2 text-right font-semibold">
                        {v.cantidad != null ? Number(v.cantidad).toLocaleString() : "—"}
                    </td>
                    <td className="px-3 py-2 text-gray-500">{v.unidad}</td>
                    <td className="px-3 py-2 text-right">
                        {v.precio_unitario != null ? `$${Number(v.precio_unitario).toLocaleString()}` : "—"}
                    </td>
                    <td className="px-3 py-2 text-right font-semibold text-green-700">
                        {v.precio_total != null ? `$${Number(v.precio_total).toLocaleString()}` : "—"}
                    </td>
                    <td className="px-3 py-2"><Badge estado={v.metodo_pago} /></td>
                    <td className="px-3 py-2"><Badge estado={v.estado_venta} /></td>
                    <td className="px-3 py-2"><Badge estado={v.estado_pago} /></td>
                    <td className="px-3 py-2 text-gray-500">{v.comprador?.nombre ?? "—"}</td>
                    <td className="px-3 py-2 text-gray-500">{v.vendedor?.name ?? "—"}</td>
                    <td className="px-3 py-2 text-gray-400 max-w-[160px] truncate">{v.observaciones ?? "—"}</td>
                </tr>
            ))}
        </Tabla>
    );
}