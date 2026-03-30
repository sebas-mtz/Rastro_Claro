import React, { useMemo } from "react";
import { useForm } from "@inertiajs/react";
import { X } from "lucide-react";

export default function DiagnosticoModal({ show, onClose, hembras = [], eventos = [] }) {

  const { data, setData, post, processing, errors, reset } = useForm({
    hembra_id:                "",
    service_event_id:         "",
    fecha:                    new Date().toISOString().split("T")[0],
    metodo:                   "ultrasonido",
    resultado:                "positivo",
    dias_gestacion_estimados: "",
    veterinario_externo:      "",
    costo:                    "",
    observaciones:            "",
  });

  // Servicios sin diagnóstico de la hembra seleccionada
  const serviciosSinDiagnostico = useMemo(() => {
    if (!data.hembra_id) return [];

    const servicios = eventos.filter(e =>
      e.tipo_evento === "servicio" && e.hembra_id == data.hembra_id
    );

    return servicios.filter(s => {
      return !eventos.some(e =>
        e.tipo_evento === "diagnostico" &&
        e.hembra_id == data.hembra_id &&
        new Date(e.fecha) > new Date(s.fecha)
      );
    });
  }, [data.hembra_id, eventos]);

  const close = () => { reset(); onClose(); };

  const handleSubmit = (e) => {
    e.preventDefault();
    post(route("reproduccion.diagnosticos.store"), { onSuccess: close });
  };

  if (!show) return null;

  return (
    <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
      <div className="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">

        <div className="flex justify-between items-center px-6 py-4 border-b sticky top-0 bg-white">
          <h3 className="font-bold text-lg">Diagnóstico de gestación</h3>
          <button onClick={close}><X size={20} /></button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-4">

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
          </div>

          {/* SERVICIO VINCULADO */}
          {serviciosSinDiagnostico.length > 0 && (
            <div>
              <label className="text-sm font-medium">Servicio que se diagnostica</label>
              <select
                value={data.service_event_id}
                onChange={e => setData("service_event_id", e.target.value)}
                className="w-full border rounded-lg p-2 mt-1 text-sm"
              >
                <option value="">Sin vincular</option>
                {serviciosSinDiagnostico.map(s => (
                  <option key={s.id} value={s.id}>
                    {s.fecha} — {s.servicio?.descripcion || s.servicio?.tipo_servicio}
                  </option>
                ))}
              </select>
            </div>
          )}

          <div className="grid grid-cols-2 gap-4">

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

            {/* MÉTODO */}
            <div>
              <label className="text-sm font-medium">Método *</label>
              <select
                value={data.metodo}
                onChange={e => setData("metodo", e.target.value)}
                className="w-full border rounded-lg p-2 mt-1 text-sm"
              >
                <option value="ultrasonido">Ultrasonido</option>
                <option value="tacto_rectal">Tacto rectal</option>
                <option value="laboratorio">Laboratorio</option>
              </select>
            </div>

            {/* RESULTADO */}
            <div>
              <label className="text-sm font-medium">Resultado *</label>
              <select
                value={data.resultado}
                onChange={e => setData("resultado", e.target.value)}
                className="w-full border rounded-lg p-2 mt-1 text-sm"
              >
                <option value="positivo">Positivo — Gestante</option>
                <option value="negativo">Negativo — Vacía</option>
                <option value="repetir">Repetir diagnóstico</option>
              </select>
            </div>

            {/* DÍAS DE GESTACIÓN (solo ultrasonido positivo) */}
            {data.metodo === "ultrasonido" && data.resultado === "positivo" && (
              <div>
                <label className="text-sm font-medium">Días de gestación estimados</label>
                <input
                  type="number"
                  value={data.dias_gestacion_estimados}
                  onChange={e => setData("dias_gestacion_estimados", e.target.value)}
                  placeholder="Ej: 45"
                  min={1} max={283}
                  className="w-full border rounded-lg p-2 mt-1 text-sm"
                />
              </div>
            )}

            {/* VETERINARIO */}
            <div>
              <label className="text-sm font-medium">Veterinario</label>
              <input
                value={data.veterinario_externo}
                onChange={e => setData("veterinario_externo", e.target.value)}
                placeholder="Nombre del veterinario"
                className="w-full border rounded-lg p-2 mt-1 text-sm"
              />
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
              {processing ? "Guardando..." : "Guardar diagnóstico"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}