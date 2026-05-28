// resources/js/Pages/Reportes/FiltrosPanel.jsx
import { Filter, RefreshCw, CalendarDays, ChevronDown,
    BarChart2, Beef, HeartPulse, Syringe, Pill,
    Scale, UtensilsCrossed, Package, Baby,
    Milk, ShoppingCart } from "lucide-react";

function Select({ label, value, onChange, children }) {
    return (
        <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">{label}</label>
            <div className="relative">
                <select
                    value={value}
                    onChange={e => onChange(e.target.value)}
                    className="w-full appearance-none rounded-xl border border-gray-300 bg-white py-2 pl-3 pr-8 text-sm
                               focus:border-blue-400 focus:ring focus:ring-blue-100 text-gray-700"
                >
                    {children}
                </select>
                <ChevronDown className="pointer-events-none absolute right-2 top-2.5 w-4 h-4 text-gray-400" />
            </div>
        </div>
    );
}

function DateInput({ label, value, onChange }) {
    return (
        <div>
            <label className="block text-xs font-medium text-gray-600 mb-1 flex items-center gap-1">
                <CalendarDays size={12} /> {label}
            </label>
            <input
                type="date"
                value={value}
                onChange={e => onChange(e.target.value)}
                className="w-full rounded-xl border border-gray-300 py-2 px-3 text-sm
                           focus:border-blue-400 focus:ring focus:ring-blue-100"
            />
        </div>
    );
}

const MODULOS = [
    { value: "general",       label: "General",       icon: BarChart2       },
    { value: "animales",      label: "Animales",      icon: Beef            },
    { value: "salud",         label: "Salud",         icon: HeartPulse      },
    { value: "vacunacion",    label: "Vacunación",    icon: Syringe         },
    { value: "tratamientos",  label: "Tratamientos",  icon: Pill            },
    { value: "pesajes",       label: "Pesajes",       icon: Scale           },
    { value: "alimentacion",  label: "Alimentación",  icon: UtensilsCrossed },
    { value: "inventario",    label: "Inventario",    icon: Package         },
    { value: "reproduccion",  label: "Reproducción",  icon: Baby            },
    { value: "produccion",    label: "Producción",    icon: Milk            },
    { value: "ventas",        label: "Ventas",        icon: ShoppingCart    },
];

const CON_ANIMAL       = ["general","animales","salud","vacunacion","tratamientos","pesajes","alimentacion","reproduccion","produccion"];
const CON_SALUD        = ["general","salud"];
const CON_ALIMENTACION = ["alimentacion","general"];
const CON_REPRODUCCION = ["reproduccion","general"];
const CON_PRODUCCION   = ["produccion","general"];
const CON_VENTAS       = ["ventas","general"];

// ocultarModulo: true cuando se usa en la pestaña Ficha (el módulo no aplica,
//   se muestran todos los grupos de filtros a la vez)
export default function FiltrosPanel({
    filtros, set, catalogos, cargando,
    onSubmit, onLimpiar,
    labelSubmit = "Generar reporte",
    ocultarModulo = false,
}) {
    const mod = ocultarModulo ? "general" : filtros.modulo;

    // En modo ficha mostramos todos los grupos excepto inventario y ventas
    // (no aplican a un animal individual)
    const mostrar = (grupos) => ocultarModulo
        ? !["inventario","ventas"].some(g => grupos.length === 1 && grupos[0] === g)
        : grupos.includes(mod);

    return (
        <form
            onSubmit={onSubmit}
            className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5"
        >
            {/* ── Selector de módulo (solo en reportes generales) ── */}
            {!ocultarModulo && (
                <div>
                    <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Módulo</p>
                    <div className="flex flex-wrap gap-2">
                        {MODULOS.map(({ value, label, icon: Icon }) => (
                            <button
                                key={value}
                                type="button"
                                onClick={() => set("modulo")(value)}
                                className={`flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition border ${
                                    mod === value
                                        ? "bg-blue-600 text-white border-blue-600 shadow-sm"
                                        : "bg-gray-50 text-gray-600 border-gray-200 hover:border-blue-300 hover:bg-blue-50"
                                }`}
                            >
                                <Icon className="w-4 h-4" /> {label}
                            </button>
                        ))}
                    </div>
                </div>
            )}

            {/* ── Fechas ── */}
            <div className="grid grid-cols-2 gap-4">
                <DateInput label="Fecha inicio" value={filtros.fecha_inicio} onChange={set("fecha_inicio")} />
                <DateInput label="Fecha fin"    value={filtros.fecha_fin}    onChange={set("fecha_fin")} />
            </div>

            {/* ── Filtros de animal ── */}
            {(ocultarModulo || CON_ANIMAL.includes(mod)) && (
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 pt-1 border-t border-gray-100">
                    <Select label="Especie" value={filtros.especie} onChange={set("especie")}>
                        <option value="">Todas</option>
                        {(catalogos.especies ?? []).map(e => <option key={e} value={e}>{e}</option>)}
                    </Select>
                    <Select label="Sexo" value={filtros.sexo} onChange={set("sexo")}>
                        <option value="">Todos</option>
                        <option value="M">Macho</option>
                        <option value="F">Hembra</option>
                    </Select>
                    <Select label="Raza" value={filtros.raza} onChange={set("raza")}>
                        <option value="">Todas</option>
                        {(catalogos.razas ?? []).map(r => <option key={r} value={r}>{r}</option>)}
                    </Select>
                    <Select label="Lote / Potrero" value={filtros.lote_id} onChange={set("lote_id")}>
                        <option value="">Todos</option>
                        {(catalogos.lotes ?? []).map(l => <option key={l.id} value={l.id}>{l.nombre}</option>)}
                    </Select>
                </div>
            )}

            {/* ── Estado productivo ── */}
            {(ocultarModulo || ["general","animales"].includes(mod)) && (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Select label="Estado productivo" value={filtros.estado_productivo} onChange={set("estado_productivo")}>
                        <option value="">Todos</option>
                        {["gestante","lactante","seca","vaca","novilla","becerro","reemplazo"].map(ep => (
                            <option key={ep} value={ep}>{ep}</option>
                        ))}
                    </Select>
                </div>
            )}

            {/* ── Filtros salud ── */}
            {(ocultarModulo || CON_SALUD.includes(mod)) && (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1 border-t border-gray-100">
                    <Select label="Tipo de evento (salud)" value={filtros.tipo_evento} onChange={set("tipo_evento")}>
                        <option value="">Todos los tipos</option>
                        <option value="consulta">Consulta</option>
                        <option value="vacunacion">Vacunación</option>
                        <option value="revision">Revisión</option>
                        <option value="emergencia">Emergencia</option>
                    </Select>
                    <Select label="Estado del evento" value={filtros.estado_salud} onChange={set("estado_salud")}>
                        <option value="">Todos los estados</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="aplicada">Aplicada</option>
                        <option value="vencida">Vencida</option>
                    </Select>
                </div>
            )}

            {/* ── Filtros vacunación ── */}
            {(ocultarModulo || mod === "vacunacion") && (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1 border-t border-gray-100">
                    <Select label="Vacuna" value={filtros.vacuna_id} onChange={set("vacuna_id")}>
                        <option value="">Todas</option>
                        {(catalogos.vacunas ?? []).map(v => <option key={v.id} value={v.id}>{v.nombre}</option>)}
                    </Select>
                    {!ocultarModulo && (
                        <Select label="Estado vacunación" value={filtros.estado_salud} onChange={set("estado_salud")}>
                            <option value="">Todos</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="aplicada">Aplicada</option>
                            <option value="vencida">Vencida</option>
                        </Select>
                    )}
                </div>
            )}

            {/* ── Filtros tratamientos ── */}
            {(ocultarModulo || mod === "tratamientos") && (
                <div className="pt-1 border-t border-gray-100">
                    <Select label="Estado del tratamiento" value={filtros.estado_trat} onChange={set("estado_trat")}>
                        <option value="">Todos</option>
                        <option value="activo">Activo</option>
                        <option value="completado">Completado</option>
                    </Select>
                </div>
            )}

            {/* ── Filtros alimentación ── */}
            {(ocultarModulo || CON_ALIMENTACION.includes(mod)) && (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1 border-t border-gray-100">
                    <Select label="Ración" value={filtros.racion_id} onChange={set("racion_id")}>
                        <option value="">Todas las raciones</option>
                        {(catalogos.raciones ?? []).map(r => <option key={r.id} value={r.id}>{r.nombre}</option>)}
                    </Select>
                    <Select label="Tipo registro" value={filtros.tipo_alimentacion} onChange={set("tipo_alimentacion")}>
                        <option value="">Todos</option>
                        <option value="manual">Manual</option>
                        <option value="automatico">Automático</option>
                    </Select>
                </div>
            )}

            {/* ── Filtros inventario (solo reportes generales) ── */}
            {!ocultarModulo && mod === "inventario" && (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1 border-t border-gray-100">
                    <Select label="Tipo de insumo" value={filtros.tipo_insumo} onChange={set("tipo_insumo")}>
                        <option value="">Todos</option>
                        {(catalogos.tipos_insumo ?? []).map(t => <option key={t} value={t}>{t}</option>)}
                    </Select>
                    <Select label="Estado" value={filtros.activo_insumo} onChange={set("activo_insumo")}>
                        <option value="">Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </Select>
                </div>
            )}

            {/* ── Filtros reproducción ── */}
            {(ocultarModulo || CON_REPRODUCCION.includes(mod)) && (
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1 border-t border-gray-100">
                    <Select label="Tipo de evento repro." value={filtros.tipo_evento_repro} onChange={set("tipo_evento_repro")}>
                        <option value="">Todos</option>
                        <option value="celo">Celo</option>
                        <option value="servicio">Servicio</option>
                        <option value="diagnostico">Diagnóstico</option>
                        <option value="parto">Parto</option>
                        <option value="aborto">Aborto</option>
                        <option value="destete">Destete</option>
                    </Select>
                    <Select label="Tipo de servicio" value={filtros.tipo_servicio} onChange={set("tipo_servicio")}>
                        <option value="">Todos</option>
                        <option value="monta_natural">Monta natural</option>
                        <option value="inseminacion_artificial">Inseminación artificial</option>
                        <option value="iatf">IATF</option>
                    </Select>
                    <Select label="Resultado diagnóstico" value={filtros.resultado_diagnostico} onChange={set("resultado_diagnostico")}>
                        <option value="">Todos</option>
                        <option value="positivo">Positivo</option>
                        <option value="negativo">Negativo</option>
                        <option value="repetir">Repetir</option>
                    </Select>
                </div>
            )}

            {/* ── Filtros producción ── */}
            {(ocultarModulo || CON_PRODUCCION.includes(mod)) && (
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1 border-t border-gray-100">
                    <Select label="Tipo de producción" value={filtros.tipo_produccion} onChange={set("tipo_produccion")}>
                        <option value="">Todos los tipos</option>
                        <option value="leche">Leche</option>
                        <option value="lana">Lana</option>
                        <option value="carne">Carne</option>
                        <option value="canal">Canal</option>
                    </Select>
                </div>
            )}

            {/* ── Filtros ventas (solo reportes generales) ── */}
            {!ocultarModulo && CON_VENTAS.includes(mod) && (
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 pt-1 border-t border-gray-100">
                    <Select label="Tipo de venta" value={filtros.tipo_venta} onChange={set("tipo_venta")}>
                        <option value="">Todos</option>
                        <option value="animal">Animal</option>
                        <option value="lote">Lote</option>
                        <option value="produccion">Producción</option>
                        <option value="subproducto_faena">Subproducto de faena</option>
                    </Select>
                    <Select label="Estado venta" value={filtros.estado_venta} onChange={set("estado_venta")}>
                        <option value="">Todos</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="completada">Completada</option>
                        <option value="cancelada">Cancelada</option>
                    </Select>
                    <Select label="Estado de pago" value={filtros.estado_pago} onChange={set("estado_pago")}>
                        <option value="">Todos</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="parcial">Parcial</option>
                        <option value="completado">Completado</option>
                    </Select>
                    <Select label="Método de pago" value={filtros.metodo_pago} onChange={set("metodo_pago")}>
                        <option value="">Todos</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="cheque">Cheque</option>
                    </Select>
                </div>
            )}

            {/* ── Acciones ── */}
            <div className="flex items-center justify-between pt-2 border-t border-gray-100">
                <button
                    type="button"
                    onClick={onLimpiar}
                    className="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition"
                >
                    <RefreshCw className="w-3.5 h-3.5" /> Limpiar filtros
                </button>
                <button
    type="submit"
    disabled={cargando}
    className="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-60
               text-white font-medium px-6 py-2 rounded-xl transition shadow-sm text-sm"
>
    <RefreshCw className={`w-4 h-4 ${cargando ? "animate-spin" : ""}`} />
    <span>{cargando ? "Generando…" : labelSubmit}</span>
</button>
            </div>
        </form>
    );
}