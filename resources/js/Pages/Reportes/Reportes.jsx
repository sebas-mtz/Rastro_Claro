// resources/js/Pages/Reportes/Reportes.jsx
import { Head, router } from "@inertiajs/react";
import { useState } from "react";
import AppLayout from "@/Layouts/AppLayout";
import {
    FileText, FileDown, BarChart2, PawPrint,
    Beef, HeartPulse, Syringe, Pill,
    Scale, UtensilsCrossed, Package, Baby,
    AlertTriangle, CheckCircle2, Clock,
    Milk, ShoppingCart, DollarSign, Search,
} from "lucide-react";

import FiltrosPanel from "./FiltrosPanel.jsx";
import FichaAnimal  from "./FichaAnimal.jsx";
import {
    StatCard, SectionHeader, Badge,
    TablaAnimales, TablaSalud, TablaVacunacion,
    TablaTratamientos, TablaPesajes,
    TablaAlimentacion, TablaInventario,
    TablaEventosReproductivos, TablaServicios,
    TablaDiagnosticos, TablaPartos,
    TablaProduccion, TablaVentas,
} from "./Tablas";

// ─── Estado inicial de filtros ────────────────────────────────────────────────
const FILTROS_VACIOS = {
    fecha_inicio: "", fecha_fin: "", modulo: "general",
    especie: "", raza: "", sexo: "", lote_id: "", estado_productivo: "",
    tipo_evento: "", estado_salud: "", estado_trat: "", vacuna_id: "",
    racion_id: "", tipo_alimentacion: "", tipo_insumo: "", activo_insumo: "",
    tipo_evento_repro: "", tipo_servicio: "", resultado_diagnostico: "",
    tipo_produccion: "",
    tipo_venta: "", estado_venta: "", estado_pago: "", metodo_pago: "",
};

// ─── Página ───────────────────────────────────────────────────────────────────
function Reportes({ catalogos = {}, datos = null, filtros: init = {}, ficha = null }) {

    const [tab, setTab]           = useState(ficha ? "animal" : "general");
    const [filtros, setFiltros]   = useState({ ...FILTROS_VACIOS, ...init });
    const [cargando, setCargando] = useState(false);

    // Estado selector de animal
    const [busqueda, setBusqueda]     = useState("");
    const [animalId, setAnimalId]     = useState(ficha?.id ?? "");
    const [cargandoFicha, setCargandoFicha] = useState(false);

    const set    = (key) => (val) => setFiltros(prev => ({ ...prev, [key]: val }));
    const params = Object.fromEntries(Object.entries(filtros).filter(([, v]) => v !== ""));

    // Animales filtrados por búsqueda de texto
    const animalesCatalogo  = catalogos.animales_lista ?? [];
    const animalesFiltrados = busqueda.trim()
        ? animalesCatalogo.filter(a => {
            const q = busqueda.toLowerCase();
            return a.arete?.toLowerCase().includes(q) || a.alias?.toLowerCase().includes(q);
          })
        : animalesCatalogo;

    // ── Handlers generales ──
    function handleSubmit(e) {
        e.preventDefault();
        setCargando(true);
        router.get(route("reportes.index"), { ...params, tab: "general" }, {
            preserveState: true, preserveScroll: true,
            onFinish: () => setCargando(false),
        });
    }

    function handleLimpiar() {
        setFiltros(FILTROS_VACIOS);
        router.get(route("reportes.index"), { tab: "general" }, { preserveState: false });
    }

    function exportar(tipo) {
        const qs = new URLSearchParams(params).toString();
        window.location.href = `${route("reportes." + tipo)}?${qs}`;
    }

    // ── Handlers ficha ──
    function cargarFicha(e) {
        e.preventDefault();
        if (!animalId) return;
        setCargandoFicha(true);
        router.get(route("reportes.index"), { ...params, tab: "animal", animal_id: animalId }, {
            preserveState: true, preserveScroll: true,
            onFinish: () => setCargandoFicha(false),
        });
    }

    function exportarFicha() {
        const qs = new URLSearchParams({ ...params, animal_id: animalId }).toString();
        window.location.href = `/reportes/ficha/pdf?${qs}`;
        }

    const mod     = filtros.modulo;
    const resumen = datos?.resumen;
    const mostrar = (sec) => datos && (mod === "general" || mod === sec);

    return (
        <>
            <Head title="Reportes" />
            <div className="py-8 px-4 md:px-6 max-w-7xl mx-auto space-y-6">

                {/* ══ Título + Pestañas ══════════════════════════════════════ */}
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2 mb-4">
                        <BarChart2 className="w-6 h-6 text-blue-600" /> Reportes
                    </h1>

                    <div className="flex rounded-2xl bg-gray-100 p-1.5 gap-1.5 shadow-inner">
                        <button
                            onClick={() => setTab("general")}
                            className={`flex-1 flex items-center justify-center gap-2.5 py-3.5 px-6 rounded-xl
                                        text-sm font-semibold transition-all duration-200 ${
                                tab === "general"
                                    ? "bg-white shadow-md text-blue-700 ring-1 ring-blue-100"
                                    : "text-gray-500 hover:text-gray-700 hover:bg-white/60"
                            }`}
                        >
                            <BarChart2 className="w-4 h-4" />
                            Reportes Generales
                            <span className={`hidden md:inline text-[10px] font-medium rounded-full px-2 py-0.5 ${
                                tab === "general" ? "bg-blue-100 text-blue-600" : "bg-gray-200 text-gray-400"
                            }`}>Múltiples animales</span>
                        </button>

                        <button
                            onClick={() => setTab("animal")}
                            className={`flex-1 flex items-center justify-center gap-2.5 py-3.5 px-6 rounded-xl
                                        text-sm font-semibold transition-all duration-200 ${
                                tab === "animal"
                                    ? "bg-white shadow-md text-green-700 ring-1 ring-green-100"
                                    : "text-gray-500 hover:text-gray-700 hover:bg-white/60"
                            }`}
                        >
                            <PawPrint className="w-4 h-4" />
                            Ficha de Animal
                            <span className={`hidden md:inline text-[10px] font-medium rounded-full px-2 py-0.5 ${
                                tab === "animal" ? "bg-green-100 text-green-600" : "bg-gray-200 text-gray-400"
                            }`}>Historia completa</span>
                        </button>
                    </div>
                </div>

                {/* ══ TAB: REPORTES GENERALES ════════════════════════════════ */}
                {tab === "general" && (<>
                    <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <p className="text-sm text-gray-500">
                            Filtra y exporta información de animales, salud, producción, ventas y más.
                        </p>
                        {datos && (
                            <div className="flex gap-2 flex-wrap">
                                <button onClick={() => exportar("pdf")}
                                    className="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded-xl transition shadow-sm">
                                    <FileText className="w-4 h-4" /> Exportar PDF
                                </button>
                                <button onClick={() => exportar("xml")}
                                    className="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium px-4 py-2 rounded-xl transition shadow-sm">
                                    <FileDown className="w-4 h-4" /> Exportar XML
                                </button>
                            </div>
                        )}
                    </div>

                    <FiltrosPanel
                        filtros={filtros} set={set} catalogos={catalogos}
                        cargando={cargando} onSubmit={handleSubmit} onLimpiar={handleLimpiar}
                    />

                    {resumen && (
                        <div>
                            <p className="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Resumen</p>
                            <div className="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-5 gap-3">
                                <StatCard icon={Beef}            label="Animales"             value={resumen.total_animales}           color="blue"   sub={`${resumen.animales_machos}M / ${resumen.animales_hembras}H`} />
                                <StatCard icon={HeartPulse}      label="Eventos de salud"     value={resumen.total_eventos_salud}      color="teal"   />
                                <StatCard icon={Clock}           label="Pendientes"            value={resumen.eventos_pendientes}       color="amber"  />
                                <StatCard icon={AlertTriangle}   label="Vencidos"              value={resumen.eventos_vencidos}         color="red"    />
                                <StatCard icon={Syringe}         label="Vacunaciones"          value={resumen.total_vacunaciones}       color="purple" sub={`${resumen.vacunaciones_aplicadas} aplicadas`} />
                                <StatCard icon={Pill}            label="Tratam. activos"       value={resumen.tratamientos_activos}     color="blue"   sub={`de ${resumen.tratamientos_total} totales`} />
                                <StatCard icon={Scale}           label="Pesajes"               value={resumen.total_pesajes}            color="green"  />
                                <StatCard icon={UtensilsCrossed} label="Registros alim."       value={resumen.total_alimentaciones}     color="orange" />
                                <StatCard icon={Package}         label="Insumos activos"       value={resumen.insumos_activos}          color="teal"   sub={`de ${resumen.insumos_total} totales`} />
                                <StatCard icon={CheckCircle2}    label="Lotes activos"         value={resumen.lotes_activos}            color="green"  />
                                <StatCard icon={Baby}            label="Partos"                value={resumen.total_partos}             color="purple" />
                                <StatCard icon={Baby}            label="Serv. por concepción"  value={resumen.servicios_por_concepcion} color="orange" sub="promedio" />
                                <StatCard icon={Milk}            label="Reg. producción"       value={resumen.total_produccion}         color="teal"   />
                                <StatCard icon={ShoppingCart}    label="Ventas"                value={resumen.total_ventas}             color="green"  />
                                <StatCard icon={DollarSign}      label="Ingresos"              value={resumen.ingresos_ventas != null ? `$${Number(resumen.ingresos_ventas).toLocaleString()}` : "—"} color="blue" sub="ventas completadas" />
                            </div>
                        </div>
                    )}

                    {datos && (
                        <div className="space-y-5">
                            {mostrar("animales")     && datos.animales     && <Section icon={Beef}            title="Animales"                  count={datos.animales.total}     color="blue">   <TablaAnimales             registros={datos.animales.registros} /></Section>}
                            {mostrar("salud")        && datos.salud        && <Section icon={HeartPulse}      title="Eventos de Salud"          count={datos.salud.total}        color="teal">   <TablaSalud                registros={datos.salud.registros} /></Section>}
                            {mostrar("vacunacion")   && datos.vacunacion   && (
                                <Section icon={Syringe} title="Vacunaciones" count={datos.vacunacion.total} color="amber">
                                    {datos.vacunacion.refuerzo_vencido > 0 && (
                                        <div className="mb-3 flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2 text-xs text-amber-700">
                                            <AlertTriangle className="w-4 h-4 shrink-0" />
                                            <strong>{datos.vacunacion.refuerzo_vencido}</strong>&nbsp;animales con refuerzo vencido.
                                        </div>
                                    )}
                                    <TablaVacunacion registros={datos.vacunacion.registros} />
                                </Section>
                            )}
                            {mostrar("tratamientos") && datos.tratamientos && <Section icon={Pill}            title="Tratamientos"              count={datos.tratamientos.total} color="purple"> <TablaTratamientos         registros={datos.tratamientos.registros} /></Section>}
                            {mostrar("pesajes")      && datos.pesajes      && <Section icon={Scale}           title="Pesajes"                   count={datos.pesajes.total}      color="green">  <TablaPesajes              registros={datos.pesajes.registros} /></Section>}
                            {mostrar("alimentacion") && datos.alimentacion && <Section icon={UtensilsCrossed} title="Alimentación"              count={datos.alimentacion.total} color="orange"> <TablaAlimentacion         registros={datos.alimentacion.registros} /></Section>}
                            {mostrar("inventario")   && datos.inventario   && <Section icon={Package}         title="Inventario de Insumos"     count={datos.inventario.total}   color="blue">   <TablaInventario           registros={datos.inventario.registros} /></Section>}
                            {mostrar("reproduccion") && datos.reproduccion && <Section icon={Baby}            title="Eventos Reproductivos"     count={datos.reproduccion.total} color="purple"> <TablaEventosReproductivos registros={datos.reproduccion.registros} /></Section>}
                            {mostrar("reproduccion") && datos.servicios    && <Section icon={Baby}            title="Servicios"                 count={datos.servicios.total}    color="orange"> <TablaServicios            registros={datos.servicios.registros} /></Section>}
                            {mostrar("reproduccion") && datos.diagnosticos && <Section icon={Baby}            title="Diagnósticos de Gestación"  count={datos.diagnosticos.total} color="teal">  <TablaDiagnosticos         registros={datos.diagnosticos.registros} /></Section>}
                            {mostrar("reproduccion") && datos.partos       && <Section icon={Baby}            title="Partos"                    count={datos.partos.total}       color="green">  <TablaPartos               registros={datos.partos.registros} /></Section>}
                            {mostrar("produccion")   && datos.produccion   && <Section icon={Milk}            title="Producción"                count={datos.produccion.total}   color="teal">   <TablaProduccion           registros={datos.produccion.registros} /></Section>}
                            {mostrar("ventas")       && datos.ventas       && (
                                <Section icon={ShoppingCart} title="Ventas" count={datos.ventas.total} color="green">
                                    {datos.ventas.pendientes > 0 && (
                                        <div className="mb-3 flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2 text-xs text-amber-700">
                                            <AlertTriangle className="w-4 h-4 shrink-0" />
                                            <strong>{datos.ventas.pendientes}</strong>&nbsp;venta(s) con estado pendiente.
                                        </div>
                                    )}
                                    <TablaVentas registros={datos.ventas.registros} />
                                </Section>
                            )}
                        </div>
                    )}

                    {!datos && (
                        <div className="bg-white rounded-2xl shadow-sm border border-dashed border-gray-200 p-14 text-center">
                            <BarChart2 className="w-10 h-10 text-gray-300 mx-auto mb-3" />
                            <p className="text-gray-500 font-medium">Selecciona los filtros y genera el reporte</p>
                            <p className="text-sm text-gray-400 mt-1">Puedes exportarlo en PDF o XML una vez generado.</p>
                        </div>
                    )}
                </>)}

                {/* ══ TAB: FICHA DE ANIMAL ═══════════════════════════════════ */}
                {tab === "animal" && (<>

                    {/* ── Selector de animal ── */}
                    <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-3">
                        <p className="text-xs font-semibold text-gray-400 uppercase tracking-wide">Animal</p>

                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                            <input
                                type="text"
                                placeholder="Buscar por arete o alias…"
                                value={busqueda}
                                onChange={e => setBusqueda(e.target.value)}
                                className="w-full rounded-xl border border-gray-300 py-2.5 pl-9 pr-4 text-sm
                                           focus:border-green-400 focus:ring focus:ring-green-100"
                            />
                        </div>

                        <select
                            value={animalId}
                            onChange={e => setAnimalId(e.target.value)}
                            className="w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-3 pr-8 text-sm
                                       focus:border-green-400 focus:ring focus:ring-green-100 text-gray-700"
                        >
                            <option value="">— Elige un animal —</option>
                            {animalesFiltrados.map(a => (
                                <option key={a.id} value={a.id}>
                                    {a.arete}{a.alias ? ` — ${a.alias}` : ""}{a.especie ? ` (${a.especie})` : ""}
                                </option>
                            ))}
                        </select>

                        {animalesFiltrados.length === 0 && busqueda && (
                            <p className="text-xs text-gray-400 text-center">Sin resultados para "{busqueda}".</p>
                        )}
                    </div>

                    {/* ── Panel de filtros — mismo que generales ── */}
                    <div className="relative">
                        <FiltrosPanel
                            filtros={filtros} set={set} catalogos={catalogos}
                            cargando={cargandoFicha}
                            onSubmit={cargarFicha}
                            onLimpiar={() => setFiltros(FILTROS_VACIOS)}
                            labelSubmit="Generar ficha"
                            ocultarModulo
                        />
                    </div>

                    {/* ── Exportar ── */}
                    {ficha && (
                        <div className="flex justify-end">
                            <button onClick={exportarFicha}
                                className="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white
                                           font-medium px-5 py-2.5 rounded-xl transition shadow-sm text-sm">
                                <FileText className="w-4 h-4" /> Exportar PDF
                            </button>
                        </div>
                    )}

                    {/* ── Ficha ── */}
                    {ficha
                        ? <FichaAnimal animal={ficha} />
                        : (
                            <div className="bg-white rounded-2xl shadow-sm border border-dashed border-gray-200 p-14 text-center">
                                <PawPrint className="w-10 h-10 text-gray-300 mx-auto mb-3" />
                                <p className="text-gray-500 font-medium">Selecciona un animal y aplica los filtros</p>
                                <p className="text-sm text-gray-400 mt-1">
                                    Puedes acotar por fechas, tipo de evento, vacuna, ración y más antes de generar la ficha.
                                </p>
                            </div>
                        )
                    }
                </>)}

            </div>
        </>
    );
}

function Section({ icon, title, count, color, children }) {
    return (
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <SectionHeader icon={icon} title={title} count={count} color={color} />
            {children}
        </div>
    );
}

Reportes.layout = (page) => <AppLayout>{page}</AppLayout>;
export default Reportes;