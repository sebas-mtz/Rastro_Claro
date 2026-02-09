import React from "react";
import { Heart, AlertTriangle, Clock } from "lucide-react";

export default function Celos({ animales = [], reproducciones, onNuevoCelo }) {
  // Aquí después conectas tu cálculo real (último celo, próximo esperado, atrasado, etc.)
  // Por ahora es estructura (UI) para que quede como tu diseño.
  const items = animales
    .filter((a) => a.sexo === "F")
    .slice(0, 10)
    .map((a, idx) => ({
      id: a.id,
      nombre: `${a.alias} (${a.arete ? "#" + a.arete : "#" + a.id})`,
      especie: a.especie,
      ciclo: 21,
      ultimo: "31/08/2024",
      proximo: idx % 3 === 0 ? "21/09/2024" : "30/09/2024",
      estado: idx % 3 === 0 ? "proximo" : idx % 3 === 1 ? "atrasado" : "normal",
    }));

  const badge = (estado) => {
    if (estado === "proximo") return <span className="px-3 py-1 rounded-full text-xs bg-orange-100 text-orange-700">Próximo</span>;
    if (estado === "atrasado") return <span className="px-3 py-1 rounded-full text-xs bg-red-100 text-red-700">Atrasado</span>;
    return <span className="px-3 py-1 rounded-full text-xs bg-green-100 text-green-700">Normal</span>;
  };

  const icon = (estado) => {
    if (estado === "proximo") return <Clock className="w-5 h-5 text-orange-500" />;
    if (estado === "atrasado") return <AlertTriangle className="w-5 h-5 text-red-500" />;
    return <Heart className="w-5 h-5 text-green-600" />;
  };

  return (
    <div className="bg-white rounded-xl border border-gray-200">
      <div className="p-5 border-b">
        <h2 className="text-lg font-semibold text-gray-900">Control de Ciclos de Celo</h2>
        <p className="text-sm text-gray-500">Seguimiento de períodos fértiles</p>
      </div>

      <div className="p-5 space-y-4">
        {items.length === 0 ? (
          <div className="text-sm text-gray-500">No hay hembras registradas.</div>
        ) : (
          items.map((it) => (
            <div key={it.id} className="border border-gray-200 rounded-xl p-4 flex items-center justify-between gap-4">
              <div className="flex items-start gap-3">
                <div className="mt-1">{icon(it.estado)}</div>

                <div>
                  <div className="font-semibold text-gray-900">{it.nombre}</div>
                  <div className="text-sm text-gray-600">
                    {it.especie} - Ciclo: {it.ciclo} días
                  </div>
                  <div className="text-sm text-gray-600">Último celo: {it.ultimo}</div>
                  <div className="text-sm text-gray-600">Próximo esperado: {it.proximo}</div>
                </div>
              </div>

              <div className="flex items-center gap-3">
                {badge(it.estado)}
                <button
                  type="button"
                  onClick={onNuevoCelo}
                  className="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 font-medium"
                >
                  Registrar Celo
                </button>
              </div>
            </div>
          ))
        )}

        {/* Botón fijo abajo como tu diseño */}
        <button
          type="button"
          onClick={onNuevoCelo}
          className="w-full mt-2 inline-flex items-center justify-center gap-2 px-4 py-3 rounded-lg bg-gray-900 text-white hover:bg-black"
        >
          <Heart className="w-4 h-4" />
          Nuevo Registro de Celo
        </button>
      </div>
    </div>
  );
}
