import React, { useMemo, useState } from "react";
import { Head } from "@inertiajs/react";
import AppLayout from "@/Layouts/AppLayout";
import { Activity, Baby, BarChart3, Plus, Heart, ClipboardList, Scale } from "lucide-react";

import Eventos from "./Eventos";
import Gestaciones from "./Gestaciones";
import Partos from "./Partos";
import ControlPartos from "./ControlPartos";
import Destetes from "./Destetes";
import Estadisticas from "./Estadisticas";
import ServicioModal from "./ServicioModal";
import DiagnosticoModal from "./DiagnosticoModal.jsx";
import PartoModal from "./PartoModal";
import TopSementales from "./TopSementales";

// El catálogo real de "sexo" en Animal usa "M"/"H" (StoreAnimalRequest:
// 'sexo' => 'required|in:M,H'). Antes este archivo comparaba contra
// "macho"/"hembra", que nunca coincide con esos valores — por lo que
// `hembras` y `machos` probablemente venían vacíos, y con ellos los
// selects de ServicioModal/DiagnosticoModal/PartoModal. Se tolera además
// "macho"/"hembra" por si algún dato entra con esa otra convención, pero
// lo ideal es homologar todo a M/H en la base de datos.
function esHembra(animal) {
  const sexo = (animal?.sexo || "").toString().toLowerCase();
  return sexo === "h" || sexo === "hembra";
}

function esMacho(animal) {
  const sexo = (animal?.sexo || "").toString().toLowerCase();
  return sexo === "m" || sexo === "macho";
}

function ReproduccionIndex({
  auth,
  eventos = [],
  animales = [],
  lotes = [],
  pajillas = [],
  donadoresExternos = [],
  estadosProductivos = {},
}) {

  const [tab, setTab] = useState("eventos");

  // Control de modales separados por tipo
  const [modalServicio, setModalServicio]       = useState(false);
  const [modalDiagnostico, setModalDiagnostico] = useState(false);
  const [modalParto, setModalParto]             = useState(false);

  const hembras = animales.filter(esHembra);
  const machos  = animales.filter(esMacho);

  // ── Cards ──────────────────────────────────────────────────────────────
  const cards = useMemo(() => {
    const servicios = eventos.filter(e => e.tipo_evento === "servicio").length;

    const gestantes = eventos.filter(e =>
      e.tipo_evento === "diagnostico" &&
      e.diagnostico?.resultado === "positivo"
    ).length;

    const partos = eventos.filter(e => e.tipo_evento === "parto").length;

    const fertilidad = servicios > 0
      ? Math.round((gestantes / servicios) * 100)
      : 0;

    return [
      { label: "Fertilidad",   value: `${fertilidad}%` },
      { label: "En gestación", value: gestantes },
      { label: "Partos",       value: partos },
      { label: "Servicios",    value: servicios },
    ];
  }, [eventos]);

  // ── Próximos eventos ───────────────────────────────────────────────────
  const proximosEventos = useMemo(() => {
    return eventos
      .filter(e => {
        // Partos próximos (gestantes con fecha probable en los próximos 30 días)
        if (e.tipo_evento === "diagnostico" && e.diagnostico?.resultado === "positivo") {
          const fechaProbable = e.diagnostico?.fecha_probable_parto;
          if (!fechaProbable) return false;
          const dias = (new Date(fechaProbable) - new Date()) / 86400000;
          return dias >= 0 && dias <= 30;
        }
        // Servicios sin diagnóstico posterior (pendientes de diagnóstico)
        if (e.tipo_evento === "servicio") {
          const diasDesdeServicio = (new Date() - new Date(e.fecha)) / 86400000;
          return diasDesdeServicio >= 40;
        }
        return false;
      })
      .sort((a, b) => new Date(a.fecha) - new Date(b.fecha))
      .slice(0, 5);
  }, [eventos]);

  const tabs = [
    { key: "eventos",      label: "Eventos",     icon: <Heart size={15} /> },
    { key: "gestaciones",  label: "Gestaciones", icon: <Activity size={15} /> },
    { key: "partos",       label: "Partos",      icon: <Baby size={15} /> },
    { key: "destetes",     label: "Destetes",    icon: <Scale size={15} /> },
    { key: "control-partos", label: "Control de partos", icon: <ClipboardList size={15} /> },
    { key: "estadisticas", label: "Estadísticas",icon: <BarChart3 size={15} /> },
  ];

  return (
    <>
      <Head title="Reproducción" />

      <div className="py-8 px-6 max-w-7xl mx-auto space-y-6">

        {/* HEADER */}
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-2xl font-bold">Reproducción</h1>
            <p className="text-gray-500 text-sm">Gestión del ciclo reproductivo ovino</p>
          </div>
          <div className="flex gap-2">
            <button
              onClick={() => setModalServicio(true)}
              className="bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 text-sm"
            >
              <Plus size={15} /> Servicio
            </button>
            <button
              onClick={() => setModalDiagnostico(true)}
              className="border px-4 py-2 rounded-lg flex items-center gap-2 text-sm hover:bg-gray-50"
            >
              <Activity size={15} /> Diagnóstico
            </button>
            <button
              onClick={() => setModalParto(true)}
              className="border px-4 py-2 rounded-lg flex items-center gap-2 text-sm hover:bg-gray-50"
            >
              <Baby size={15} /> Parto
            </button>
          </div>
        </div>

        {/* CARDS */}
        <div className="grid md:grid-cols-4 gap-4">
          {cards.map((c, i) => (
            <div key={i} className="bg-white p-5 rounded-xl shadow-sm border">
              <p className="text-sm text-gray-500">{c.label}</p>
              <p className="text-2xl font-bold mt-1">{c.value}</p>
            </div>
          ))}
        </div>

        {/* LAYOUT PRINCIPAL */}
        <div className="grid lg:grid-cols-12 gap-6">

          {/* IZQUIERDA */}
          <div className="lg:col-span-4 space-y-4">

            {/* SEMENTALES CON MÁS SERVICIOS (antes: calendario reproductivo) */}
            <TopSementales eventos={eventos} animales={animales} />

            {/* PRÓXIMOS EVENTOS */}
            <div className="bg-white p-5 rounded-xl shadow-sm border">
              <h3 className="font-semibold text-sm mb-3">Próximos eventos</h3>
              <div className="space-y-2">
                {proximosEventos.length === 0 ? (
                  <p className="text-sm text-gray-400">Sin eventos próximos</p>
                ) : (
                  proximosEventos.map((e) => (
                    <div key={e.id} className="p-3 bg-gray-50 rounded-lg">
                      <p className="text-sm font-medium">
                        {e.tipo_evento === "diagnostico"
                          ? "Parto próximo"
                          : "Diagnóstico pendiente"}
                      </p>
                      <p className="text-xs text-gray-500 mt-0.5">
                        {e.hembra?.alias || "Animal"} —{" "}
                        {e.tipo_evento === "diagnostico"
                          ? e.diagnostico?.fecha_probable_parto
                          : e.fecha}
                      </p>
                    </div>
                  ))
                )}
              </div>
            </div>
          </div>

          {/* DERECHA */}
          <div className="lg:col-span-8">

            {/* TABS */}
            <div className="flex gap-1 border-b mb-4">
              {tabs.map(t => (
                <button
                  key={t.key}
                  onClick={() => setTab(t.key)}
                  className={`flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium border-b-2 transition-colors ${
                    tab === t.key
                      ? "border-blue-600 text-black"
                      : "border-transparent text-gray-500 hover:text-gray-700"
                  }`}
                >
                  {t.icon} {t.label}
                </button>
              ))}
            </div>

            {tab === "eventos" && (
              <Eventos
                animales={animales}
                eventos={eventos}
                onNuevo={() => setModalServicio(true)}
              />
            )}
            {tab === "gestaciones" && (
              <Gestaciones
                animales={animales}
                eventos={eventos}
                onNuevoDiagnostico={() => setModalDiagnostico(true)}
              />
            )}
            {tab === "partos" && (
              <Partos
                animales={animales}
                eventos={eventos}
                onNuevoParto={() => setModalParto(true)}
              />
            )}
            {tab === "control-partos" && (
              <ControlPartos eventos={eventos} />
            )}
            {tab === "destetes" && (
              <Destetes
                eventos={eventos}
                animales={animales}
                lotes={lotes}
                estadosProductivos={estadosProductivos}
              />
            )}
            {tab === "estadisticas" && (
              <Estadisticas animales={animales} lotes={lotes} eventos={eventos} />
            )}
          </div>
        </div>
      </div>

      {/* MODALES */}
   <ServicioModal
  show={modalServicio}
  onClose={() => setModalServicio(false)}
  hembras={hembras}
  machos={machos}
  lotes={lotes}
  pajillas={pajillas}
/>
      <DiagnosticoModal
        show={modalDiagnostico}
        onClose={() => setModalDiagnostico(false)}
        hembras={hembras}
        eventos={eventos}
      />
      <PartoModal
        show={modalParto}
        onClose={() => setModalParto(false)}
        hembras={hembras}
        eventos={eventos}
        animales={animales}
        lotes={lotes}
        donadoresExternos={donadoresExternos}

      />
    </>
  );
}

ReproduccionIndex.layout = (page) => (
  <AppLayout user={page.props.auth.user}>{page}</AppLayout>
);

export default ReproduccionIndex;