import React, { useMemo, useState } from "react";

export default function CalendarioReproductivo({ reproducciones = [] }) {

  const [selectedDay, setSelectedDay] = useState(null);

  // 🔥 Agrupar eventos por fecha
  const eventosPorFecha = useMemo(() => {
    const map = {};

    (reproducciones?.data || []).forEach((r) => {
      if (!map[r.fecha]) map[r.fecha] = [];
      map[r.fecha].push(r);
    });

    return map;
  }, [reproducciones]);

  const hoy = new Date();
  const year = hoy.getFullYear();
  const month = hoy.getMonth();

  const diasEnMes = new Date(year, month + 1, 0).getDate();

  const dias = Array.from({ length: diasEnMes }, (_, i) => {
    const fecha = new Date(year, month, i + 1)
      .toISOString()
      .split("T")[0];

    return {
      dia: i + 1,
      fecha,
      eventos: eventosPorFecha[fecha] || [],
    };
  });

  // 🎨 Colores por tipo
  const colorEvento = (tipo) => {
    switch (tipo) {
      case "celo": return "bg-pink-500";
      case "monta": return "bg-blue-500";
      case "inseminacion": return "bg-indigo-500";
      case "diagnostico_gestacion": return "bg-purple-500";
      case "parto": return "bg-green-600";
      default: return "bg-gray-400";
    }
  };

  return (
    <div>

      {/* CALENDARIO */}
      <div className="grid grid-cols-7 gap-2 text-xs">
        {dias.map((d) => (
          <div
            key={d.fecha}
            onClick={() => setSelectedDay(d)}
            className="border rounded-lg p-2 min-h-[70px] bg-white cursor-pointer hover:bg-gray-50"
          >
            <div className="font-semibold text-gray-700">{d.dia}</div>

            <div className="mt-1 space-y-1">
              {d.eventos.slice(0, 3).map((e) => (
                <div
                  key={e.id}
                  className={`text-white px-1 rounded text-[10px] ${colorEvento(e.tipo_evento)}`}
                >
                  {e.tipo_evento}
                </div>
              ))}
            </div>
          </div>
        ))}
      </div>

      {/* DETALLE DEL DÍA */}
      {selectedDay && (
        <div className="mt-4 p-4 border rounded-xl bg-gray-50">
          <h3 className="font-semibold mb-2">
            Eventos del {selectedDay.fecha}
          </h3>

          {selectedDay.eventos.length === 0 ? (
            <p className="text-sm text-gray-400">Sin eventos</p>
          ) : (
            selectedDay.eventos.map(e => (
              <div key={e.id} className="text-sm mb-1">
                <strong>{e.tipo_evento}</strong> - {e.hembra?.alias || "Animal"}
              </div>
            ))
          )}
        </div>
      )}

    </div>
  );
}