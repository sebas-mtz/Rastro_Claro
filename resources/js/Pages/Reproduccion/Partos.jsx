import React, { useMemo } from "react";
import { Baby, AlertTriangle, Clock } from "lucide-react";

export default function Partos({ animales = [], eventos = [], onNuevoParto }) {

  // Partos registrados
  const recientes = useMemo(() => {
    return eventos
      .filter(e => e.tipo_evento === "parto")
      .sort((a, b) => new Date(b.fecha) - new Date(a.fecha))
      .map((p) => {
        const animal = animales.find(a => a.id === p.hembra_id);
        return {
          id: p.id,
          nombre: `${animal?.alias || "Animal"} (#${animal?.arete || animal?.id})`,
          fecha: p.fecha,
          tipoParto: p.parto?.tipo_parto || "normal",
          numeroCrias: p.parto?.numero_crias || 0,
          complicaciones: p.parto?.complicaciones || false,
          crias: p.parto?.crias || [],
        };
      });
  }, [eventos, animales]);

  // Próximos partos desde diagnósticos positivos sin parto posterior
  const proximos = useMemo(() => {
    return eventos
      .filter(e => {
        if (e.tipo_evento !== "diagnostico") return false;
        if (e.diagnostico?.resultado !== "positivo") return false;
        // Verificar que no haya parto posterior
        const tienePartoPosteriort = eventos.some(p =>
          p.tipo_evento === "parto" &&
          p.hembra_id === e.hembra_id &&
          new Date(p.fecha) > new Date(e.fecha)
        );
        return !tienePartoPosteriort;
      })
      .map((g) => {
        const animal = animales.find(a => a.id === g.hembra_id);
        const fechaProbable = g.diagnostico?.fecha_probable_parto;
        const diasFaltantes = fechaProbable
          ? Math.floor((new Date(fechaProbable) - new Date()) / 86400000)
          : null;

        return {
          id: g.id,
          nombre: `${animal?.alias || "Animal"} (#${animal?.arete || animal?.id})`,
          fechaProbable,
          diasFaltantes,
          urgente: diasFaltantes !== null && diasFaltantes <= 21,
        };
      })
      .sort((a, b) => new Date(a.fechaProbable) - new Date(b.fechaProbable));
  }, [eventos, animales]);

  const complicados = recientes.filter(p => p.complicaciones);

  const badgeParto = (tipo) => {
    const map = {
      normal:   "bg-green-100 text-green-700",
      distocico:"bg-orange-100 text-orange-700",
      cesarea:  "bg-red-100 text-red-700",
    };
    return (
      <span className={`px-2 py-0.5 text-xs rounded-full font-medium ${map[tipo] || "bg-gray-100 text-gray-500"}`}>
        {tipo === "distocico" ? "Distócico" : tipo === "cesarea" ? "Cesárea" : "Normal"}
      </span>
    );
  };

  return (
    <div className="bg-white rounded-xl border border-gray-200">
      <div className="p-5 border-b">
        <h2 className="text-lg font-semibold">Partos</h2>
        <p className="text-sm text-gray-500">Registro y seguimiento de nacimientos</p>
      </div>

      <div className="p-5 space-y-6">

        {/* Próximos partos */}
        <div>
          <h3 className="font-semibold text-sm text-gray-700 mb-2">
            Próximos partos ({proximos.length})
          </h3>
          {proximos.length === 0 ? (
            <p className="text-sm text-gray-400">Sin partos próximos</p>
          ) : (
            <div className="space-y-2">
              {proximos.map((p) => (
                <div key={p.id} className={`border rounded-xl p-4 flex justify-between items-center ${p.urgente ? "border-orange-200 bg-orange-50" : ""}`}>
                  <div className="flex gap-3">
                    <Clock className={`w-5 h-5 mt-0.5 ${p.urgente ? "text-orange-500" : "text-gray-400"}`} />
                    <div>
                      <p className="font-semibold text-sm">{p.nombre}</p>
                      <p className="text-xs text-gray-500 mt-0.5">
                        Fecha probable: {p.fechaProbable}
                        {p.diasFaltantes !== null && (
                          <span className={p.urgente ? "text-orange-600 font-medium" : ""}>
                            {" "}— {p.diasFaltantes > 0 ? `en ${p.diasFaltantes} días` : "hoy o pasado"}
                          </span>
                        )}
                      </p>
                    </div>
                  </div>
                  {p.urgente && (
                    <span className="px-2 py-0.5 text-xs bg-orange-100 text-orange-700 rounded-full font-medium">
                      Próximo
                    </span>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Partos recientes */}
        <div>
          <h3 className="font-semibold text-sm text-gray-700 mb-2">
            Partos registrados ({recientes.length})
          </h3>
          {recientes.length === 0 ? (
            <p className="text-sm text-gray-400">Sin partos registrados</p>
          ) : (
            <div className="space-y-2">
              {recientes.map((p) => (
                <div key={p.id} className="border rounded-xl p-4">
                  <div className="flex justify-between items-start">
                    <div className="flex gap-3">
                      <Baby className="w-5 h-5 text-green-600 mt-0.5" />
                      <div>
                        <p className="font-semibold text-sm">{p.nombre}</p>
                        <p className="text-xs text-gray-500 mt-0.5">Fecha: {p.fecha}</p>
                        <p className="text-xs text-gray-500">
                          {p.numeroCrias} {p.numeroCrias === 1 ? "cría" : "crías"}
                        </p>
                        {p.crias.length > 0 && (
                          <div className="flex gap-1 mt-1 flex-wrap">
                            {p.crias.map((c, i) => (
                              <span
                                key={i}
                                className={`text-xs px-2 py-0.5 rounded-full ${
                                  c.condicion === "vivo"
                                    ? "bg-green-100 text-green-700"
                                    : "bg-red-100 text-red-700"
                                }`}
                              >
                                {c.sexo === "macho" ? "♂" : "♀"} {c.condicion === "vivo" ? "Vivo" : "Muerto"}
                                {c.peso_nacimiento ? ` ${c.peso_nacimiento}kg` : ""}
                              </span>
                            ))}
                          </div>
                        )}
                      </div>
                    </div>
                    <div className="flex flex-col items-end gap-1">
                      {badgeParto(p.tipoParto)}
                      {p.complicaciones && (
                        <span className="flex items-center gap-1 text-xs text-red-600">
                          <AlertTriangle size={11} /> Complicaciones
                        </span>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        <button
          onClick={onNuevoParto}
          className="w-full bg-black text-white py-3 rounded-lg flex items-center justify-center gap-2 text-sm"
        >
          <Baby size={15} /> Registrar parto
        </button>
      </div>
    </div>
  );
}