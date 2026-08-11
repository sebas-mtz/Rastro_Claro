import React from "react";
import { useForm } from "@inertiajs/react";
import { Skull, X } from "lucide-react";

export default function MuerteModal({ show, animal, onClose, onSuccess }) {
  const { data, setData, post, processing, errors, reset } = useForm({
    fecha: new Date().toISOString().split("T")[0],
    causa: "",
    observaciones: "",
  });

  if (!show || !animal) return null;

  const cerrar = () => {
    reset();
    onClose();
  };

  const enviar = (event) => {
    event.preventDefault();
    post(route("animales.muerte.store", animal.id), {
      preserveScroll: true,
      onSuccess: () => {
        reset();
        onSuccess?.();
        onClose();
      },
    });
  };

  return (
    <div className="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 p-4">
      <div className="w-full max-w-lg rounded-2xl bg-white shadow-xl">
        <div className="flex items-center justify-between border-b px-6 py-4">
          <div className="flex items-center gap-2">
            <Skull className="h-5 w-5 text-red-600" />
            <h2 className="text-lg font-bold">Registrar muerte</h2>
          </div>
          <button type="button" onClick={cerrar}><X size={20} /></button>
        </div>

        <form onSubmit={enviar} className="space-y-4 p-6">
          <p className="rounded-lg bg-red-50 p-3 text-sm text-red-700">
            Animal: <strong>{animal.alias || animal.arete || `#${animal.id}`}</strong>.
            Después de registrar la muerte sus datos quedarán bloqueados.
          </p>

          <div>
            <label className="text-sm font-medium">Fecha *</label>
            <input
              type="date"
              value={data.fecha}
              onChange={(e) => setData("fecha", e.target.value)}
              className="mt-1 w-full rounded-lg border p-2 text-sm"
            />
            {errors.fecha && <p className="mt-1 text-xs text-red-500">{errors.fecha}</p>}
          </div>

          <div>
            <label className="text-sm font-medium">Causa *</label>
            <input
              value={data.causa}
              onChange={(e) => setData("causa", e.target.value)}
              placeholder="Ej. enfermedad, accidente..."
              className="mt-1 w-full rounded-lg border p-2 text-sm"
            />
            {errors.causa && <p className="mt-1 text-xs text-red-500">{errors.causa}</p>}
          </div>

          <div>
            <label className="text-sm font-medium">Observaciones</label>
            <textarea
              value={data.observaciones}
              onChange={(e) => setData("observaciones", e.target.value)}
              rows={3}
              className="mt-1 w-full resize-none rounded-lg border p-2 text-sm"
            />
          </div>

          <div className="flex justify-end gap-2 pt-2">
            <button type="button" onClick={cerrar} className="rounded-lg border px-4 py-2 text-sm">
              Cancelar
            </button>
            <button
              type="submit"
              disabled={processing}
              className="rounded-lg bg-red-600 px-4 py-2 text-sm text-white disabled:opacity-50"
            >
              {processing ? "Guardando..." : "Registrar muerte"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
