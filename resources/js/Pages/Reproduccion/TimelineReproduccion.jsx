import React from "react";
import { Heart, Activity, Baby, Syringe } from "lucide-react";

export default function TimelineReproduccion({ reproducciones = [] }) {

  const ordenar = [...(reproducciones?.data || [])].sort(
    (a, b) => new Date(a.fecha) - new Date(b.fecha)
  );

  const icono = (tipo) => {
    switch (tipo) {
      case "celo": return <Heart className="text-pink-500" />;
      case "monta": return <Activity className="text-blue-500" />;
      case "inseminacion": return <Syringe className="text-indigo-500" />;
      case "diagnostico_gestacion": return <Activity className="text-purple-500" />;
      case "parto": return <Baby className="text-green-600" />;
      default: return <Activity />;
    }
  };

  const colorLinea = (tipo) => {
    switch (tipo) {
      case "celo": return "bg-pink-500";
      case "inseminacion": return "bg-indigo-500";
      case "diagnostico_gestacion": return "bg-purple-500";
      case "parto": return "bg-green-600";
      default: return "bg-gray-400";
    }
  };

  return (
    <div className="bg-white rounded-xl border p-5">

      <h2 className="font-semibold mb-4">Timeline reproductivo</h2>

      <div className="relative border-l-2 border-gray-200 pl-6 space-y-6">

        {ordenar.map((e) => (
          <div key={e.id} className="relative">

            <div className={`absolute -left-[13px] top-1 w-6 h-6 rounded-full flex items-center justify-center text-white ${colorLinea(e.tipo_evento)}`}>
              {icono(e.tipo_evento)}
            </div>

            <div>
              <div className="font-semibold capitalize">
                {e.tipo_evento.replace("_", " ")}
              </div>

              <div className="text-sm text-gray-600">
                Fecha: {e.fecha}
              </div>

              {e.estado && (
                <div className="text-xs text-gray-400">
                  Estado: {e.estado}
                </div>
              )}

              {e.diagnostico && (
                <div className="text-xs text-gray-400">
                  Diagnóstico: {e.diagnostico}
                </div>
              )}

              {e.numero_crias && (
                <div className="text-xs text-gray-400">
                  Crías: {e.numero_crias}
                </div>
              )}
            </div>

          </div>
        ))}

      </div>
    </div>
  );
}