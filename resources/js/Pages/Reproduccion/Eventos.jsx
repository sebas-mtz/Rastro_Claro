import React, { useMemo, useState } from "react";
import { Heart, AlertTriangle, Clock } from "lucide-react";

export default function Eventos({ animales = [], eventos = [], onNuevo }) {

  const [verTodos, setVerTodos] = useState(false);

  // Agrupar servicios por hembra (último servicio de cada una)
  const items = useMemo(() => {
    const servicios = eventos.filter(e => e.tipo_evento === "servicio");

    return animales
      .filter(a => a.sexo === "hembra")
      .map((a) => {
        const svsHembra = servicios
          .filter(s => s.hembra_id === a.id)
          .sort((x, y) => new Date(y.fecha) - new Date(x.fecha));

        const ultimo = svsHembra[0] || null;

        // Buscar si tiene diagnóstico posterior al último servicio
        const tieneDiagnostico = ultimo
          ? eventos.some(e =>
              e.tipo_evento === "diagnostico" &&
              e.hembra_id === a.id &&
              new Date(e.fecha) > new Date(ultimo.fecha)
            )
          : false;

        // Días desde el último servicio
        const diasDesde = ultimo
          ? Math.floor((new Date() - new Date(ultimo.fecha)) / 86400000)
          : null;

        // Estado
        let estado = "sin_servicio";
        if (ultimo) {
          if (!tieneDiagnostico && diasDesde >= 45) estado = "pendiente_diagnostico";
          else if (!tieneDiagnostico) estado = "servida";
          else estado = "diagnosticada";
        }

        return {
          id: a.id,
          nombre: `${a.alias || "Animal"} (#${a.arete || a.id})`,
          especie: a.especie,
          ultimaFecha: ultimo?.fecha || null,
          tipoServicio: ultimo?.servicio?.tipo_servicio || null,
          descripcion: ultimo?.servicio?.descripcion || null,
          diasDesde,
          estado,
        };
      });
  }, [animales, eventos]);

  // Ordenar por urgencia
  const ordenados = useMemo(() => {
    const prioridad = {
      pendiente_diagnostico: 1,
      servida: 2,
      diagnosticada: 3,
      sin_servicio: 4,
    };
    return [...items].sort((a, b) => prioridad[a.estado] - prioridad[b.estado]);
  }, [items]);

  const visibles = verTodos
    ? ordenados
    : ordenados.filter(i => i.estado !== "sin_servicio");

  const badge = (estado) => {
    const map = {
      pendiente_diagnostico: "bg-orange-100 text-orange-700",
      servida:               "bg-blue-100 text-blue-700",
      diagnosticada:         "bg-green-100 text-green-700",
      sin_servicio:          "bg-gray-100 text-gray-500",
    };
    const label = {
      pendiente_diagnostico: "Pendiente diagnóstico",
      servida:               "Servida",
      diagnosticada:         "Diagnosticada",
      sin_servicio:          "Sin servicio",
    };
    return (
      <span className={`px-3 py-1 text-xs rounded-full font-medium ${map[estado]}`}>
        {label[estado]}
      </span>
    );
  };

  const icon = (estado) => {
    if (estado === "pendiente_diagnostico")
      return <AlertTriangle className="w-5 h-5 text-orange-500" />;
    if (estado === "servida")
      return <Clock className="w-5 h-5 text-blue-500" />;
    return <Heart className="w-5 h-5 text-green-600" />;
  };

  return (
    <div className="bg-white rounded-xl border border-gray-200">
      <div className="p-5 border-b">
        <h2 className="text-lg font-semibold">Servicios reproductivos</h2>
        <p className="text-sm text-gray-500">Montas e inseminaciones del hato</p>
      </div>

      <div className="p-5 space-y-3">
        {visibles.length === 0 ? (
          <p className="text-sm text-gray-400">No hay servicios registrados</p>
        ) : (
          visibles.map((it) => (
            <div key={it.id} className="border rounded-xl p-4 flex justify-between items-center">
              <div className="flex gap-3">
                <div className="mt-0.5">{icon(it.estado)}</div>
                <div>
                  <p className="font-semibold text-sm">{it.nombre}</p>
                  {it.ultimaFecha && (
                    <p className="text-xs text-gray-500 mt-0.5">
                      Último servicio: {it.ultimaFecha}
                      {it.diasDesde !== null && ` (hace ${it.diasDesde} días)`}
                    </p>
                  )}
                  {it.descripcion && (
                    <p className="text-xs text-gray-400 mt-0.5">{it.descripcion}</p>
                  )}
                </div>
              </div>
              <div className="flex items-center gap-2">
                {badge(it.estado)}
                <button
                  onClick={onNuevo}
                  className="px-3 py-1.5 border rounded-lg text-xs hover:bg-gray-50"
                >
                  Registrar
                </button>
              </div>
            </div>
          ))
        )}

        <button
          onClick={() => setVerTodos(!verTodos)}
          className="w-full text-sm text-blue-600 py-1"
        >
          {verTodos ? "Mostrar menos" : "Ver todos los animales"}
        </button>

        <button
          onClick={onNuevo}
          className="w-full bg-black text-white py-3 rounded-lg flex justify-center items-center gap-2 text-sm"
        >
          <Heart size={15} /> Nuevo servicio
        </button>
      </div>
    </div>
  );
}