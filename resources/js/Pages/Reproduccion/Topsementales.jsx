import React, { useMemo, useState } from "react";
import { Search, Trophy } from "lucide-react";

const TIPOS_MONTA = ["monta_natural", "monta_controlada"];
const TIPOS_PAJILLA = [
  "inseminacion_artificial",
  "iatf",
  "transferencia_embriones",
  "fiv",
];

function esMacho(animal) {
  return String(animal?.sexo ?? "").toUpperCase() === "M";
}

/**
 * Resuelve a qué macho interno se le debe atribuir un evento de servicio,
 * o null si no aplica (donador externo, o falta información).
 *
 * - Monta natural/controlada: el macho viene directo en servicio.macho_id.
 * - Inseminación/IATF/transferencia/FIV: el macho viene de
 *   servicio.pajilla.animal_id — solo si la pajilla es de un semental
 *   interno (no de un donador externo, que no cuenta aquí porque no es un
 *   animal de tu propio catálogo).
 *
 * IMPORTANTE: para que el caso de pajillas funcione, el controlador que
 * arma "eventos" para esta página debe cargar la relación
 * servicio.pajilla (ej. EventoReproductivo::with(['servicio.pajilla'])).
 * No tengo ese controlador para confirmarlo — si ves que los servicios de
 * IATF/inseminación no están sumando aquí, ese es el primer lugar a
 * revisar.
 */
function machoIdDelServicio(servicio) {
  if (!servicio) return null;

  if (TIPOS_MONTA.includes(servicio.tipo_servicio)) {
    return servicio.macho_id ?? null;
  }

  if (TIPOS_PAJILLA.includes(servicio.tipo_servicio)) {
    return servicio.pajilla?.animal_id ?? null;
  }

  return null;
}

export default function TopSementales({ eventos = [], animales = [] }) {
  const [busqueda, setBusqueda] = useState("");
  const [vista, setVista] = useState("top5"); // "top5" | "todos"

  const sementales = useMemo(() => {
    const machos = animales.filter(esMacho);
    const conteoPorMacho = {};

    eventos.forEach((e) => {
      if (e.tipo_evento !== "servicio") return;

      const machoId = machoIdDelServicio(e.servicio);
      if (!machoId) return;

      conteoPorMacho[machoId] = (conteoPorMacho[machoId] || 0) + 1;
    });

    return machos
      .map((m) => ({
        id: m.id,
        alias: m.alias,
        arete: m.arete,
        servicios: conteoPorMacho[m.id] || 0,
      }))
      .sort(
        (a, b) =>
          b.servicios - a.servicios ||
          (a.alias || "").localeCompare(b.alias || "")
      );
  }, [eventos, animales]);

  const filtrados = useMemo(() => {
    if (!busqueda.trim()) return sementales;
    const q = busqueda.toLowerCase();
    return sementales.filter(
      (s) =>
        s.alias?.toLowerCase().includes(q) ||
        s.arete?.toLowerCase().includes(q)
    );
  }, [sementales, busqueda]);

  const buscando = Boolean(busqueda.trim());
  const visibles = buscando
    ? filtrados
    : vista === "top5"
    ? filtrados.slice(0, 5)
    : filtrados;

  return (
    <div className="bg-white p-5 rounded-xl shadow-sm border">
      <div className="flex items-center gap-2 font-semibold text-sm mb-4">
        <Trophy size={16} />
        Sementales con más servicios
      </div>

      <div className="flex gap-2 mb-3">
        <div className="flex items-center flex-1 bg-gray-50 border border-gray-200 rounded-lg px-2.5 py-1.5">
          <Search className="w-4 h-4 text-gray-400 mr-1.5" />
          <input
            type="text"
            placeholder="Buscar por arete o alias..."
            value={busqueda}
            onChange={(e) => setBusqueda(e.target.value)}
            className="w-full outline-none text-sm text-gray-700 bg-transparent"
          />
        </div>

        <select
          value={vista}
          onChange={(e) => setVista(e.target.value)}
          disabled={buscando}
          className="border border-gray-200 rounded-lg px-2 py-1.5 text-sm text-gray-700 disabled:opacity-50"
        >
          <option value="top5">Top 5</option>
          <option value="todos">Ver todos</option>
        </select>
      </div>

      <div className="space-y-2">
        {visibles.length === 0 ? (
          <p className="text-sm text-gray-400">
            {sementales.length === 0
              ? "Sin sementales registrados."
              : "Sin resultados para tu búsqueda."}
          </p>
        ) : (
          visibles.map((s, i) => (
            <div
              key={s.id}
              className="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
            >
              <div className="flex items-center gap-2">
                {!buscando && vista === "top5" && (
                  <span className="text-xs font-semibold text-gray-400 w-4">
                    {i + 1}
                  </span>
                )}
                <div>
                  <p className="text-sm font-medium">{s.alias || "Sin alias"}</p>
                  <p className="text-xs text-gray-500">Arete: {s.arete || "N/D"}</p>
                </div>
              </div>
              <span className="text-xs bg-blue-100 text-blue-700 font-medium px-2 py-1 rounded-full">
                {s.servicios} {s.servicios === 1 ? "servicio" : "servicios"}
              </span>
            </div>
          ))
        )}
      </div>
    </div>
  );
}