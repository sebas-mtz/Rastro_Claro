import React from "react";
import { X, Eye } from "lucide-react";

export default function ShowLoteModal({ lote, onClose }) {
  if (!lote) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50">
      <div className="bg-white rounded-2xl shadow-lg max-w-3xl w-full max-h-[90vh] overflow-hidden mx-4">
        {/* Header */}
        <div className="flex justify-between items-center bg-green-50 px-6 py-4 border-b border-green-100">
          <h2 className="text-xl font-bold text-gray-800">Detalles del Lote</h2>
          <button onClick={onClose} className="text-gray-500 hover:text-gray-700">
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Contenido scrollable */}
        <div className="p-6 overflow-y-auto max-h-[calc(90vh-140px)] space-y-4 text-gray-700">
          <InfoItem label="Nombre" value={lote.nombre} />
          <InfoItem label="Descripción" value={lote.descripcion || "N/D"} />
          <InfoItem label="Corral/Potrero" value={lote.corral_potrero || "N/D"} />
          <InfoItem label="Responsable" value={lote.responsable?.name || "Sin responsable"} />
          <InfoItem
            label="Fecha de Registro"
            value={new Date(lote.created_at).toLocaleDateString()}
          />

          <div>
            <dt className="text-sm font-medium text-gray-500">Animales Asociados</dt>
            {lote.animales && lote.animales.length > 0 ? (
              <div className="mt-2 max-h-64 overflow-y-auto border rounded-lg p-2">
                <table className="w-full text-sm text-left text-gray-700">
                  <thead className="bg-gray-100 sticky top-0">
                    <tr>
                      <th className="px-2 py-1">Arete</th>
                      <th className="px-2 py-1">Especie</th>
                      <th className="px-2 py-1">Raza</th>
                      <th className="px-2 py-1">Sexo</th>
                      <th className="px-2 py-1">Estado Productivo</th>
                      <th className="px-2 py-1 text-center">Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    {lote.animales.map((a) => (
                      <tr key={a.id} className="border-b hover:bg-gray-50">
                        <td className="px-2 py-1 font-medium">{a.arete}</td>
                        <td className="px-2 py-1">{a.especie}</td>
                        <td className="px-2 py-1">{a.raza || "N/D"}</td>
                        <td className="px-2 py-1">
                          <span
                            className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${
                              a.sexo === "M"
                                ? "bg-blue-100 text-blue-800"
                                : "bg-pink-100 text-pink-800"
                            }`}
                          >
                            {a.sexo === "M" ? "Macho" : "Hembra"}
                          </span>
                        </td>
                        <td className="px-2 py-1">
                          {a.estado_productivo ? (
                            <span className="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800">
                              {a.estado_productivo}
                            </span>
                          ) : (
                            "N/D"
                          )}
                        </td>
                        <td className="px-2 py-1 text-center">
                          <a
                            href={route("animales.show", a.id)} // Usa la ruta de tu controlador
                            className="inline-flex items-center gap-1 bg-green-600 text-white px-3 py-1 rounded-lg hover:bg-green-700 transition text-xs"
                          >
                            <Eye className="w-4 h-4" />
                            Ver
                          </a>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                <div className="mt-2 text-xs text-gray-500 text-center">
                  Total: {lote.animales.length} animales
                </div>
              </div>
            ) : (
              <p className="mt-1 text-sm text-gray-500">
                No hay animales asociados.
              </p>
            )}
          </div>
        </div>

        {/* Footer */}
        <div className="px-6 py-4 border-t border-gray-200 flex justify-end">
          <button
            onClick={onClose}
            className="bg-gray-100 text-gray-700 font-medium px-4 py-2 rounded-lg hover:bg-gray-200 transition"
          >
            Cerrar
          </button>
        </div>
      </div>
    </div>
  );
}

// Componente reutilizable para label + valor
function InfoItem({ label, value }) {
  return (
    <div>
      <dt className="text-sm font-medium text-gray-500">{label}</dt>
      <dd className="mt-1 text-sm text-gray-900">{value}</dd>
    </div>
  );
}