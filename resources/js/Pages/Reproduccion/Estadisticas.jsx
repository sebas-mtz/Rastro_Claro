import React from "react";
import { BarChart3 } from "lucide-react";

export default function Estadisticas({ animales = [], lotes = [], reproducciones }) {
  return (
    <div className="bg-white rounded-xl border border-gray-200">
      <div className="p-5 border-b">
        <h2 className="text-lg font-semibold text-gray-900">Estadísticas</h2>
        <p className="text-sm text-gray-500">Indicadores reproductivos por especie/lote</p>
      </div>

      <div className="p-5 space-y-4">
        <div className="text-sm text-gray-600">
          Aquí pondrás gráficas/indicadores:
          <ul className="list-disc ml-5 mt-2">
            <li>Tasa de fertilidad</li>
            <li>% fallidos vs confirmados</li>
            <li>Días promedio entre partos</li>
          </ul>
        </div>

        <div className="w-full h-48 rounded-lg border border-dashed border-gray-300 flex items-center justify-center text-sm text-gray-500">
          Gráficas aquí
        </div>
      </div>
    </div>
  );
}
