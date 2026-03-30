import React, { useMemo } from "react";
import { Activity, Baby, AlertTriangle } from "lucide-react";

export default function Gestaciones({ animales = [], eventos = [], onNuevoDiagnostico }) {

  const items = useMemo(() => {
    // Último diagnóstico por hembra
    const diagnosticos = eventos.filter(e => e.tipo_evento === "diagnostico");

    const porHembra = {};
    diagnosticos.forEach(d => {
      if (
        !porHembra[d.hembra_id] ||
        new Date(d.fecha) > new Date(porHembra[d.hembra_id].fecha)
      ) {
        porHembra[d.hembra_id] = d;
      }
    });

    return Object.values(porHembra).map((d) => {
      const animal = animales.find(a => a.id === d.hembra_id);
      const resultado = d.diagnostico?.resultado || "pendiente";
      const fechaProbableParto = d.diagnostico?.fecha_probable_parto || null;

      // Días de gestación actuales
      let diasGestacion = null;
      if (resultado === "positivo" && fechaProbableParto) {
        diasGestacion = 283 - Math.floor(
          (new Date(fechaProbableParto) - new Date()) / 86400000
        );
      }

      return {
        id: d.id,
        hembra_id: d.hembra_id,
        nombre: `${animal?.alias || "Animal"} (#${animal?.arete || animal?.id})`,
        fecha: d.fecha,
        metodo: d.diagnostico?.metodo || null,
        resultado,
        fechaProbableParto,
        diasGestacion,
        veterinario: d.diagnostico?.veterinario || null,
      };
    });
  }, [eventos, animales]);

  const gestantes  = items.filter(i => i.resultado === "positivo");
  const negativos  = items.filter(i => i.resultado === "negativo");
  const pendientes = items.filter(i => i.resultado === "repetir" || i.resultado === "pendiente");

  const badge = (resultado) => {
    const map = {
      positivo:  "bg-purple-100 text-purple-700",
      negativo:  "bg-red-100 text-red-700",
      repetir:   "bg-yellow-100 text-yellow-700",
      pendiente: "bg-yellow-100 text-yellow-700",
    };
    const label = {
      positivo:  "Gestante",
      negativo:  "Negativo",
      repetir:   "Repetir",
      pendiente: "Pendiente",
    };
    return (
      <span className={`px-3 py-1 text-xs rounded-full font-medium ${map[resultado] || "bg-gray-100 text-gray-500"}`}>
        {label[resultado] || resultado}
      </span>
    );
  };

  const icon = (resultado) => {
    if (resultado === "positivo")  return <Baby className="w-5 h-5 text-purple-600" />;
    if (resultado === "negativo")  return <AlertTriangle className="w-5 h-5 text-red-500" />;
    return <Activity className="w-5 h-5 text-yellow-600" />;
  };

  const renderLista = (lista, titulo) => (
    <div>
      <h3 className="font-semibold text-sm text-gray-700 mb-2">{titulo}</h3>
      {lista.length === 0 ? (
        <p className="text-sm text-gray-400 mb-4">Sin registros</p>
      ) : (
        <div className="space-y-2 mb-4">
          {lista.map((it) => (
            <div key={it.id} className="border rounded-xl p-4 flex justify-between items-center">
              <div className="flex gap-3">
                <div className="mt-0.5">{icon(it.resultado)}</div>
                <div>
                  <p className="font-semibold text-sm">{it.nombre}</p>
                  <p className="text-xs text-gray-500 mt-0.5">
                    Diagnóstico: {it.fecha}
                    {it.metodo && ` — ${it.metodo.replace("_", " ")}`}
                  </p>
                  {it.fechaProbableParto && (
                    <p className="text-xs text-gray-400 mt-0.5">
                      Parto estimado: {it.fechaProbableParto}
                      {it.diasGestacion !== null && ` (${it.diasGestacion} días de gestación)`}
                    </p>
                  )}
                  {it.veterinario && (
                    <p className="text-xs text-gray-400">Vet: {it.veterinario}</p>
                  )}
                </div>
              </div>
              <div>{badge(it.resultado)}</div>
            </div>
          ))}
        </div>
      )}
    </div>
  );

  return (
    <div className="bg-white rounded-xl border border-gray-200">
      <div className="p-5 border-b">
        <h2 className="text-lg font-semibold">Gestaciones</h2>
        <p className="text-sm text-gray-500">Diagnósticos y seguimiento de preñez</p>
      </div>
      <div className="p-5 space-y-2">
        {renderLista(gestantes,  "Gestantes confirmadas")}
        {renderLista(pendientes, "Pendientes / repetir")}
        {renderLista(negativos,  "Diagnósticos negativos")}
        <button
          onClick={onNuevoDiagnostico}
          className="w-full bg-black text-white py-3 rounded-lg flex items-center justify-center gap-2 text-sm"
        >
          <Activity size={15} /> Registrar diagnóstico
        </button>
      </div>
    </div>
  );
}