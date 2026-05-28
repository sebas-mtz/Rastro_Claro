import React, { useEffect, useState } from "react";
import { useForm } from "@inertiajs/react";
import { X, Plus, Trash2 } from "lucide-react";

export default function ServicioModal({ show, onClose, hembras = [], machos = [], lotes = [] }) {

  const { data, setData, post, processing, errors, reset } = useForm({
    hembra_id:       "",
    lote_id:         "",
    fecha:           new Date().toISOString().split("T")[0],
    tipo_servicio:   "monta_natural",
    macho_id:        "",
    pajilla_codigo:  "",
    pajilla_raza:    "",
    pajilla_origen:  "",
    tecnico_externo: "",
    numero_servicio: 1,
    costo:           "",
    observaciones:   "",
  });

  const hembraSeleccionada = hembras.find(h => h.id === Number(data.hembra_id));

  const machosFiltrados = hembraSeleccionada
  ? machos.filter(m => m.especie === hembraSeleccionada.especie && m.sexo === 'macho')
  : machos;

console.log("Machos filtrados:", machosFiltrados);  // Lo que se va a renderizar
  const esMontaNatural = data.tipo_servicio === "monta_natural";
  const esIA = ["inseminacion_artificial", "iatf"].includes(data.tipo_servicio);

  useEffect(() => {
    if (hembraSeleccionada?.lote_id) {
      setData("lote_id", hembraSeleccionada.lote_id);
    }
  }, [data.hembra_id]);

  const close = () => { reset(); onClose(); };

  const handleSubmit = (e) => {
    e.preventDefault();
    post(route("reproduccion.servicios.store"), { onSuccess: close });
  };

  if (!show) return null;

  return (
    <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
      <div className="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

        <div className="flex justify-between items-center px-6 py-4 border-b sticky top-0 bg-white">
          <h3 className="font-bold text-lg">Registrar servicio</h3>
          <button onClick={close}><X size={20} /></button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-4">

          <div className="grid grid-cols-2 gap-4">

            {/* HEMBRA */}
            <div className="col-span-2">
              <label className="text-sm font-medium">Hembra *</label>
              <select
                 name="hembra_id"
                  value={data.hembra_id}
                  onChange={e => setData("hembra_id", Number(e.target.value))}
                   className="w-full border rounded-lg p-2 mt-1 text-sm"
                  >
                <option value="">Seleccionar hembra...</option>
                {hembras.map(h => (
                  <option key={h.id} value={h.id}>
                    {h.alias} ({h.arete}) — {h.lote_nombre || "Sin lote"}
                  </option>
                ))}
              </select>
              {errors.hembra_id && <p className="text-xs text-red-500 mt-1">{errors.hembra_id}</p>}
            </div>

            {/* FECHA */}
            <div>
              <label className="text-sm font-medium">Fecha *</label>
              <input
                type="date"
                value={data.fecha}
                onChange={e => setData("fecha", e.target.value)}
                className="w-full border rounded-lg p-2 mt-1 text-sm"
              />
            </div>

            {/* TIPO */}
            <div>
              <label className="text-sm font-medium">Tipo de servicio *</label>
              <select
                value={data.tipo_servicio}
                onChange={e => setData("tipo_servicio", e.target.value)}
                className="w-full border rounded-lg p-2 mt-1 text-sm"
              >
                <option value="monta_natural">Monta natural</option>
                <option value="inseminacion_artificial">Inseminación artificial</option>
                <option value="iatf">IATF</option>
              </select>
            </div>

            {/* MACHO (solo monta natural) */}
            {esMontaNatural && (
              <div className="col-span-2">
                <label className="text-sm font-medium">Macho *</label>
                <select
                  name="macho_id"
                  value={data.macho_id}
                  onChange={e => setData("macho_id", Number(e.target.value))}
                  className="w-full border rounded-lg p-2 mt-1 text-sm"
                >
                  <option value="">Seleccionar macho...</option>
                  {machosFiltrados.map(m => (
                    <option key={m.id} value={m.id}>
                      {m.alias} ({m.arete})
                    </option>
                  ))}
                </select>
                {errors.macho_id && <p className="text-xs text-red-500 mt-1">{errors.macho_id}</p>}
              </div>
            )}

            {/* PAJILLA (solo IA/IATF) */}
            {esIA && (
              <>
                <div>
                  <label className="text-sm font-medium">Código pajilla *</label>
                  <input
                    value={data.pajilla_codigo}
                    onChange={e => setData("pajilla_codigo", e.target.value)}
                    placeholder="Ej: ANG-4801"
                    className="w-full border rounded-lg p-2 mt-1 text-sm"
                  />
                  {errors.pajilla_codigo && <p className="text-xs text-red-500 mt-1">{errors.pajilla_codigo}</p>}
                </div>
                <div>
                  <label className="text-sm font-medium">Raza pajilla</label>
                  <input
                    value={data.pajilla_raza}
                    onChange={e => setData("pajilla_raza", e.target.value)}
                    placeholder="Ej: Angus"
                    className="w-full border rounded-lg p-2 mt-1 text-sm"
                  />
                </div>
                <div>
                  <label className="text-sm font-medium">Técnico</label>
                  <input
                    value={data.tecnico_externo}
                    onChange={e => setData("tecnico_externo", e.target.value)}
                    placeholder="Nombre del técnico"
                    className="w-full border rounded-lg p-2 mt-1 text-sm"
                  />
                </div>
              </>
            )}

            {/* NÚMERO DE SERVICIO */}
            <div>
              <label className="text-sm font-medium">Número de servicio</label>
              <select
                value={data.numero_servicio}
                onChange={e => setData("numero_servicio", parseInt(e.target.value))}
                className="w-full border rounded-lg p-2 mt-1 text-sm"
              >
                {[1,2,3,4,5].map(n => (
                  <option key={n} value={n}>{n}° servicio</option>
                ))}
              </select>
            </div>

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

            {/* OBSERVACIONES */}
            <div className="col-span-2">
              <label className="text-sm font-medium">Observaciones</label>
              <textarea
                value={data.observaciones}
                onChange={e => setData("observaciones", e.target.value)}
                rows={2}
                className="w-full border rounded-lg p-2 mt-1 text-sm resize-none"
              />
            </div>
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
              {processing ? "Guardando..." : "Guardar servicio"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}