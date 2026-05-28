import React, { useEffect } from "react";
import { useForm } from "@inertiajs/react";
import { X } from "lucide-react";

export default function ProduccionEditModal({ produccion, onClose }) {
  if (!produccion) return null;

  const { data, setData, put, processing, errors, reset } = useForm({
    animal_id: produccion.animal_id ?? "", // ✅ AÑADIR ESTE CAMPO
    fecha: produccion.fecha ?? "",
    tipo: produccion.tipo ?? "",
    valor: produccion.valor ?? "",
    unidad: produccion.unidad ?? "",
  });

  // Actualizar datos cuando cambia la producción
  useEffect(() => {
    setData({
      animal_id: produccion.animal_id ?? "", // ✅ AÑADIR ESTE CAMPO
      fecha: produccion.fecha ?? "",
      tipo: produccion.tipo ?? "",
      valor: produccion.valor ?? "",
      unidad: produccion.unidad ?? "",
    });
  }, [produccion, setData]);

  const submit = (e) => {
    e.preventDefault();

    put(route("producciones.update", produccion.id), {
      preserveScroll: true,
      onSuccess: () => {
        onClose();
      },
      onError: () => {
        // Puedes manejar errores aquí si es necesario
      },
    });
  };

  return (
    <div
      role="dialog"
      aria-modal="true"
      className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 p-4"
    >
      <div className="relative w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
        <button
          type="button"
          onClick={onClose}
          aria-label="Cerrar"
          className="absolute right-3 top-3 rounded-md p-1 text-gray-500 hover:text-gray-700"
        >
          <X className="w-5 h-5" />
        </button>

        <h3 className="mb-4 text-lg font-semibold text-gray-800">Editar Producción</h3>

        <form onSubmit={submit} className="space-y-4">
          {/* Campo oculto para animal_id */}
          <input 
            type="hidden" 
            value={data.animal_id} 
            onChange={(e) => setData("animal_id", e.target.value)}
          />

          {/* Fecha */}
          <div>
            <label htmlFor="fecha" className="mb-1 block text-sm font-medium text-gray-700">
              Fecha
            </label>
            <input
              id="fecha"
              type="date"
              value={data.fecha || ""}
              onChange={(e) => setData("fecha", e.target.value)}
              className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2
               focus:ring-green-500"
            />
            {errors.fecha && <p className="mt-1 text-xs text-red-500">{errors.fecha}</p>}
          </div>

          {/* Tipo */}
          <div>
            <label htmlFor="tipo" className="mb-1 block text-sm font-medium text-gray-700">
              Tipo
            </label>
            <select
              id="tipo"
              value={data.tipo || ""}
              onChange={(e) => setData("tipo", e.target.value)}
              className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2
               focus:ring-green-500"
            >
              <option value="">-- Seleccionar --</option>
              <option value="leche">Leche</option>
              <option value="lana">Lana</option>
              <option value="huevo">Huevo</option>
              <option value="carne">Carne</option>
              <option value="grasa">Grasa</option>
              <option value="peso">Peso</option>
              <option value="canal">Canal</option>
            </select>
            {errors.tipo && <p className="mt-1 text-xs text-red-500">{errors.tipo}</p>}
          </div>

          {/* Valor */}
          <div>
            <label htmlFor="valor" className="mb-1 block text-sm font-medium text-gray-700">
              Valor
            </label>
            <input
              id="valor"
              type="number"
              step="any"
              value={data.valor ?? ""}
              onChange={(e) => setData("valor", e.target.value)}
              className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2
               focus:ring-green-500"
            />
            {errors.valor && <p className="mt-1 text-xs text-red-500">{errors.valor}</p>}
          </div>

          {/* Unidad */}
          <div>
            <label htmlFor="unidad" className="mb-1 block text-sm font-medium text-gray-700">
              Unidad (opcional)
            </label>
            <input
              id="unidad"
              type="text"
              value={data.unidad ?? ""}
              onChange={(e) => setData("unidad", e.target.value)}
              className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2
               focus:ring-green-500"
            />
            {errors.unidad && <p className="mt-1 text-xs text-red-500">{errors.unidad}</p>}
          </div>

          {/* Buttons */}
          <div className="flex items-center gap-3 pt-2">
            <button
              type="button"
              onClick={onClose}
              className="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
              disabled={processing}
            >
              Cancelar
            </button>

            <button
              type="submit"
              disabled={processing}
              className="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60"
            >
              {processing ? "Guardando..." : "Guardar cambios"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}