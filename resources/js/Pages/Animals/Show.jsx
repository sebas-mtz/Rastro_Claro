import React from "react";
import { Edit, X } from "lucide-react";

export default function ShowModal({ animal, onClose, onEdit }) {
    if (!animal) return null;

    function calcularEdad(fechaNac) {
        if (!fechaNac) return "N/D";
        const nacimiento = new Date(fechaNac);
        const hoy = new Date();
        let años = hoy.getFullYear() - nacimiento.getFullYear();
        const mes = hoy.getMonth() - nacimiento.getMonth();
        if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) años--;
        return `${años} año${años !== 1 ? "s" : ""}`;
    }

    return (
        <div className="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
            <div className="bg-white rounded-2xl shadow-xl w-full max-w-3xl relative border 
            border-gray-200 overflow-hidden animate-fadeIn">
                {/* Header */}
                <div className="flex justify-between items-center px-6 py-4 bg-green-50 border-b border-green-100">
                    <h2 className="text-xl font-semibold text-gray-800">
                        {animal.alias || animal.especie}
                    </h2>
                    <button
                        onClick={onClose}
                        className="text-gray-500 hover:text-gray-700 transition"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                {/* Cuerpo */}
                <div className="p-6">
                    <div className="flex justify-between items-center mb-6">
                        <p className="text-green-700 font-medium">
                            Arete: <span className="font-semibold">{animal.arete}</span>
                        </p>
                        <span className="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-medium">
                            {animal.estado_productivo || "Desconocido"}
                        </span>
                    </div>

                    <div className="grid md:grid-cols-2 gap-6">
                        {/* Columna 1 */}
                        <div className="space-y-4">
                            <InfoItem label="Especie" value={animal.especie} />
                            <InfoItem label="Raza" value={animal.raza || "N/D"} />
                            <InfoItem label="Sexo" value={animal.sexo === "M" ? "Macho" : "Hembra"} />
                            <InfoItem
                                label="Fecha de Nacimiento"
                                value={animal.fecha_nac ? new Date(animal.fecha_nac).toLocaleDateString() : "N/D"}
                            />
                            <InfoItem label="Edad" value={calcularEdad(animal.fecha_nac)} />
                        </div>

                        {/* Columna 2 */}
                        <div className="space-y-4">
                            <InfoItem label="Peso" value={animal.peso ? `${animal.peso} kg` : "N/D"} />
                            <InfoItem label="BCS" value={animal.BCS || "N/D"} />
                            <InfoItem label="Estado Productivo" value={animal.estado_productivo || "N/D"} />
                            <InfoItem label="Lote" value={animal.lote?.nombre || "Sin lote"} />
                            <InfoItem
                                label="Fecha de Registro"
                                value={new Date(animal.created_at).toLocaleDateString()}
                            />
                        </div>
                    </div>
                </div>

                {/* Footer */}
                <div className="flex justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-200">
                 <button
                    onClick={onClose}
                   className="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 transition"
                >
                Cerrar
                  </button>
                  <a
                     href={route('animales.show', animal.id)}
                     className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center gap-2"
                    >
                   <Edit className="w-4 h-4" />
                   Ver Animal
                </a>    
                    </div>
                    </div>
                </div>
            );
           }

            function InfoItem({ label, value }) {
                return (
                <div>
                    <dt className="text-sm font-medium text-gray-500">{label}</dt>
                    <dd className="mt-1 text-sm text-gray-900">{value}</dd>
              </div>
                 );
            } 