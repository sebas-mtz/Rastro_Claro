import React, { useState } from "react";
import { useForm } from "@inertiajs/react";
import { X, Plus, Trash2 } from "lucide-react";

export default function PartoModal({ show, onClose, hembras = [], eventos = [], animales = [] }) {

  const { data, setData, post, processing, errors, reset } = useForm({
    hembra_id:              "",
    servicio_evento_id:     "",
    padre_id:               "", // ← nuevo — solo si no hay servicio vinculado
    fecha:                  new Date().toISOString().split("T")[0],
    tipo_parto:             "normal",
    asistencia_requerida:   false,
    complicaciones:         false,
    detalle_complicaciones: "",
    costo:                  "",
    observaciones:          "",
    crias: [
      { sexo: "macho", peso_nacimiento: "", condicion: "vivo", arete: "", arete_temporal: "" }
    ],
  });

  // Gestaciones activas de la hembra seleccionada
  const gestacionesActivas = eventos.filter(e =>
    e.tipo_evento === "diagnostico" &&
    e.hembra_id == data.hembra_id &&
    e.diagnostico?.resultado === "positivo" &&
    !eventos.some(p =>
      p.tipo_evento === "parto" &&
      p.hembra_id == data.hembra_id &&
      new Date(p.fecha) > new Date(e.fecha)
    )
  );

  // Machos disponibles para asignar como padre manual
  const machos = animales.filter(a => a.sexo === "macho");

  // Si hay servicio vinculado, el padre viene del servicio — no mostrar selector
  const servicioSeleccionado = gestacionesActivas.find(g => g.id == data.servicio_evento_id);
  const padreDesdeServicio = servicioSeleccionado?.servicio?.macho ?? null;
  const mostrarSelectorPadre = !data.servicio_evento_id; // ← mostrar solo si no hay servicio

  const agregarCria = () => {
    setData("crias", [
      ...data.crias,
      { sexo: "macho", peso_nacimiento: "", condicion: "vivo", arete: "", arete_temporal: "" }
    ]);
  };

  const actualizarCria = (index, campo, valor) => {
    const nuevas = [...data.crias];
    nuevas[index] = { ...nuevas[index], [campo]: valor };
    setData("crias", nuevas);
  };

  const eliminarCria = (index) => {
    setData("crias", data.crias.filter((_, i) => i !== index));
  };

  const handleServicioChange = (e) => {
    const val = e.target.value;
    setData(data => ({
      ...data,
      servicio_evento_id: val,
      padre_id: "", // limpiar padre manual si vincula servicio
    }));
  };

  const close = () => { reset(); onClose(); };

  const handleSubmit = (e) => {
    e.preventDefault();
    post(route("reproduccion.partos.store"), {
      forceFormData: true,
      onSuccess: close,
    });
  };

  if (!show) return null;

  return (
    <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
      <div className="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

        <div className="flex justify-between items-center px-6 py-4 border-b sticky top-0 bg-white">
          <h3 className="font-bold text-lg">Registrar parto</h3>
          <button onClick={close}><X size={20} /></button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-5">

          {/* HEMBRA */}
          <div>
            <label className="text-sm font-medium">Hembra *</label>
            <select
              value={data.hembra_id}
              onChange={e => setData("hembra_id", e.target.value)}
              className="w-full border rounded-lg p-2 mt-1 text-sm"
            >
              <option value="">Seleccionar hembra...</option>
              {hembras.map(h => (
                <option key={h.id} value={h.id}>
                  {h.alias} ({h.arete})
                </option>
              ))}
            </select>
            {errors.hembra_id && <p className="text-red-500 text-xs mt-1">{errors.hembra_id}</p>}
          </div>

          {/* GESTACIÓN VINCULADA */}
          {data.hembra_id && (
            <div>
              <label className="text-sm font-medium">Gestación que originó el parto</label>
              {gestacionesActivas.length > 0 ? (
                <>
                  <select
                    value={data.servicio_evento_id}
                    onChange={handleServicioChange}
                    className="w-full border rounded-lg p-2 mt-1 text-sm"
                  >
                    <option value="">Sin vincular (parto sin servicio registrado)</option>
                    {gestacionesActivas.map(g => (
                      <option key={g.id} value={g.id}>
                        Diagnóstico {g.fecha} — Parto probable: {g.diagnostico?.fecha_probable_parto ?? "—"}
                      </option>
                    ))}
                  </select>

                  {/* Mostrar qué padre trae el servicio seleccionado */}
                  {padreDesdeServicio && (
                    <p className="text-xs text-green-700 bg-green-50 rounded-lg px-3 py-2 mt-2">
                      Padre asignado desde la monta:{" "}
                      <span className="font-semibold">
                        {padreDesdeServicio.alias ?? padreDesdeServicio.arete}
                      </span>
                    </p>
                  )}
                </>
              ) : (
                <p className="text-xs text-gray-400 mt-1">
                  No hay gestaciones activas registradas para esta hembra.
                </p>
              )}
            </div>
          )}

          {/* PADRE MANUAL — solo si no hay servicio vinculado */}
          {data.hembra_id && mostrarSelectorPadre && (
            <div>
              <label className="text-sm font-medium">Padre (opcional)</label>
              <select
                value={data.padre_id}
                onChange={e => setData("padre_id", e.target.value)}
                className="w-full border rounded-lg p-2 mt-1 text-sm"
              >
                <option value="">Desconocido / no registrado</option>
                {machos.map(m => (
                  <option key={m.id} value={m.id}>
                    {m.alias ? `${m.alias} (${m.arete})` : m.arete} — {m.especie}
                  </option>
                ))}
              </select>
              <p className="text-xs text-gray-400 mt-1">
                Si registras el servicio/monta previo, el padre se asigna automáticamente.
              </p>
            </div>
          )}

          <div className="grid grid-cols-2 gap-4">

            {/* FECHA */}
            <div>
              <label className="text-sm font-medium">Fecha del parto *</label>
              <input
                type="date"
                value={data.fecha}
                onChange={e => setData("fecha", e.target.value)}
                className="w-full border rounded-lg p-2 mt-1 text-sm"
              />
              {errors.fecha && <p className="text-red-500 text-xs mt-1">{errors.fecha}</p>}
            </div>

            {/* TIPO */}
            <div>
              <label className="text-sm font-medium">Tipo de parto *</label>
              <select
                value={data.tipo_parto}
                onChange={e => setData("tipo_parto", e.target.value)}
                className="w-full border rounded-lg p-2 mt-1 text-sm"
              >
                <option value="normal">Normal</option>
                <option value="distocico">Distócico</option>
                <option value="cesarea">Cesárea</option>
              </select>
            </div>

            {/* CHECKBOXES */}
            <div className="col-span-2 flex gap-6">
              <label className="flex items-center gap-2 text-sm cursor-pointer">
                <input
                  type="checkbox"
                  checked={data.asistencia_requerida}
                  onChange={e => setData("asistencia_requerida", e.target.checked)}
                />
                Requirió asistencia
              </label>
              <label className="flex items-center gap-2 text-sm cursor-pointer">
                <input
                  type="checkbox"
                  checked={data.complicaciones}
                  onChange={e => setData("complicaciones", e.target.checked)}
                />
                Hubo complicaciones
              </label>
            </div>

            {data.complicaciones && (
              <div className="col-span-2">
                <label className="text-sm font-medium">Detalle de complicaciones</label>
                <textarea
                  value={data.detalle_complicaciones}
                  onChange={e => setData("detalle_complicaciones", e.target.value)}
                  rows={2}
                  className="w-full border rounded-lg p-2 mt-1 text-sm resize-none"
                />
              </div>
            )}

            {/* COSTO */}
            <div>
              <label className="text-sm font-medium">Costo</label>
              <input
                type="number"
                value={data.costo}
                onChange={e => setData("costo", e.target.value)}
                placeholder="0.00"
                className="w-full border rounded-lg p-2 mt-1 text-sm"
              />
            </div>
          </div>

          {/* CRÍAS */}
          <div>
            <div className="flex justify-between items-center mb-2">
              <label className="text-sm font-semibold">Crías *</label>
              <button
                type="button"
                onClick={agregarCria}
                className="flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800"
              >
                <Plus size={13} /> Agregar cría
              </button>
            </div>

            <div className="space-y-3">
              {data.crias.map((cria, i) => (
                <div key={i} className="border rounded-xl p-4 space-y-3">
                  <div className="flex justify-between items-center">
                    <span className="text-sm font-medium text-gray-600">Cría {i + 1}</span>
                    {data.crias.length > 1 && (
                      <button type="button" onClick={() => eliminarCria(i)}>
                        <Trash2 size={15} className="text-red-400 hover:text-red-600" />
                      </button>
                    )}
                  </div>

                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <label className="text-xs font-medium">Sexo *</label>
                      <select
                        value={cria.sexo}
                        onChange={e => actualizarCria(i, "sexo", e.target.value)}
                        className="w-full border rounded-lg p-2 mt-1 text-sm"
                      >
                        <option value="macho">Macho</option>
                        <option value="hembra">Hembra</option>
                      </select>
                    </div>

                    <div>
                      <label className="text-xs font-medium">Condición *</label>
                      <select
                        value={cria.condicion}
                        onChange={e => actualizarCria(i, "condicion", e.target.value)}
                        className="w-full border rounded-lg p-2 mt-1 text-sm"
                      >
                        <option value="vivo">Vivo</option>
                        <option value="nacido_muerto">Nacido muerto</option>
                        <option value="murio_al_nacer">Murió al nacer</option>
                      </select>
                    </div>

                    <div>
                      <label className="text-xs font-medium">Peso al nacer (kg)</label>
                      <input
                        type="number"
                        value={cria.peso_nacimiento}
                        onChange={e => actualizarCria(i, "peso_nacimiento", e.target.value)}
                        placeholder="Ej: 32"
                        step="0.1"
                        className="w-full border rounded-lg p-2 mt-1 text-sm"
                      />
                    </div>

                    {cria.condicion === "vivo" && (
                      <div>
                        <label className="text-xs font-medium">Arete</label>
                        <input
                          value={cria.arete}
                          onChange={e => actualizarCria(i, "arete", e.target.value)}
                          placeholder="Arete definitivo"
                          className="w-full border rounded-lg p-2 mt-1 text-sm"
                        />
                      </div>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* OBSERVACIONES */}
          <div>
            <label className="text-sm font-medium">Observaciones</label>
            <textarea
              value={data.observaciones}
              onChange={e => setData("observaciones", e.target.value)}
              rows={2}
              className="w-full border rounded-lg p-2 mt-1 text-sm resize-none"
            />
          </div>

          <div className="flex justify-end gap-2 pt-2">
            <button type="button" onClick={close} className="px-4 py-2 border rounded-lg text-sm">
              Cancelar
            </button>
            <button
              type="submit"
              disabled={processing}
              className="px-4 py-2 bg-black text-white rounded-lg text-sm disabled:opacity-50"
            >
              {processing ? "Guardando..." : "Registrar parto"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}