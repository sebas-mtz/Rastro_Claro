import React, { useEffect, useMemo, useState } from "react";
import axios from "axios";
import { useForm } from "@inertiajs/react";
import { AlertTriangle, Baby, CalendarDays, Plus, Scale, X } from "lucide-react";

const hoy = () => new Date().toISOString().split("T")[0];

const tipoNacimiento = (cantidad) => ({
  1: "Simple",
  2: "Gemelar",
  3: "Triple",
  4: "Cuádruple",
}[cantidad] ?? `${cantidad} crías`);

const diasDesde = (fecha) => {
  const inicio = new Date(`${fecha}T00:00:00`);
  const fechaActual = new Date();
  fechaActual.setHours(0, 0, 0, 0);
  return Math.max(0, Math.floor((fechaActual - inicio) / 86400000));
};

const nombreAnimal = (animal, respaldo = "Sin identificación") =>
  animal?.alias || animal?.arete || respaldo;

const situacionTerminalLocal = (cria, animal) => {
  if (cria.condicion === "nacido_muerto") return "nacido_muerto";
  if (cria.condicion === "murio_al_nacer") return "murio_al_nacer";
  if (!animal) return "sin_animal";
  if (animal.muerte || animal.estado_productivo === "muerto") return "muerto";
  if (animal.venta || animal.estado_productivo === "vendido") return "vendido";
  if (["faeneado", "sacrificado"].includes(animal.estado_productivo)) {
    return animal.estado_productivo;
  }
  return null;
};

const prepararCrias = (partoEvento, animales) =>
  (partoEvento?.parto?.crias ?? []).map((cria) => {
    const animalListado = animales.find(
      (animal) => String(animal.id) === String(cria.animal_id),
    );
    const animal = cria.animal || animalListado;
    const situacionLocal = situacionTerminalLocal(cria, animal);
    const disponible = cria.disponible_destete ?? (situacionLocal === null);

    return {
      cria,
      animal,
      disponible,
      situacion: cria.situacion || situacionLocal || "disponible",
      fechaBaja: cria.fecha_baja,
      causa: cria.causa_baja,
      observacion: cria.observacion_baja,
    };
  });

const estadoManualInicial = (animal, estadosProductivos, preferido = "reemplazo") => {
  const opciones = estadosProductivos?.[animal?.especie] ?? [];
  const estadoActual = String(animal?.estado_productivo ?? "").trim().toLowerCase();
  const normalizado = opciones.find(
    (opcion) => String(opcion).trim().toLowerCase() === estadoActual,
  );

  if (normalizado) return normalizado;
  if (opciones.includes(preferido)) return preferido;
  return opciones[0] ?? "";
};

const etiquetaSituacion = {
  nacido_muerto: "Nació muerta",
  murio_al_nacer: "Murió al nacer",
  muerto: "Fallecida",
  vendido: "Vendida",
  faeneado: "Faenada",
  sacrificado: "Sacrificada",
  sin_animal: "Sin animal asociado",
};

function NotaCriaNoDisponible({ item }) {
  const { data, setData, patch, processing, errors, reset } = useForm({
    observaciones: "",
  });

  if (item.observacion) {
    return (
      <p className="mt-1 text-xs text-gray-600">
        <span className="font-medium">Nota:</span> {item.observacion}
      </p>
    );
  }

  const guardar = (event) => {
    event.preventDefault();
    patch(route("reproduccion.crias.observaciones", item.cria.id), {
      preserveScroll: true,
      onSuccess: () => reset(),
    });
  };

  return (
    <form onSubmit={guardar} className="mt-3 flex flex-col gap-2 sm:flex-row">
      <input
        value={data.observaciones}
        onChange={(e) => setData("observaciones", e.target.value)}
        placeholder="Agregar una nota para Control de partos"
        className="min-w-0 flex-1 rounded-lg border p-2 text-xs"
      />
      <button
        type="submit"
        disabled={processing || !data.observaciones.trim()}
        className="rounded-lg border border-amber-300 px-3 py-2 text-xs font-medium text-amber-800 disabled:opacity-50"
      >
        {processing ? "Guardando..." : "Guardar nota"}
      </button>
      {errors.observaciones && (
        <p className="text-xs text-red-500 sm:basis-full">{errors.observaciones}</p>
      )}
    </form>
  );
}

function CriasNoDisponibles({ items, compact = false }) {
  if (!items.length) return null;

  return (
    <div className={compact ? "mt-3 space-y-2" : "space-y-2"}>
      {!compact && <h3 className="text-sm font-semibold">Crías no disponibles</h3>}
      {items.map((item) => (
        <div
          key={item.cria.id}
          className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm"
        >
          <div className="flex flex-wrap items-center justify-between gap-2">
            <p className="font-medium text-amber-900">
              {nombreAnimal(item.animal, `Cría #${item.cria.id}`)}
            </p>
            <span className="rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">
              {etiquetaSituacion[item.situacion] || item.situacion}
            </span>
          </div>
          <div className="mt-1 flex flex-wrap gap-3 text-xs text-amber-800">
            {item.causa && <span>Causa: {item.causa}</span>}
            {item.fechaBaja && <span>Fecha: {item.fechaBaja}</span>}
          </div>
          <NotaCriaNoDisponible item={item} />
        </div>
      ))}
    </div>
  );
}

function NuevoLoteModal({ show, onClose, onCreated }) {
  const [data, setData] = useState({
    nombre: "",
    corral_potrero: "",
    descripcion: "",
  });
  const [errors, setErrors] = useState({});
  const [processing, setProcessing] = useState(false);

  useEffect(() => {
    if (!show) return;
    setData({ nombre: "", corral_potrero: "", descripcion: "" });
    setErrors({});
  }, [show]);

  if (!show) return null;

  const enviar = async (event) => {
    event.preventDefault();
    setProcessing(true);
    setErrors({});

    try {
      const response = await axios.post(route("reproduccion.lotes.store"), data);
      onCreated(response.data.lote);
      onClose();
    } catch (error) {
      setErrors(error.response?.data?.errors ?? {
        general: "No se pudo crear el lote. Intenta nuevamente.",
      });
    } finally {
      setProcessing(false);
    }
  };

  return (
    <div className="fixed inset-0 z-[90] flex items-center justify-center bg-black/50 p-4">
      <div className="w-full max-w-lg rounded-2xl bg-white shadow-xl">
        <div className="flex items-center justify-between border-b px-6 py-4">
          <div>
            <h3 className="text-lg font-bold">Nuevo lote</h3>
            <p className="text-xs text-gray-500">
              Al crearlo quedará seleccionado para esta cría.
            </p>
          </div>
          <button type="button" onClick={onClose}><X size={20} /></button>
        </div>

        <form onSubmit={enviar} className="space-y-4 p-6">
          <div>
            <label className="text-sm font-medium">Nombre *</label>
            <input
              value={data.nombre}
              onChange={(e) => setData((actual) => ({ ...actual, nombre: e.target.value }))}
              className="mt-1 w-full rounded-lg border p-2 text-sm"
              autoFocus
            />
            {errors.nombre && <p className="mt-1 text-xs text-red-500">{errors.nombre}</p>}
          </div>

          <div>
            <label className="text-sm font-medium">Corral o potrero</label>
            <input
              value={data.corral_potrero}
              onChange={(e) => setData((actual) => ({ ...actual, corral_potrero: e.target.value }))}
              className="mt-1 w-full rounded-lg border p-2 text-sm"
            />
          </div>

          <div>
            <label className="text-sm font-medium">Descripción</label>
            <textarea
              value={data.descripcion}
              onChange={(e) => setData((actual) => ({ ...actual, descripcion: e.target.value }))}
              rows={2}
              className="mt-1 w-full resize-none rounded-lg border p-2 text-sm"
            />
          </div>

          {errors.general && <p className="text-sm text-red-600">{errors.general}</p>}

          <div className="flex justify-end gap-2">
            <button type="button" onClick={onClose} className="rounded-lg border px-4 py-2 text-sm">
              Cancelar
            </button>
            <button
              type="submit"
              disabled={processing}
              className="rounded-lg bg-blue-700 px-4 py-2 text-sm text-white disabled:opacity-50"
            >
              {processing ? "Creando..." : "Crear y seleccionar"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

function DesteteModal({
  partoEvento,
  animales,
  lotes,
  estadosProductivos,
  onClose,
}) {
  const [indiceNuevoLote, setIndiceNuevoLote] = useState(null);
  const [lotesDisponibles, setLotesDisponibles] = useState(lotes);
  const { data, setData, post, processing, errors, reset } = useForm({
    parto_id: "",
    fecha: hoy(),
    estado_madre: "bueno",
    estado_productivo_madre: "",
    observaciones: "",
    crias: [],
  });

  const crias = useMemo(
    () => prepararCrias(partoEvento, animales),
    [partoEvento, animales],
  );
  const disponibles = useMemo(
    () => crias.filter((item) => item.disponible),
    [crias],
  );
  const noDisponibles = useMemo(
    () => crias.filter((item) => !item.disponible),
    [crias],
  );

  useEffect(() => {
    setLotesDisponibles(lotes);
  }, [lotes]);

  useEffect(() => {
    if (!partoEvento) return;

    setData({
      parto_id: partoEvento.parto.id,
      fecha: hoy(),
      estado_madre: "bueno",
      estado_productivo_madre: estadoManualInicial(
        partoEvento.hembra,
        estadosProductivos,
        "mantenimiento",
      ),
      observaciones: "",
      crias: disponibles.map(({ cria, animal }) => ({
        cria_id: cria.id,
        peso_destete: animal?.peso ?? "",
        estado_destino: estadoManualInicial(animal, estadosProductivos),
        lote_id: animal?.lote_id || "",
      })),
    });
  }, [partoEvento?.id]);

  if (!partoEvento) return null;

  const actualizarCria = (index, campo, valor) => {
    const criasActualizadas = [...data.crias];
    criasActualizadas[index] = { ...criasActualizadas[index], [campo]: valor };
    setData("crias", criasActualizadas);
  };

  const seleccionarLoteCreado = (lote) => {
    setLotesDisponibles((actuales) => {
      if (actuales.some((actual) => String(actual.id) === String(lote.id))) {
        return actuales;
      }
      return [...actuales, lote];
    });

    if (indiceNuevoLote !== null) {
      actualizarCria(indiceNuevoLote, "lote_id", String(lote.id));
    }
  };

  const enviar = (event) => {
    event.preventDefault();
    post(route("reproduccion.destetes.store"), {
      preserveScroll: true,
      onSuccess: () => {
        reset();
        onClose();
      },
    });
  };

  const mensajesError = [...new Set(
    Object.values(errors)
      .flatMap((error) => Array.isArray(error) ? error : [error])
      .filter(Boolean),
  )];
  const opcionesMadre = estadosProductivos?.[partoEvento.hembra?.especie] ?? [];

  return (
    <>
      <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div className="max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-2xl bg-white">
          <div className="sticky top-0 z-10 flex items-center justify-between border-b bg-white px-6 py-4">
            <div>
              <h2 className="text-lg font-bold">Registrar destete</h2>
              <p className="text-xs text-gray-500">
                {nombreAnimal(partoEvento.hembra)} · Destete {tipoNacimiento(disponibles.length)}
              </p>
            </div>
            <button type="button" onClick={onClose}><X size={20} /></button>
          </div>

          <form onSubmit={enviar} className="space-y-5 p-6">
            {mensajesError.length > 0 && (
              <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                {mensajesError.map((mensaje) => <p key={mensaje}>{mensaje}</p>)}
              </div>
            )}

            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
              <div>
                <label className="text-sm font-medium">Fecha del destete *</label>
                <input
                  type="date"
                  value={data.fecha}
                  min={partoEvento.fecha}
                  max={hoy()}
                  onChange={(e) => setData("fecha", e.target.value)}
                  className="mt-1 w-full rounded-lg border p-2 text-sm"
                />
              </div>

              <div>
                <label className="text-sm font-medium">Condición de la madre *</label>
                <select
                  value={data.estado_madre}
                  onChange={(e) => setData("estado_madre", e.target.value)}
                  className="mt-1 w-full rounded-lg border p-2 text-sm"
                >
                  <option value="bueno">Bueno (B)</option>
                  <option value="regular">Regular (R)</option>
                  <option value="malo">Malo (M)</option>
                </select>
              </div>

              <div>
                <label className="text-sm font-medium">Estado productivo de la madre *</label>
                <select
                  value={data.estado_productivo_madre}
                  onChange={(e) => setData("estado_productivo_madre", e.target.value)}
                  className="mt-1 w-full rounded-lg border p-2 text-sm"
                >
                  {opcionesMadre.map((estado) => (
                    <option key={estado} value={estado}>{estado}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="text-sm font-medium">Tipo de destete</label>
                <input
                  value={tipoNacimiento(disponibles.length)}
                  disabled
                  className="mt-1 w-full rounded-lg border bg-gray-50 p-2 text-sm"
                />
                <p className="mt-1 text-xs text-gray-400">
                  Calculado con las crías disponibles.
                </p>
              </div>
            </div>

            <div>
              <h3 className="mb-2 text-sm font-semibold">Crías a destetar</h3>
              <div className="space-y-3">
                {disponibles.map(({ cria, animal }, index) => {
                  const opciones = [...new Set(estadosProductivos?.[animal?.especie] ?? [])];
                  return (
                    <div key={cria.id} className="rounded-xl border p-4">
                      <p className="mb-3 text-sm font-medium">
                        {nombreAnimal(animal, `Cría #${cria.id}`)}
                        <span className="ml-2 text-xs font-normal text-gray-500">
                          {cria.sexo === "macho" ? "Macho" : "Hembra"}
                        </span>
                      </p>
                      <div className="grid gap-3 md:grid-cols-3">
                        <div>
                          <label className="text-xs font-medium">Peso de destete (kg) *</label>
                          <input
                            type="number"
                            min="0.01"
                            step="0.01"
                            value={data.crias[index]?.peso_destete ?? ""}
                            onChange={(e) => actualizarCria(index, "peso_destete", e.target.value)}
                            className="mt-1 w-full rounded-lg border p-2 text-sm"
                          />
                        </div>
                        <div>
                          <label className="text-xs font-medium">Nuevo estado *</label>
                          <select
                            value={data.crias[index]?.estado_destino ?? ""}
                            onChange={(e) => actualizarCria(index, "estado_destino", e.target.value)}
                            className="mt-1 w-full rounded-lg border p-2 text-sm"
                          >
                            {opciones.map((estado) => (
                              <option key={estado} value={estado}>{estado}</option>
                            ))}
                          </select>
                        </div>
                        <div>
                          <label className="text-xs font-medium">Lote de destino</label>
                          <div className="mt-1 flex gap-2">
                            <select
                              value={data.crias[index]?.lote_id ?? ""}
                              onChange={(e) => actualizarCria(index, "lote_id", e.target.value)}
                              className="min-w-0 flex-1 rounded-lg border p-2 text-sm"
                            >
                              <option value="">Sin lote</option>
                              {lotesDisponibles.map((lote) => (
                                <option key={lote.id} value={lote.id}>{lote.nombre}</option>
                              ))}
                            </select>
                            <button
                              type="button"
                              title="Crear un lote nuevo"
                              onClick={() => setIndiceNuevoLote(index)}
                              className="flex items-center gap-1 rounded-lg border px-3 text-xs text-blue-700 hover:bg-blue-50"
                            >
                              <Plus size={14} /> Nuevo
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>

            <CriasNoDisponibles items={noDisponibles} />

            <div>
              <label className="text-sm font-medium">Observaciones del destete</label>
              <textarea
                value={data.observaciones}
                onChange={(e) => setData("observaciones", e.target.value)}
                rows={2}
                className="mt-1 w-full resize-none rounded-lg border p-2 text-sm"
              />
            </div>

            <div className="flex justify-end gap-2">
              <button type="button" onClick={onClose} className="rounded-lg border px-4 py-2 text-sm">
                Cancelar
              </button>
              <button
                type="submit"
                disabled={processing || disponibles.length === 0}
                className="rounded-lg bg-blue-700 px-4 py-2 text-sm text-white disabled:opacity-50"
              >
                {processing ? "Guardando..." : "Guardar destete"}
              </button>
            </div>
          </form>
        </div>
      </div>

      <NuevoLoteModal
        show={indiceNuevoLote !== null}
        onClose={() => setIndiceNuevoLote(null)}
        onCreated={seleccionarLoteCreado}
      />
    </>
  );
}

export default function Destetes({
  eventos = [],
  animales = [],
  lotes = [],
  estadosProductivos = {},
}) {
  const [seleccionado, setSeleccionado] = useState(null);

  useEffect(() => {
    if (!seleccionado) return;

    const actualizado = eventos.find(
      (evento) => String(evento.id) === String(seleccionado.id),
    );

    if (actualizado && actualizado !== seleccionado) {
      setSeleccionado(actualizado);
    }
  }, [eventos, seleccionado]);

  const pendientes = useMemo(
    () => eventos
      .filter((evento) => evento.tipo_evento === "parto" && evento.parto && !evento.parto.destetado)
      .sort((a, b) => new Date(a.fecha) - new Date(b.fecha)),
    [eventos],
  );

  const registrados = useMemo(
    () => eventos
      .filter((evento) => evento.tipo_evento === "parto" && evento.parto?.destete)
      .sort((a, b) => new Date(b.parto.destete.fecha) - new Date(a.parto.destete.fecha)),
    [eventos],
  );

  return (
    <div className="space-y-5">
      <div className="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div className="border-b p-5">
          <h2 className="text-lg font-semibold">Destetes pendientes</h2>
          <p className="text-sm text-gray-500">
            Todos los partos sin destete, sin importar su antigüedad
          </p>
        </div>

        {pendientes.length === 0 ? (
          <p className="p-6 text-sm text-gray-400">No hay partos pendientes de destete.</p>
        ) : (
          <div className="divide-y">
            {pendientes.map((evento) => {
              const crias = prepararCrias(evento, animales);
              const disponibles = crias.filter((item) => item.disponible);
              const noDisponibles = crias.filter((item) => !item.disponible);
              const cantidadNacimiento = evento.parto.numero_crias || crias.length;

              return (
                <div key={evento.id} className="p-4">
                  <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex gap-3">
                      <Baby className="mt-0.5 h-5 w-5 text-blue-600" />
                      <div>
                        <p className="font-medium">
                          {nombreAnimal(evento.hembra)}
                          {evento.hembra?.alias && evento.hembra?.arete ? ` (${evento.hembra.arete})` : ""}
                        </p>
                        <div className="mt-1 flex flex-wrap gap-3 text-xs text-gray-500">
                          <span className="flex items-center gap-1"><CalendarDays size={13} /> Parto: {evento.fecha}</span>
                          <span>{diasDesde(evento.fecha)} días transcurridos</span>
                          <span>Parto {tipoNacimiento(cantidadNacimiento)}</span>
                          <span className="flex items-center gap-1">
                            <Scale size={13} /> Destete {tipoNacimiento(disponibles.length)}
                          </span>
                        </div>
                      </div>
                    </div>

                    {disponibles.length > 0 ? (
                      <button
                        type="button"
                        onClick={() => setSeleccionado(evento)}
                        className="rounded-lg bg-blue-700 px-4 py-2 text-sm text-white hover:bg-blue-800"
                      >
                        Añadir destete
                      </button>
                    ) : (
                      <div className="flex max-w-sm items-start gap-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                        <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" />
                        <span>
                          No puede registrarse el destete porque ninguna cría continúa disponible.
                        </span>
                      </div>
                    )}
                  </div>

                  {disponibles.length === 0 && (
                    <CriasNoDisponibles items={noDisponibles} compact />
                  )}
                </div>
              );
            })}
          </div>
        )}
      </div>

      <div className="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div className="border-b p-5">
          <h2 className="text-lg font-semibold">Destetes registrados</h2>
          <p className="text-sm text-gray-500">Historial de destetes guardados</p>
        </div>

        {registrados.length === 0 ? (
          <p className="p-6 text-sm text-gray-400">Aún no hay destetes registrados.</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full min-w-[800px] text-left text-sm">
              <thead className="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                  <th className="px-4 py-3">Madre</th>
                  <th className="px-4 py-3">Fecha del parto</th>
                  <th className="px-4 py-3">Fecha del destete</th>
                  <th className="px-4 py-3">Tipo de destete</th>
                  <th className="px-4 py-3">Condición madre</th>
                  <th className="px-4 py-3">Estado productivo madre</th>
                  <th className="px-4 py-3">Crías</th>
                </tr>
              </thead>
              <tbody className="divide-y">
                {registrados.map((evento) => (
                  <tr key={evento.id}>
                    <td className="px-4 py-3 font-medium">{nombreAnimal(evento.hembra)}</td>
                    <td className="px-4 py-3">{evento.fecha}</td>
                    <td className="px-4 py-3">{evento.parto.destete.fecha}</td>
                    <td className="px-4 py-3 capitalize">
                      {evento.parto.destete.tipo_nacimiento?.replace("_", " ")}
                    </td>
                    <td className="px-4 py-3 capitalize">{evento.parto.destete.estado_madre}</td>
                    <td className="px-4 py-3">{evento.parto.destete.estado_productivo_madre || "—"}</td>
                    <td className="px-4 py-3">{evento.parto.destete.detalles?.length ?? 0}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      <DesteteModal
        partoEvento={seleccionado}
        animales={animales}
        lotes={lotes}
        estadosProductivos={estadosProductivos}
        onClose={() => setSeleccionado(null)}
      />
    </div>
  );
}
