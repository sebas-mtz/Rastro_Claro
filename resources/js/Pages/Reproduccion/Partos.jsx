import React from "react";
import { Baby } from "lucide-react";

export default function Partos({ animales = [], reproducciones, onNuevoParto }) {
  return (
    <div className="bg-white rounded-xl border border-gray-200">
      <div className="p-5 border-b">
        <h2 className="text-lg font-semibold text-gray-900">Partos</h2>
        <p className="text-sm text-gray-500">Registro de nacimientos y complicaciones</p>
      </div>

      <div className="p-5 space-y-4">
        <div className="text-sm text-gray-600">
          Aquí mostrarás:
          <ul className="list-disc ml-5 mt-2">
            <li>Partos del mes</li>
            <li>Próximos partos</li>
            <li>Complicaciones / alertas</li>
          </ul>
        </div>

        <button
          type="button"
          onClick={onNuevoParto}
          className="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-lg bg-gray-900 text-white hover:bg-black"
        >
          <Baby className="w-4 h-4" />
          Registrar Parto
        </button>
      </div>
    </div>
  );
}
