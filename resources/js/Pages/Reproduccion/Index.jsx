import React, { useMemo, useState } from "react";
import { Head, Link } from "@inertiajs/react";

// 👇 IMPORTA el layout con sidebar (igual que Producciones)
import AppLayout from "@/Layouts/AppLayout";

import { Heart, Activity, Baby, BarChart3, Plus, CalendarDays } from "lucide-react";

// Tabs (cada una en su archivo)
import Celos from "./Celos";
import Gestaciones from "./Gestaciones";
import Partos from "./Partos";
import Estadisticas from "./Estadisticas";

// (opcional) Modal único
import ReproduccionModal from "./ReproduccionModal";

function ReproduccionIndex({
  auth,
  reproducciones,
  animales = [],
  lotes = [],
  stats = {},
}) {
  const [tab, setTab] = useState("celos");
  const [showModal, setShowModal] = useState(false);
  const [defaultTipoEvento, setDefaultTipoEvento] = useState("celo");

  const openModalFor = (tipo) => {
    setDefaultTipoEvento(tipo);
    setShowModal(true);
  };

  const cards = useMemo(
    () => [
      { key: "fertilidad", label: "Tasa de Fertilidad", value: stats?.fertilidad ?? "—" },
      { key: "gestacion", label: "En Gestación", value: stats?.gestacion ?? "—" },
      { key: "nacimientos30d", label: "Nacimientos (30d)", value: stats?.nacimientos30d ?? "—" },
      { key: "proximosCelos", label: "Próximos Celos", value: stats?.proximosCelos ?? "—" },
    ],
    [stats]
  );

  return (
    <>
      <Head title="Módulo de Reproducción" />

      {/* ✅ MISMAS MEDIDAS QUE PRODUCCIONES */}
      <div className="py-8 px-6 max-w-7xl mx-auto space-y-6">
        {/* ENCABEZADO */}
        <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          <div>
            <h1 className="text-2xl font-bold text-gray-800">Módulo de Reproducción</h1>
            <p className="text-gray-600">
              Controla celos, gestaciones, partos y estadísticas reproductivas.
            </p>

            
          </div>

          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={() => openModalFor(tab === "partos" ? "parto" : tab === "gestaciones" ? "diagnostico_gestacion" : "celo")}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-black transition"
            >
              <Plus className="w-4 h-4" />
              Registrar evento
            </button>
          </div>
        </div>

        {/* CARDS (misma idea de dashboard) */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          {cards.map((c) => (
            <div key={c.key} className="bg-white rounded-2xl shadow p-5 border border-gray-100">
              <div className="text-sm text-gray-500">{c.label}</div>
              <div className="mt-2 text-2xl font-bold text-gray-800">{c.value}</div>
            </div>
          ))}
        </div>

        {/* ✅ CONTENIDO PRINCIPAL: Izquierda calendario / derecha tabs */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
          {/* izquierda */}
          <div className="lg:col-span-4 space-y-4">
            <div className="bg-white rounded-2xl shadow p-5 border border-gray-100">
              <div className="flex items-center gap-2 font-semibold text-gray-800">
                <CalendarDays className="w-5 h-5" />
                Calendario Reproductivo
              </div>
              <p className="text-sm text-gray-500 mt-1">Programación de celos y partos</p>

              {/* Aquí pon tu componente de calendario real */}
              <div className="mt-4 h-72 rounded-xl border border-dashed border-gray-300 flex items-center justify-center text-sm text-gray-500">
                Calendario aquí
              </div>
            </div>

            <div className="bg-white rounded-2xl shadow p-5 border border-gray-100">
              <h3 className="font-semibold text-gray-800">Eventos próximos</h3>

              {/* Placeholder (luego lo alimentas con data real) */}
              <div className="mt-3 space-y-3">
                <div className="rounded-xl bg-purple-50 p-3">
                  <div className="text-sm font-semibold">Parto esperado</div>
                  <div className="text-sm text-gray-600">Blanquita #008 - 25 Sep</div>
                </div>
                <div className="rounded-xl bg-orange-50 p-3">
                  <div className="text-sm font-semibold">Celo esperado</div>
                  <div className="text-sm text-gray-600">Bella #001 - 22 Sep</div>
                </div>
              </div>
            </div>
          </div>

          {/* derecha */}
          <div className="lg:col-span-8 space-y-4">
            {/* PESTAÑAS (estilo Producciones) */}
            <div className="flex gap-6 border-b pb-3 text-gray-600">
              <button
                className={`flex items-center gap-2 pb-2 ${
                  tab === "celos"
                    ? "border-b-2 border-blue-600 text-blue-600 font-semibold"
                    : "hover:text-blue-600"
                }`}
                onClick={() => setTab("celos")}
              >
                <Heart size={18} />
                Celos
              </button>

              <button
                className={`flex items-center gap-2 pb-2 ${
                  tab === "gestaciones"
                    ? "border-b-2 border-blue-600 text-blue-600 font-semibold"
                    : "hover:text-blue-600"
                }`}
                onClick={() => setTab("gestaciones")}
              >
                <Activity size={18} />
                Gestaciones
              </button>

              <button
                className={`flex items-center gap-2 pb-2 ${
                  tab === "partos"
                    ? "border-b-2 border-blue-600 text-blue-600 font-semibold"
                    : "hover:text-blue-600"
                }`}
                onClick={() => setTab("partos")}
              >
                <Baby size={18} />
                Partos
              </button>

              <button
                className={`flex items-center gap-2 pb-2 ${
                  tab === "estadisticas"
                    ? "border-b-2 border-blue-600 text-blue-600 font-semibold"
                    : "hover:text-blue-600"
                }`}
                onClick={() => setTab("estadisticas")}
              >
                <BarChart3 size={18} />
                Estadísticas
              </button>
            </div>

            {/* CONTENIDO */}
            {tab === "celos" && (
              <Celos
                animales={animales}
                reproducciones={reproducciones}
                onNuevoCelo={() => openModalFor("celo")}
              />
            )}

            {tab === "gestaciones" && (
              <Gestaciones
                animales={animales}
                reproducciones={reproducciones}
                onNuevoDiagnostico={() => openModalFor("diagnostico_gestacion")}
              />
            )}

            {tab === "partos" && (
              <Partos
                animales={animales}
                reproducciones={reproducciones}
                onNuevoParto={() => openModalFor("parto")}
              />
            )}

            {tab === "estadisticas" && (
              <Estadisticas animales={animales} lotes={lotes} reproducciones={reproducciones} />
            )}
          </div>
        </div>
      </div>

      {/* Modal */}
      <ReproduccionModal
        show={showModal}
        onClose={() => setShowModal(false)}
        animales={animales}
        lotes={lotes}
        defaultTipoEvento={defaultTipoEvento}
      />
    </>
  );
}

// ✅ CONECTA CON SIDEBAR (igual que Producciones)
ReproduccionIndex.layout = (page) => (
  <AppLayout user={page.props.auth.user}>{page}</AppLayout>
);

export default ReproduccionIndex;
