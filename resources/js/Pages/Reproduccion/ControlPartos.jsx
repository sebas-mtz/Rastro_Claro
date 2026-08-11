import React, { useMemo } from "react";
import { ClipboardList } from "lucide-react";


const fechaLocal = (fecha) => {
  if (!fecha) return null;
  return new Date(`${fecha.substring(0, 10)}T00:00:00`);
};

const pesajeMasCercano = (pesajes, fechaParto, diasObjetivo) => {
  if (!pesajes?.length || !fechaParto) return null;

  const inicioParto = fechaLocal(fechaParto);
  const objetivo = fechaLocal(fechaParto);
  objetivo.setDate(objetivo.getDate() + diasObjetivo);

  return pesajes
    .filter((pesaje) => fechaLocal(pesaje.fecha) >= inicioParto)
    .reduce((masCercano, pesaje) => {
      const diferencia = Math.abs(fechaLocal(pesaje.fecha) - objetivo);
      if (!masCercano || diferencia < masCercano.diferencia) {
        return { ...pesaje, diferencia };
      }
      return masCercano;
    }, null);
};

const badge = (texto, colores) => (
  <span className={`inline-flex rounded-full px-2 py-1 text-xs font-medium ${colores}`}>
    {texto}
  </span>
);

const identificacion = (principal, secundaria) => {
  const valores = [principal, secundaria].filter(Boolean);
  return [...new Set(valores)];
};

const CeldaIdentificacion = ({ principal, secundaria, vacio = "Sin identificación" }) => {
  const valores = identificacion(principal, secundaria);

  if (!valores.length) {
    return <span className="text-gray-400">{vacio}</span>;
  }

  return (
    <div>
      <p className="font-medium">{valores[0]}</p>
      {valores[1] && <p className="text-xs text-gray-500">{valores[1]}</p>}
    </div>
  );
};

const CeldaPesaje = ({ pesaje, bloqueado }) => {
  if (bloqueado || !pesaje) {
    return <span className="text-gray-400">—</span>;
  }

  return (
    <div className="whitespace-nowrap">
      <p className="font-medium text-gray-800">{Number(pesaje.peso).toFixed(2)} kg</p>
      <p className="text-xs text-gray-500">{pesaje.fecha}</p>
    </div>
  );
};

const observacionesAnimal = (cria) => {
  const animal = cria.animal;

  if (!animal) {
    const condicion = {
      nacido_muerto: "Nacido muerto",
      murio_al_nacer: "Murió al nacer",
    };
    return [
      condicion[cria.condicion] ?? "Sin animal asociado",
      cria.observaciones,
    ].filter(Boolean).join(" · ");
  }

  if (animal.muerte || animal.estado_productivo === "muerto") {
    return [
      "Estado: Muerto",
      animal.muerte?.causa ? `Causa: ${animal.muerte.causa}` : null,
      animal.muerte?.observaciones,
      cria.observaciones,
    ].filter(Boolean).join(" · ");
  }

  if (animal.venta || animal.estado_productivo === "vendido") {
    return [
      "Estado: Vendido",
      animal.venta?.fecha ? `Fecha: ${animal.venta.fecha}` : null,
      animal.venta?.observaciones,
      cria.observaciones,
    ].filter(Boolean).join(" · ");
  }

  return [
    `Estado: ${animal.estado_productivo || "Sin definir"}`,
    cria.observaciones,
  ].filter(Boolean).join(" · ");
};

export default function ControlPartos({ eventos = [] }) {
  const filas = useMemo(
    () =>
      eventos
        .filter((evento) => evento.tipo_evento === "parto" && evento.parto)
        .sort((a, b) => new Date(b.fecha) - new Date(a.fecha))
        .flatMap((evento) => {
          const crias = evento.parto.crias ?? [];
          const numeroCrias = evento.parto.numero_crias || crias.length;
const tipoNacimiento = evento.parto.tipo_nacimiento;

          if (!crias.length) {
            return [{
              evento,
              cria: null,
              numeroCrias,
              tipoNacimiento,
            }];
          }

          return crias.map((cria) => ({
            evento,
            cria,
            numeroCrias,
            tipoNacimiento,
          }));
        }),
    [eventos],
  );

  const vigorBadge = (vigor) => {
    const etiqueta = {
      B: "Bueno",
      R: "Regular",
      M: "Malo",
    }[vigor] ?? "Sin datos";

    const colores = {
      Bueno: "bg-green-100 text-green-700",
      Regular: "bg-amber-100 text-amber-700",
      Malo: "bg-red-100 text-red-700",
      "Sin datos": "bg-gray-100 text-gray-600",
    };

    return badge(etiqueta, colores[etiqueta]);
  };

  return (
    <div className="overflow-hidden rounded-xl border border-gray-200 bg-white">
      <div className="border-b p-5">
        <div className="flex items-center gap-2">
          <ClipboardList className="h-5 w-5 text-blue-700" />
          <h2 className="text-lg font-semibold">Control de partos</h2>
        </div>
        <p className="mt-1 text-sm text-gray-500">
          Historial del parto y seguimiento de peso individual de cada cría
        </p>
      </div>

      {filas.length === 0 ? (
        <p className="p-6 text-sm text-gray-400">Sin partos registrados</p>
      ) : (
        <>
          <div className="overflow-x-auto">
            <table className="min-w-[2400px] w-full text-left text-sm">
              <thead className="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                <tr>
                  <th className="px-3 py-3">ID parto</th>
                  <th className="px-3 py-3">Fecha</th>
                  <th className="px-3 py-3">Madre</th>
                  <th className="px-3 py-3">Cría</th>
                  <th className="px-3 py-3">Padre</th>
                  <th className="px-3 py-3">Facilidad materna</th>
                  <th className="px-3 py-3">Tipo de parto</th>
                  <th className="px-3 py-3">Asistencia</th>
                  <th className="px-3 py-3">Complicaciones</th>
                  <th className="px-3 py-3">Detalle</th>
                  <th className="px-3 py-3">N.º crías</th>
                  <th className="px-3 py-3">Clasificación</th>
                  <th className="px-3 py-3">Leche</th>
                  <th className="px-3 py-3">Vigor</th>
                  <th className="bg-blue-50 px-3 py-3 text-blue-700">30 días</th>
                  <th className="bg-blue-50 px-3 py-3 text-blue-700">Destete</th>
                  <th className="bg-blue-50 px-3 py-3 text-blue-700">90 días</th>
                  <th className="bg-blue-50 px-3 py-3 text-blue-700">120 días</th>
                  <th className="bg-blue-50 px-3 py-3 text-blue-700">150 días</th>
                  <th className="px-3 py-3">Observaciones</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {filas.map(({ evento, cria, numeroCrias, tipoNacimiento }, index) => {
                  const animal = cria?.animal;
                  const estado = animal?.estado_productivo;
                  const bloqueado =
                    !animal ||
                    Boolean(animal.muerte) ||
                    Boolean(animal.venta) ||
                    ["muerto", "vendido"].includes(estado);
                  const pesajes = animal?.pesajes ?? [];
                  const detalleDestete = evento.parto.destete?.detalles?.find(
                    (detalle) => String(detalle.cria_id) === String(cria?.id),
                  );
                  const padre = animal?.padre;
                  const padreExterno = animal?.padre_externo;

                  return (
                    <tr key={`${evento.id}-${cria?.id ?? index}`} className="align-top hover:bg-gray-50/70">
                      <td className="px-3 py-3 font-medium">#{evento.parto.id}</td>
                      <td className="whitespace-nowrap px-3 py-3">{evento.fecha}</td>
                      <td className="px-3 py-3">
                        <CeldaIdentificacion
                          principal={evento.hembra?.alias}
                          secundaria={evento.hembra?.arete}
                        />
                      </td>
                      <td className="px-3 py-3">
                        {cria ? (
                          <>
                            <p className="font-medium">
                              {animal?.alias || animal?.arete || `Cría #${cria.id}`}
                            </p>
                            <p className="text-xs text-gray-500">
                              {cria.sexo === "macho" ? "Macho" : "Hembra"} · {cria.condicion}
                            </p>
                          </>
                        ) : "—"}
                      </td>
                      <td className="whitespace-nowrap px-3 py-3">
                        {padre ? (
                          <CeldaIdentificacion
                            principal={padre.alias}
                            secundaria={padre.arete}
                          />
                        ) : padreExterno ? (
                          <CeldaIdentificacion
                            principal={padreExterno.nombre}
                            secundaria={padreExterno.codigo}
                            vacio="Donador externo"
                          />
                        ) : (
                          <span className="text-gray-400">—</span>
                        )}
                      </td>
                      <td className="px-3 py-3">
                        {evento.parto.facilidad_materna
                          ? badge("Sí", "bg-green-100 text-green-700")
                          : badge("No", "bg-gray-100 text-gray-600")}
                      </td>
                      <td className="px-3 py-3">
                        {evento.parto.tipo_parto === "distocico"
                          ? "Distócico"
                          : evento.parto.tipo_parto === "cesarea"
                            ? "Cesárea"
                            : "Normal"}
                      </td>
                      <td className="px-3 py-3">{evento.parto.asistencia_requerida ? "Sí" : "No"}</td>
                      <td className="px-3 py-3">{evento.parto.complicaciones ? "Sí" : "No"}</td>
                      <td className="max-w-56 px-3 py-3 text-gray-600">
                        {evento.parto.detalle_complicaciones || "—"}
                      </td>
                      <td className="px-3 py-3 font-medium">{numeroCrias}</td>
                      <td className="px-3 py-3">
                        {badge(tipoNacimiento, "bg-blue-100 text-blue-700")}
                      </td>
                      <td className="px-3 py-3">
                      <div>
                        {evento.parto.salio_leche
                          ? badge("Sí", "bg-sky-100 text-sky-700")
                          : badge("No", "bg-gray-100 text-gray-600")}

                        {evento.parto.observaciones_leche && (
                          <p className="text-xs text-gray-500 mt-1">
                            {evento.parto.observaciones_leche}
                          </p>
                        )}
                      </div>
                    </td>
                      <td className="px-3 py-3">{vigorBadge(cria?.vigor)}</td>
                      <td className="bg-blue-50/30 px-3 py-3">
                        <CeldaPesaje
                          bloqueado={bloqueado}
                          pesaje={pesajeMasCercano(pesajes, evento.fecha, 30)}
                        />
                      </td>
                      <td className="bg-blue-50/30 px-3 py-3">
                        {bloqueado ? (
                          <span className="text-gray-400">—</span>
                        ) : evento.parto.destete ? (
                          <div className="whitespace-nowrap">
                            <p className="font-medium capitalize">
                              {evento.parto.destete.tipo_nacimiento?.replace("_", " ") || tipoNacimiento}
                            </p>
                            <p className="text-xs text-gray-500">
                              {evento.parto.destete.fecha || "Sin fecha"}
                              {detalleDestete?.peso_destete
                                ? ` · ${Number(detalleDestete.peso_destete).toFixed(2)} kg`
                                : ""}
                            </p>
                          </div>
                        ) : (
                          <div className="whitespace-nowrap">
                            <p className="font-medium">{tipoNacimiento}</p>
                            <p className="text-xs text-amber-600">Pendiente</p>
                          </div>
                        )}
                      </td>
                      {[90, 120, 150].map((dias) => (
                        <td key={dias} className="bg-blue-50/30 px-3 py-3">
                          <CeldaPesaje
                            bloqueado={bloqueado}
                            pesaje={pesajeMasCercano(pesajes, evento.fecha, dias)}
                          />
                        </td>
                      ))}
                      <td className="max-w-80 px-3 py-3 text-xs leading-5 text-gray-600">
                        {cria ? observacionesAnimal(cria) : "Sin crías registradas"}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>

          <p className="border-t bg-gray-50 px-5 py-3 text-xs text-gray-500">
            Cada seguimiento muestra el pesaje histórico cuya fecha es la más cercana al día indicado,
            contado desde la fecha del parto.
          </p>
        </>
      )}
    </div>
  );
}
