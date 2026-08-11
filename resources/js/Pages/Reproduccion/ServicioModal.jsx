import React, { useEffect, useMemo } from "react";
import { useForm } from "@inertiajs/react";
import { Check, X } from "lucide-react";

export default function ServicioModal({
  show,onClose,hembras = [],machos = [],pajillas = [],}) {
  const {data,setData,post,processing,errors,reset,
  } = useForm({tipo_servicio: "monta_natural",macho_id: "",donador_key: "",hembra_ids: [],pajilla_ids: [],fecha: new Date().toISOString().split("T")[0],tecnico_externo: "",observaciones: "",});

  /*
   * Tipos que utilizan directamente un semental.
   */
  const esMonta = ["monta_natural","monta_controlada",].includes(data.tipo_servicio);

  /*
   * Todos los demás tipos utilizan pajillas.
   */
  const usaPajillas = [
    "inseminacion_artificial",
    "iatf",
    "transferencia_embriones","fiv",].includes(data.tipo_servicio);

  /*
   * Solo se consideran pajillas disponibles.
   *
   * Si el backend ya manda únicamente pajillas disponibles,
   * este filtro no afecta el resultado.
   */
  const pajillasDisponibles = useMemo(() => {
    return pajillas.filter(
      (pajilla) =>
        !pajilla.estado ||
        !["utilizada", "agotada", "descartada"].includes(pajilla.estado)
    );
  }, [pajillas]);

  /*
   * Agrupar las pajillas por donador.
   *
   * La clave permite diferenciar donadores internos y externos,
   * aunque accidentalmente tengan el mismo ID.
   */
  const donadores = useMemo(() => {
    const grupos = new Map();

    pajillasDisponibles.forEach((pajilla) => {
      const esExterno =
        pajilla.tipo_donador === "externo" ||
        Boolean(pajilla.donador_externo_id);

      const donadorId =
        pajilla.donador?.id ??
        pajilla.animal_id ??
        pajilla.donador_externo_id;

      if (!donadorId) {
        return;
      }

      const tipoDonador = esExterno ? "externo" : "interno";
      const key = `${tipoDonador}:${donadorId}`;

      if (!grupos.has(key)) {
        grupos.set(key, {
          key,
          id: donadorId,
          tipo: tipoDonador,
          nombre:
            pajilla.donador?.nombre ??
            pajilla.donador?.alias ??
            pajilla.donador_nombre ??
            "Donador sin nombre",
          arete:
            pajilla.donador?.arete ??
            pajilla.donador_arete ??
            null,
          raza:
            pajilla.donador?.raza ??
            pajilla.raza ??
            null,
          especie:
            pajilla.donador?.especie ??
            pajilla.especie ??
            null,
          pajillas: [],
        });
      }

      grupos.get(key).pajillas.push(pajilla);
    });

    return Array.from(grupos.values()).sort((a, b) =>
      a.nombre.localeCompare(b.nombre)
    );
  }, [pajillasDisponibles]);

  const machoSeleccionado = useMemo(() => {
    return machos.find(
      (macho) => Number(macho.id) === Number(data.macho_id)
    );
  }, [machos, data.macho_id]);

  const donadorSeleccionado = useMemo(() => {
    return donadores.find(
      (donador) => donador.key === data.donador_key
    );
  }, [donadores, data.donador_key]);

  /*
   * La especie de referencia se obtiene del semental o del donador.
   * Así solamente aparecen hembras compatibles.
   */
  const especieSeleccionada = esMonta
    ? machoSeleccionado?.especie
    : donadorSeleccionado?.especie;

  const hembrasFiltradas = useMemo(() => {
    if (!especieSeleccionada) {
      return hembras;
    }

    return hembras.filter(
      (hembra) => hembra.especie === especieSeleccionada
    );
  }, [hembras, especieSeleccionada]);

  const cantidadPajillasDisponibles =
    donadorSeleccionado?.pajillas.length ?? 0;

  /*
   * Cuando cambian las hembras seleccionadas para un servicio con pajilla,
   * se toman automáticamente las primeras pajillas disponibles del donador.
   *
   * El backend debe volver a validar y bloquear esas pajillas dentro
   * de la transacción.
   */
  useEffect(() => {
    if (!usaPajillas || !donadorSeleccionado) {
      if (data.pajilla_ids.length > 0) {
        setData("pajilla_ids", []);
      }

      return;
    }

    const pajillaIds = donadorSeleccionado.pajillas
      .slice(0, data.hembra_ids.length)
      .map((pajilla) => Number(pajilla.id));

    const actuales = data.pajilla_ids.map(Number);

    const sonIguales =
      pajillaIds.length === actuales.length &&
      pajillaIds.every(
        (pajillaId, index) => pajillaId === actuales[index]
      );

    if (!sonIguales) {
      setData("pajilla_ids", pajillaIds);
    }
  }, [
    usaPajillas,
    donadorSeleccionado,
    data.hembra_ids,
    data.pajilla_ids,
  ]);

  const cambiarTipoServicio = (event) => {
    const tipo = event.target.value;

    setData((current) => ({
      ...current,
      tipo_servicio: tipo,
      macho_id: "",
      donador_key: "",
      hembra_ids: [],
      pajilla_ids: [],
      tecnico_externo: "",
    }));
  };

  const cambiarMacho = (event) => {
    const machoId = event.target.value
      ? Number(event.target.value)
      : "";

    setData((current) => ({
      ...current,
      macho_id: machoId,
      hembra_ids: [],
      pajilla_ids: [],
    }));
  };

  const cambiarDonador = (event) => {
    setData((current) => ({
      ...current,
      donador_key: event.target.value,
      hembra_ids: [],
      pajilla_ids: [],
    }));
  };

  const alternarHembra = (hembraId) => {
    const id = Number(hembraId);
    const yaSeleccionada = data.hembra_ids.includes(id);

    if (yaSeleccionada) {
      setData(
        "hembra_ids",
        data.hembra_ids.filter(
          (seleccionadaId) => seleccionadaId !== id
        )
      );

      return;
    }

    /*
     * En servicios con pajilla no se pueden seleccionar más hembras
     * que pajillas disponibles tenga el donador.
     */
    if (
      usaPajillas &&
      data.hembra_ids.length >= cantidadPajillasDisponibles
    ) {
      return;
    }

    setData("hembra_ids", [...data.hembra_ids, id]);
  };

  const seleccionarTodas = () => {
    if (usaPajillas) {
      const ids = hembrasFiltradas
        .slice(0, cantidadPajillasDisponibles)
        .map((hembra) => Number(hembra.id));

      setData("hembra_ids", ids);

      return;
    }

    setData(
      "hembra_ids",
      hembrasFiltradas.map((hembra) => Number(hembra.id))
    );
  };

  const limpiarSeleccion = () => {
    setData((current) => ({
      ...current,
      hembra_ids: [],
      pajilla_ids: [],
    }));
  };

  const close = () => {
    reset();
    onClose();
  };

  const handleSubmit = (event) => {
    event.preventDefault();

    post(route("reproduccion.servicios.store"), {
      preserveScroll: true,
      onSuccess: close,
    });
  };

  if (!show) {
    return null;
  }

  const proveedorSeleccionado = esMonta
    ? Boolean(data.macho_id)
    : Boolean(data.donador_key);

  return (
    <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
      <div className="bg-white rounded-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div className="flex justify-between items-center px-6 py-4 border-b sticky top-0 bg-white z-10">
          <div>
            <h3 className="font-bold text-lg">
              Registrar servicio
            </h3>

            <p className="text-xs text-gray-500 mt-0.5">
              El servicio y la fecha se aplicarán a todas las
              hembras seleccionadas.
            </p>
          </div>

          <button
            type="button"
            onClick={close}
            className="p-1 rounded hover:bg-gray-100"
          >
            <X size={20} />
          </button>
        </div>

        <form
          onSubmit={handleSubmit}
          className="p-6 space-y-5"
        >
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {/* 1. TIPO DE SERVICIO */}
            <div className="md:col-span-2">
              <label className="text-sm font-medium">
                Tipo de servicio *
              </label>

              <select
                value={data.tipo_servicio}
                onChange={cambiarTipoServicio}
                className="w-full border rounded-lg p-2 mt-1 text-sm"
              >
                <option value="monta_natural">
                  Monta natural
                </option>

                <option value="monta_controlada">
                  Monta controlada
                </option>

                <option value="inseminacion_artificial">
                  Inseminación artificial
                </option>

                <option value="iatf">
                  IATF
                </option>

                <option value="transferencia_embriones">
                  Transferencia de embriones
                </option>

                <option value="fiv">
                  FIV
                </option>
              </select>

              {errors.tipo_servicio && (
                <p className="text-xs text-red-500 mt-1">
                  {errors.tipo_servicio}
                </p>
              )}
            </div>

            {/* 2. MACHO PARA MONTAS */}
            {esMonta && (
              <div className="md:col-span-2">
                <label className="text-sm font-medium">
                  Semental *
                </label>

                <select
                  value={data.macho_id}
                  onChange={cambiarMacho}
                  className="w-full border rounded-lg p-2 mt-1 text-sm"
                >
                  <option value="">
                    Seleccionar semental...
                  </option>

                  {machos.map((macho) => (
                    <option key={macho.id} value={macho.id}>
                      {macho.alias || "Sin alias"}
                      {macho.arete
                        ? ` (${macho.arete})`
                        : ""}
                      {macho.especie
                        ? ` — ${macho.especie}`
                        : ""}
                    </option>
                  ))}
                </select>

                {errors.macho_id && (
                  <p className="text-xs text-red-500 mt-1">
                    {errors.macho_id}
                  </p>
                )}
              </div>
            )}

            {/* 2. DONADOR PARA SERVICIOS CON PAJILLA */}
            {usaPajillas && (
              <div className="md:col-span-2">
                <label className="text-sm font-medium">
                  Donador *
                </label>

                <select
                  value={data.donador_key}
                  onChange={cambiarDonador}
                  className="w-full border rounded-lg p-2 mt-1 text-sm"
                >
                  <option value="">
                    Seleccionar donador...
                  </option>

                  {donadores.map((donador) => (
                    <option
                      key={donador.key}
                      value={donador.key}
                    >
                      {donador.nombre}
                      {donador.arete
                        ? ` (${donador.arete})`
                        : ""}
                      {donador.raza
                        ? ` — ${donador.raza}`
                        : ""}
                      {` — ${
                        donador.tipo === "externo"
                          ? "Externo"
                          : "Interno"
                      }`}
                      {` — ${donador.pajillas.length} pajilla${
                        donador.pajillas.length === 1
                          ? ""
                          : "s"
                      }`}
                    </option>
                  ))}
                </select>

                {errors.donador_key && (
                  <p className="text-xs text-red-500 mt-1">
                    {errors.donador_key}
                  </p>
                )}

                {errors.pajilla_ids && (
                  <p className="text-xs text-red-500 mt-1">
                    {errors.pajilla_ids}
                  </p>
                )}

                {donadorSeleccionado && (
                  <div className="mt-2 rounded-lg bg-gray-50 border px-3 py-2 text-sm">
                    <span className="font-medium">
                      Pajillas disponibles:
                    </span>{" "}
                    {cantidadPajillasDisponibles}
                  </div>
                )}

                {donadores.length === 0 && (
                  <p className="text-xs text-amber-600 mt-1">
                    No hay pajillas disponibles en el módulo de
                    genética.
                  </p>
                )}
              </div>
            )}

            {/* 3. SELECCIÓN MÚLTIPLE DE HEMBRAS */}
            <div className="md:col-span-2">
              <div className="flex flex-wrap items-center justify-between gap-2">
                <label className="text-sm font-medium">
                  Hembras *
                </label>

                <div className="flex items-center gap-3">
                  <span className="text-xs text-gray-500">
                    {data.hembra_ids.length} seleccionada
                    {data.hembra_ids.length === 1 ? "" : "s"}
                    {usaPajillas &&
                      donadorSeleccionado &&
                      ` de ${cantidadPajillasDisponibles}`}
                  </span>

                  <button
                    type="button"
                    onClick={seleccionarTodas}
                    disabled={!proveedorSeleccionado}
                    className="text-xs font-medium underline disabled:text-gray-300"
                  >
                    Seleccionar todas
                  </button>

                  <button
                    type="button"
                    onClick={limpiarSeleccion}
                    disabled={data.hembra_ids.length === 0}
                    className="text-xs font-medium underline disabled:text-gray-300"
                  >
                    Limpiar
                  </button>
                </div>
              </div>

              {!proveedorSeleccionado && (
                <div className="mt-1 border border-dashed rounded-lg p-4 text-center text-sm text-gray-500">
                  {esMonta
                    ? "Primero selecciona un semental."
                    : "Primero selecciona un donador."}
                </div>
              )}

              {proveedorSeleccionado && (
                <div className="mt-1 border rounded-lg max-h-64 overflow-y-auto divide-y">
                  {hembrasFiltradas.map((hembra) => {
                    const seleccionada =
                      data.hembra_ids.includes(
                        Number(hembra.id)
                      );

                    const limiteAlcanzado =
                      usaPajillas &&
                      !seleccionada &&
                      data.hembra_ids.length >=
                        cantidadPajillasDisponibles;

                    return (
                      <label
                        key={hembra.id}
                        className={`flex items-center gap-3 px-3 py-3 ${
                          limiteAlcanzado
                            ? "opacity-50 cursor-not-allowed"
                            : "cursor-pointer hover:bg-gray-50"
                        }`}
                      >
                        <input
                          type="checkbox"
                          checked={seleccionada}
                          disabled={limiteAlcanzado}
                          onChange={() =>
                            alternarHembra(hembra.id)
                          }
                          className="sr-only"
                        />

                        <span
                          className={`w-5 h-5 rounded border flex items-center justify-center ${
                            seleccionada
                              ? "bg-black border-black text-white"
                              : "bg-white border-gray-300"
                          }`}
                        >
                          {seleccionada && (
                            <Check size={14} />
                          )}
                        </span>

                        <span className="flex-1 min-w-0">
                          <span className="block text-sm font-medium truncate">
                            {hembra.alias || "Sin alias"}
                            {hembra.arete
                              ? ` (${hembra.arete})`
                              : ""}
                          </span>

                          <span className="block text-xs text-gray-500 truncate">
                            {hembra.lote_nombre ||
                              "Sin lote"}
                            {hembra.especie
                              ? ` — ${hembra.especie}`
                              : ""}
                          </span>
                        </span>
                      </label>
                    );
                  })}

                  {hembrasFiltradas.length === 0 && (
                    <div className="p-4 text-center text-sm text-gray-500">
                      No hay hembras compatibles con la especie
                      seleccionada.
                    </div>
                  )}
                </div>
              )}

              {errors.hembra_ids && (
                <p className="text-xs text-red-500 mt-1">
                  {errors.hembra_ids}
                </p>
              )}

              {Object.entries(errors)
                .filter(([campo]) =>
                  campo.startsWith("hembra_ids.")
                )
                .map(([campo, mensaje]) => (
                  <p
                    key={campo}
                    className="text-xs text-red-500 mt-1"
                  >
                    {mensaje}
                  </p>
                ))}
            </div>

            {/* 4. FECHA PARA TODAS */}
            <div className="md:col-span-2">
              <label className="text-sm font-medium">
                Fecha *
              </label>

              <input
                type="date"
                value={data.fecha}
                onChange={(event) =>
                  setData("fecha", event.target.value)
                }
                max={new Date().toISOString().split("T")[0]}
                className="w-full border rounded-lg p-2 mt-1 text-sm"
              />

              {errors.fecha && (
                <p className="text-xs text-red-500 mt-1">
                  {errors.fecha}
                </p>
              )}
            </div>

            {/* TÉCNICO PARA SERVICIOS CON PAJILLA */}
            {usaPajillas && (
              <div className="md:col-span-2">
                <label className="text-sm font-medium">
                  Técnico
                </label>

                <input
                  value={data.tecnico_externo}
                  onChange={(event) =>
                    setData(
                      "tecnico_externo",
                      event.target.value
                    )
                  }
                  placeholder="Nombre del técnico"
                  className="w-full border rounded-lg p-2 mt-1 text-sm"
                />

                {errors.tecnico_externo && (
                  <p className="text-xs text-red-500 mt-1">
                    {errors.tecnico_externo}
                  </p>
                )}
              </div>
            )}

            {/* OBSERVACIONES */}
            <div className="md:col-span-2">
              <label className="text-sm font-medium">
                Observaciones
              </label>

              <textarea
                value={data.observaciones}
                onChange={(event) =>
                  setData(
                    "observaciones",
                    event.target.value
                  )
                }
                rows={3}
                className="w-full border rounded-lg p-2 mt-1 text-sm resize-none"
              />

              {errors.observaciones && (
                <p className="text-xs text-red-500 mt-1">
                  {errors.observaciones}
                </p>
              )}
            </div>
          </div>

          {usaPajillas &&
            donadorSeleccionado &&
            data.hembra_ids.length > 0 && (
              <div className="rounded-lg border bg-gray-50 p-3">
                <p className="text-sm font-medium">
                  Asignación automática
                </p>

                <p className="text-xs text-gray-600 mt-1">
                  Se utilizarán {data.pajilla_ids.length} pajilla
                  {data.pajilla_ids.length === 1 ? "" : "s"} del
                  donador seleccionado para{" "}
                  {data.hembra_ids.length} hembra
                  {data.hembra_ids.length === 1 ? "" : "s"}.
                </p>
              </div>
            )}

          <div className="flex justify-end gap-2 pt-2">
            <button
              type="button"
              onClick={close}
              className="px-4 py-2 border rounded-lg text-sm"
            >
              Cancelar
            </button>

            <button
              type="submit"
              disabled={
                processing ||
                !proveedorSeleccionado ||
                data.hembra_ids.length === 0 ||
                (usaPajillas &&
                  data.pajilla_ids.length !==
                    data.hembra_ids.length)
              }
              className="px-4 py-2 bg-black text-white rounded-lg text-sm disabled:opacity-50"
            >
              {processing
                ? "Guardando..."
                : `Guardar ${
                    data.hembra_ids.length || ""
                  } servicio${
                    data.hembra_ids.length === 1 ? "" : "s"
                  }`}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}