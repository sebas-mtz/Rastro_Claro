// resources/js/Pages/Reportes/FichaAnimal.jsx
import {
    PawPrint, Scale, Utensils, HeartPulse, Syringe,
    Pill, Milk, Baby, GitBranch, TrendingUp, TrendingDown,
    Minus, AlertTriangle,
} from "lucide-react";
import {
    LineChart, Line, XAxis, YAxis, CartesianGrid,
    Tooltip, ResponsiveContainer,
} from "recharts";
import { Badge, SectionHeader } from "./Tablas";

// ─── Helpers ──────────────────────────────────────────────────────────────────
const fmtFecha = (f) => f ? new Date(f).toLocaleDateString("es-MX") : "N/D";
const fmtPeso  = (v) => v != null ? `${Number(v).toFixed(2)} kg` : "—";

function calcularEdad(fechaNac) {
    if (!fechaNac) return "N/D";
    const nac = new Date(fechaNac);
    const hoy = new Date();
    let años = hoy.getFullYear() - nac.getFullYear();
    const mes = hoy.getMonth() - nac.getMonth();
    if (mes < 0 || (mes === 0 && hoy.getDate() < nac.getDate())) años--;
    return `${años} año${años !== 1 ? "s" : ""}`;
}

// ─── Fila de dato ─────────────────────────────────────────────────────────────
function Dato({ label, value, highlight }) {
    return (
        <div className="flex justify-between border-b border-gray-100 py-1.5">
            <span className="text-gray-500 text-xs font-medium">{label}</span>
            <span className={`text-xs font-semibold ${highlight ? "text-blue-600" : "text-gray-800"}`}>{value}</span>
        </div>
    );
}

// ─── Card contenedor ──────────────────────────────────────────────────────────
function Card({ icon, title, color = "blue", children, extra }) {
    return (
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div className="flex items-center justify-between mb-4">
                <SectionHeader icon={icon} title={title} color={color} />
                {extra}
            </div>
            {children}
        </div>
    );
}

// ─── Tabla interior reutilizable ──────────────────────────────────────────────
function MiniTabla({ headers, rows, emptyMsg = "Sin registros." }) {
    if (!rows || rows.length === 0)
        return <p className="text-sm text-gray-400 py-4 text-center">{emptyMsg}</p>;
    return (
        <div className="overflow-x-auto">
            <table className="w-full text-xs">
                <thead>
                    <tr className="bg-gray-50 text-gray-500 uppercase text-[10px] tracking-wide">
                        {headers.map(h => (
                            <th key={h} className="px-3 py-2 text-left font-semibold whitespace-nowrap">{h}</th>
                        ))}
                    </tr>
                </thead>
                <tbody>{rows}</tbody>
            </table>
        </div>
    );
}

// ─── Componente principal ─────────────────────────────────────────────────────
export default function FichaAnimal({ animal }) {
    const pesajes        = animal.pesajes        ?? [];
    const alimentaciones = animal.alimentaciones ?? [];
    const eventossalud   = animal.eventos_salud  ?? [];
    const tratamientos   = animal.tratamientos   ?? [];
    const producciones   = animal.producciones   ?? [];
    const eventosRepro   = animal.eventos_reproductivos ?? [];
    const esHembra       = animal.sexo === "F";

    // ── Cálculos de peso ──
    const pesajesOrden  = [...pesajes].sort((a, b) => a.fecha.localeCompare(b.fecha));
    const pesoInicial   = pesajesOrden.length ? parseFloat(pesajesOrden[0].peso)                         : null;
    const pesoActual    = pesajesOrden.length ? parseFloat(pesajesOrden[pesajesOrden.length - 1].peso)   : null;
    const gananciaPeso  = pesoInicial != null && pesoActual != null
        ? Math.round((pesoActual - pesoInicial) * 100) / 100 : null;
    const chartDataPeso = pesajesOrden.map(p => ({ fecha: p.fecha, peso: parseFloat(p.peso) }));

    // ── Splits de reproductivos ──
    const servicios    = eventosRepro.filter(e => e.tipo_evento === "servicio");
    const diagnosticos = eventosRepro.filter(e => e.tipo_evento === "diagnostico");
    const partos       = eventosRepro.filter(e => e.tipo_evento === "parto");

    return (
        <div className="space-y-5">

            {/* ══ Datos Generales ════════════════════════════════════════════ */}
            <Card icon={PawPrint} title={`${animal.alias ? `${animal.alias} — ` : ""}${animal.arete}`} color="green">
                <div className="grid md:grid-cols-2 gap-6">
                    {/* Columna izq */}
                    <div>
                        <p className="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Identificación</p>
                        <Dato label="Arete"             value={animal.arete} highlight />
                        <Dato label="Alias"             value={animal.alias || "—"} />
                        <Dato label="Especie"           value={animal.especie} />
                        <Dato label="Raza"              value={animal.raza || "—"} />
                        <Dato label="Sexo"              value={animal.sexo === "M" ? "Macho" : "Hembra"} />
                        <Dato label="Fecha Nacimiento"  value={fmtFecha(animal.fecha_nac)} />
                        <Dato label="Edad"              value={calcularEdad(animal.fecha_nac)} />
                        <Dato label="Fecha de Registro" value={fmtFecha(animal.created_at)} />
                    </div>
                    {/* Columna der */}
                    <div>
                        <p className="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Estado actual</p>
                        <Dato label="Lote / Potrero"    value={animal.lote?.nombre || "Sin lote"} />
                        <Dato label="Estado productivo" value={animal.estado_productivo || "—"} />
                        <Dato label="Peso registrado"   value={pesoActual != null ? fmtPeso(pesoActual) : fmtPeso(animal.peso)} highlight />
                        <Dato label="BCS"               value={animal.BCS || "—"} />
                        {animal.madre && <Dato label="Madre"  value={`${animal.madre.arete}${animal.madre.alias ? ` — ${animal.madre.alias}` : ""}`} />}
                        {animal.padre && <Dato label="Padre"  value={`${animal.padre.arete}${animal.padre.alias ? ` — ${animal.padre.alias}` : ""}`} />}
                    </div>
                </div>
            </Card>

            {/* ══ Historial de Peso ══════════════════════════════════════════ */}
            <Card icon={Scale} title="Historial de Peso" color="blue">
                {pesajesOrden.length === 0 ? (
                    <p className="text-sm text-gray-400 text-center py-4">No hay pesajes registrados.</p>
                ) : (<>
                    {/* Mini stat cards */}
                    <div className="grid grid-cols-3 gap-3 mb-5">
                        <div className="rounded-xl border border-gray-100 bg-gray-50 p-3 text-center">
                            <p className="text-[11px] text-gray-500 mb-1">Peso inicial</p>
                            <p className="text-base font-bold text-gray-800">{fmtPeso(pesoInicial)}</p>
                            <p className="text-[10px] text-gray-400 mt-0.5">{pesajesOrden[0]?.fecha}</p>
                        </div>
                        <div className="rounded-xl border border-blue-100 bg-blue-50 p-3 text-center">
                            <p className="text-[11px] text-blue-600 mb-1">Peso actual</p>
                            <p className="text-base font-bold text-blue-700">{fmtPeso(pesoActual)}</p>
                            <p className="text-[10px] text-blue-400 mt-0.5">{pesajesOrden[pesajesOrden.length - 1]?.fecha}</p>
                        </div>
                        <div className={`rounded-xl border p-3 text-center ${
                            gananciaPeso > 0  ? "border-emerald-100 bg-emerald-50" :
                            gananciaPeso < 0  ? "border-red-100    bg-red-50"     :
                            "border-gray-100 bg-gray-50"
                        }`}>
                            <p className="text-[11px] text-gray-500 mb-1">Ganancia total</p>
                            <div className="flex items-center justify-center gap-1">
                                {gananciaPeso > 0  ? <TrendingUp  className="w-3.5 h-3.5 text-emerald-600" /> :
                                 gananciaPeso < 0  ? <TrendingDown className="w-3.5 h-3.5 text-red-500"    /> :
                                 <Minus className="w-3.5 h-3.5 text-gray-400" />}
                                <p className={`text-base font-bold ${
                                    gananciaPeso > 0 ? "text-emerald-700" :
                                    gananciaPeso < 0 ? "text-red-600"     : "text-gray-600"
                                }`}>
                                    {gananciaPeso != null ? `${gananciaPeso >= 0 ? "+" : ""}${gananciaPeso} kg` : "—"}
                                </p>
                            </div>
                            <p className="text-[10px] text-gray-400 mt-0.5">{pesajesOrden.length} pesaje(s)</p>
                        </div>
                    </div>

                    {/* Gráfica */}
                    {chartDataPeso.length > 1 && (
                        <div className="mb-5">
                            <ResponsiveContainer width="100%" height={180}>
                                <LineChart data={chartDataPeso} margin={{ top: 5, right: 10, left: 0, bottom: 5 }}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                                    <XAxis dataKey="fecha" tick={{ fontSize: 10 }}
                                        tickFormatter={f => { const [,m,d] = f.split("-"); return `${d}/${m}`; }} />
                                    <YAxis tick={{ fontSize: 10 }} tickFormatter={v => `${v}kg`} width={52} />
                                    <Tooltip
                                        formatter={v => [`${Number(v).toFixed(2)} kg`, "Peso"]}
                                        labelFormatter={l => `Fecha: ${l}`}
                                    />
                                    <Line type="monotone" dataKey="peso" stroke="#3b82f6" strokeWidth={2}
                                        dot={{ r: 3, fill: "#3b82f6" }} activeDot={{ r: 5 }} />
                                </LineChart>
                            </ResponsiveContainer>
                        </div>
                    )}

                    {/* Tabla completa */}
                    <MiniTabla
                        headers={["Fecha", "Peso", "Variación", "Notas"]}
                        rows={[...pesajesOrden].reverse().map((p, i, arr) => {
                            const sig   = arr[i + 1];
                            const delta = sig ? Math.round((parseFloat(p.peso) - parseFloat(sig.peso)) * 100) / 100 : null;
                            return (
                                <tr key={p.id} className="border-t border-gray-100 hover:bg-blue-50/40">
                                    <td className="px-3 py-2 text-gray-500">{p.fecha}</td>
                                    <td className="px-3 py-2 font-semibold">{fmtPeso(p.peso)}</td>
                                    <td className="px-3 py-2">
                                        {delta != null ? (
                                            <span className={`font-medium text-xs ${delta > 0 ? "text-emerald-600" : delta < 0 ? "text-red-500" : "text-gray-400"}`}>
                                                {delta >= 0 ? "+" : ""}{delta} kg
                                            </span>
                                        ) : "—"}
                                    </td>
                                    <td className="px-3 py-2 text-gray-400">{p.notas || "—"}</td>
                                </tr>
                            );
                        })}
                    />
                </>)}
            </Card>

            {/* ══ Eventos de Salud ═══════════════════════════════════════════ */}
            <Card icon={HeartPulse} title="Eventos de Salud" color="teal">
                {(() => {
                    const pendientes = eventossalud.filter(e => e.estado === "pendiente").length;
                    const vencidos   = eventossalud.filter(e => e.estado === "vencida").length;
                    return (
                        <>
                        {(pendientes > 0 || vencidos > 0) && (
                            <div className="flex gap-2 mb-3 flex-wrap">
                                {pendientes > 0 && (
                                    <div className="flex items-center gap-1.5 bg-amber-50 border border-amber-200 rounded-lg px-3 py-1.5 text-xs text-amber-700">
                                        <AlertTriangle className="w-3.5 h-3.5" />
                                        <strong>{pendientes}</strong> pendiente(s)
                                    </div>
                                )}
                                {vencidos > 0 && (
                                    <div className="flex items-center gap-1.5 bg-red-50 border border-red-200 rounded-lg px-3 py-1.5 text-xs text-red-700">
                                        <AlertTriangle className="w-3.5 h-3.5" />
                                        <strong>{vencidos}</strong> vencido(s)
                                    </div>
                                )}
                            </div>
                        )}
                        <MiniTabla
                            headers={["Tipo", "F. Programada", "F. Aplicación", "Estado", "Diagnóstico", "Responsable"]}
                            rows={eventossalud.map(ev => (
                                <tr key={ev.id} className="border-t border-gray-100 hover:bg-teal-50/40">
                                    <td className="px-3 py-2"><Badge estado={ev.tipo} /></td>
                                    <td className="px-3 py-2 text-gray-500">{ev.fecha_programada}</td>
                                    <td className="px-3 py-2 text-gray-500">{ev.fecha_aplicacion ?? "—"}</td>
                                    <td className="px-3 py-2"><Badge estado={ev.estado} /></td>
                                    <td className="px-3 py-2 text-gray-600 max-w-[180px] truncate">{ev.diagnostico ?? "—"}</td>
                                    <td className="px-3 py-2 text-gray-500">{ev.responsable ?? "—"}</td>
                                </tr>
                            ))}
                            emptyMsg="No hay eventos de salud registrados."
                        />
                        </>
                    );
                })()}
            </Card>

            {/* ══ Vacunaciones ═══════════════════════════════════════════════ */}
            <Card icon={Syringe} title="Vacunaciones" color="amber">
                <MiniTabla
                    headers={["Vacuna", "F. Programada", "F. Aplicación", "Dosis", "Lote Vacuna", "Estado"]}
                    rows={eventossalud.filter(e => e.tipo === "vacunacion").map(ev => (
                        <tr key={ev.id} className="border-t border-gray-100 hover:bg-amber-50/40">
                            <td className="px-3 py-2 font-medium">{ev.vacuna?.nombre ?? "—"}</td>
                            <td className="px-3 py-2 text-gray-500">{ev.fecha_programada}</td>
                            <td className="px-3 py-2 text-gray-500">{ev.fecha_aplicacion ?? "—"}</td>
                            <td className="px-3 py-2">{ev.dosis ?? "—"}</td>
                            <td className="px-3 py-2 text-gray-500">{ev.lote_vacuna ?? "—"}</td>
                            <td className="px-3 py-2"><Badge estado={ev.estado} /></td>
                        </tr>
                    ))}
                    emptyMsg="No hay vacunaciones registradas."
                />
            </Card>

            {/* ══ Tratamientos ═══════════════════════════════════════════════ */}
            <Card icon={Pill} title="Tratamientos" color="purple">
                <MiniTabla
                    headers={["Tratamiento", "F. Inicio", "F. Fin prevista", "Estado", "Responsable", "Notas"]}
                    rows={tratamientos.map(tr => (
                        <tr key={tr.id} className="border-t border-gray-100 hover:bg-purple-50/40">
                            <td className="px-3 py-2 font-medium">{tr.nombre}</td>
                            <td className="px-3 py-2 text-gray-500">{tr.fecha_inicio}</td>
                            <td className="px-3 py-2 text-gray-500">{tr.fecha_fin ?? "—"}</td>
                            <td className="px-3 py-2"><Badge estado={tr.estado} /></td>
                            <td className="px-3 py-2 text-gray-500">{tr.responsable ?? "—"}</td>
                            <td className="px-3 py-2 text-gray-400 max-w-[160px] truncate">{tr.notas ?? "—"}</td>
                        </tr>
                    ))}
                    emptyMsg="No hay tratamientos registrados."
                />
            </Card>

            {/* ══ Alimentación ═══════════════════════════════════════════════ */}
            <Card icon={Utensils} title="Alimentación" color="orange">
                {alimentaciones.length > 0 && (
                    <div className="flex flex-wrap gap-2 mb-3">
                        {(() => {
                            const porRacion = {};
                            alimentaciones.forEach(a => {
                                const n = a.racion?.nombre ?? "Sin ración";
                                porRacion[n] = (porRacion[n] ?? 0) + 1;
                            });
                            const [top] = Object.entries(porRacion).sort((a,b) => b[1]-a[1]);
                            return top ? (
                                <span className="text-xs rounded-full border border-orange-200 bg-orange-50 px-3 py-1 text-orange-700">
                                    Ración más usada: <strong>{top[0]}</strong> ({top[1]} reg.)
                                </span>
                            ) : null;
                        })()}
                        <span className="text-xs rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-gray-600">
                            {alimentaciones.length} registro(s)
                        </span>
                    </div>
                )}
                <MiniTabla
                    headers={["Fecha", "Hora", "Ración", "Cantidad", "Unidad", "MS%", "PB%", "Notas"]}
                    rows={alimentaciones.map(a => (
                        <tr key={a.id} className="border-t border-gray-100 hover:bg-orange-50/40">
                            <td className="px-3 py-2 text-gray-500">{a.fecha}</td>
                            <td className="px-3 py-2 text-gray-400">{a.hora ?? "—"}</td>
                            <td className="px-3 py-2 font-medium">{a.racion?.nombre ?? "—"}</td>
                            <td className="px-3 py-2 font-semibold">{a.cantidad}</td>
                            <td className="px-3 py-2 text-gray-500">{a.unidad}</td>
                            <td className="px-3 py-2 text-gray-500">{a.racion?.MS ?? "—"}</td>
                            <td className="px-3 py-2 text-gray-500">{a.racion?.PB ?? "—"}</td>
                            <td className="px-3 py-2 text-gray-400 max-w-[120px] truncate">{a.notas ?? "—"}</td>
                        </tr>
                    ))}
                    emptyMsg="No hay registros de alimentación."
                />
            </Card>

            {/* ══ Producción ═════════════════════════════════════════════════ */}
            <Card icon={Milk} title="Producción" color="teal">
                {producciones.length > 0 && (
                    <div className="flex flex-wrap gap-2 mb-3">
                        {["leche","lana","carne","canal"].map(tipo => {
                            const regs = producciones.filter(p => p.tipo === tipo);
                            if (!regs.length) return null;
                            const total = regs.reduce((s, p) => s + parseFloat(p.valor ?? 0), 0);
                            const unidad = regs[0]?.unidad ?? "";
                            return (
                                <span key={tipo} className="text-xs rounded-full border border-teal-200 bg-teal-50 px-3 py-1 text-teal-700 capitalize">
                                    {tipo}: <strong>{total.toFixed(2)} {unidad}</strong>
                                </span>
                            );
                        })}
                    </div>
                )}
                <MiniTabla
                    headers={["Fecha", "Tipo", "Valor", "Unidad"]}
                    rows={producciones.map(p => (
                        <tr key={p.id} className="border-t border-gray-100 hover:bg-teal-50/40">
                            <td className="px-3 py-2 text-gray-500">{p.fecha}</td>
                            <td className="px-3 py-2"><Badge estado={p.tipo} /></td>
                            <td className="px-3 py-2 font-semibold">{p.valor != null ? Number(p.valor).toLocaleString() : "—"}</td>
                            <td className="px-3 py-2 text-gray-500">{p.unidad ?? "—"}</td>
                        </tr>
                    ))}
                    emptyMsg="No hay registros de producción."
                />
            </Card>

            {/* ══ Historial Reproductivo (solo hembras) ══════════════════════ */}
            {esHembra && (
                <Card icon={Baby} title="Historial Reproductivo" color="purple">
                    {/* Resumen reproductivo */}
                    {eventosRepro.length > 0 && (
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
                            {[
                                { label: "Servicios",    val: servicios.length,    color: "violet" },
                                { label: "Diagnósticos", val: diagnosticos.length, color: "sky"    },
                                { label: "Partos",       val: partos.length,       color: "emerald"},
                                {
                                    label: "Tasa concepción",
                                    val: servicios.length > 0
                                        ? `${Math.round((diagnosticos.filter(d => d.diagnostico?.resultado === "positivo").length / servicios.length) * 100)}%`
                                        : "—",
                                    color: "purple"
                                },
                            ].map(({ label, val, color }) => (
                                <div key={label} className={`rounded-xl border p-3 text-center bg-${color}-50 border-${color}-100`}>
                                    <p className={`text-xl font-bold text-${color}-700`}>{val}</p>
                                    <p className="text-[11px] text-gray-500 mt-0.5">{label}</p>
                                </div>
                            ))}
                        </div>
                    )}

                    {/* Sub-tabla: Todos los eventos */}
                    {eventosRepro.length > 0 && (
                        <div className="mb-5">
                            <p className="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Todos los eventos</p>
                            <MiniTabla
                                headers={["Tipo", "Fecha", "Costo", "Observaciones"]}
                                rows={eventosRepro.map(ev => (
                                    <tr key={ev.id} className="border-t border-gray-100 hover:bg-rose-50/40">
                                        <td className="px-3 py-2"><Badge estado={ev.tipo_evento} /></td>
                                        <td className="px-3 py-2 text-gray-500">{ev.fecha}</td>
                                        <td className="px-3 py-2">{ev.costo ? `$${ev.costo}` : "—"}</td>
                                        <td className="px-3 py-2 text-gray-400 max-w-[200px] truncate">{ev.observaciones ?? "—"}</td>
                                    </tr>
                                ))}
                            />
                        </div>
                    )}

                    {/* Sub-tabla: Servicios */}
                    {servicios.length > 0 && (
                        <div className="mb-5">
                            <p className="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Servicios</p>
                            <MiniTabla
                                headers={["Fecha", "Tipo", "# Serv.", "Macho / Pajilla", "Técnico"]}
                                rows={servicios.map(ev => {
                                    const srv = ev.servicio;
                                    return (
                                        <tr key={ev.id} className="border-t border-gray-100 hover:bg-pink-50/40">
                                            <td className="px-3 py-2 text-gray-500">{ev.fecha}</td>
                                            <td className="px-3 py-2"><Badge estado={srv?.tipo_servicio} /></td>
                                            <td className="px-3 py-2 text-center">{srv?.numero_servicio ?? "—"}</td>
                                            <td className="px-3 py-2 font-mono text-gray-600">
                                                {srv?.macho?.arete ?? (srv?.pajilla_codigo ? `🧬 ${srv.pajilla_codigo}` : "—")}
                                            </td>
                                            <td className="px-3 py-2 text-gray-500">{srv?.tecnico?.name ?? srv?.tecnico_externo ?? "—"}</td>
                                        </tr>
                                    );
                                })}
                            />
                        </div>
                    )}

                    {/* Sub-tabla: Diagnósticos */}
                    {diagnosticos.length > 0 && (
                        <div className="mb-5">
                            <p className="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Diagnósticos de gestación</p>
                            <MiniTabla
                                headers={["Fecha", "Método", "Resultado", "Días Gest.", "F. Prob. Parto", "Veterinario"]}
                                rows={diagnosticos.map(ev => {
                                    const dx = ev.diagnostico;
                                    return (
                                        <tr key={ev.id} className="border-t border-gray-100 hover:bg-purple-50/40">
                                            <td className="px-3 py-2 text-gray-500">{ev.fecha}</td>
                                            <td className="px-3 py-2 text-gray-500">{dx?.metodo?.replace(/_/g," ") ?? "—"}</td>
                                            <td className="px-3 py-2">{dx?.resultado ? <Badge estado={dx.resultado} /> : "—"}</td>
                                            <td className="px-3 py-2 text-center">{dx?.dias_gestacion_estimados ?? "—"}</td>
                                            <td className="px-3 py-2 text-gray-500">{dx?.fecha_probable_parto ?? "—"}</td>
                                            <td className="px-3 py-2 text-gray-500">{dx?.veterinario?.name ?? dx?.veterinario_externo ?? "—"}</td>
                                        </tr>
                                    );
                                })}
                            />
                        </div>
                    )}

                    {/* Sub-tabla: Partos */}
                    {partos.length > 0 && (
                        <div>
                            <p className="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Partos</p>
                            <MiniTabla
                                headers={["Fecha", "Tipo", "# Crías", "Asistencia", "Complicaciones", "Crías (aretes)"]}
                                rows={partos.map(ev => {
                                    const p = ev.parto;
                                    return (
                                        <tr key={ev.id} className="border-t border-gray-100 hover:bg-emerald-50/40">
                                            <td className="px-3 py-2 text-gray-500">{ev.fecha}</td>
                                            <td className="px-3 py-2"><Badge estado={p?.tipo_parto} /></td>
                                            <td className="px-3 py-2 text-center font-semibold">{p?.numero_crias ?? "—"}</td>
                                            <td className="px-3 py-2"><Badge estado={p?.asistencia_requerida} /></td>
                                            <td className="px-3 py-2"><Badge estado={p?.complicaciones} /></td>
                                            <td className="px-3 py-2 font-mono text-[10px] text-gray-500">
                                                {p?.crias?.map(c => (
                                                    <span key={c.id} className="inline-block mr-1 bg-gray-100 rounded px-1">
                                                        {c.animal?.arete ?? c.arete_temporal ?? "s/a"} ({c.sexo?.[0]?.toUpperCase() ?? "?"})
                                                    </span>
                                                ))}
                                            </td>
                                        </tr>
                                    );
                                })}
                            />
                        </div>
                    )}

                    {eventosRepro.length === 0 && (
                        <p className="text-sm text-gray-400 text-center py-4">No hay eventos reproductivos registrados.</p>
                    )}
                </Card>
            )}
        </div>
    );
}