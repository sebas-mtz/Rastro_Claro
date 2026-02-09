import React from "react";
import { Activity } from "lucide-react";

export default function Gestaciones({ animales = [], reproducciones, onNuevoDiagnostico }) {
  return (
    <div className="bg-white rounded-xl border border-gray-200">
      <div className="p-5 border-b">
        <h2 className="text-lg font-semibold text-gray-900">Gestaciones</h2>
        <p className="text-sm text-gray-500">Diagnósticos y seguimiento</p>
      </div>

      <div className="p-5 space-y-4">
        <div className="text-sm text-gray-600">
          Aquí mostrarás:
          <ul className="list-disc ml-5 mt-2">
            <li>Pendientes de diagnóstico</li>
            <li>Gestantes confirmadas</li>
            <li>Fechas probables de parto</li>
          </ul>
        </div>

        <button
          type="button"
          onClick={onNuevoDiagnostico}
          className="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-lg bg-gray-900 text-white hover:bg-black"
        >
          <Activity className="w-4 h-4" />
          Registrar Diagnóstico de Gestación
        </button>
      </div>
    </div>
  );
}
