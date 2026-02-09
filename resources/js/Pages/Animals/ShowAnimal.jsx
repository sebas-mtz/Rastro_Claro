import React, { useState } from "react";
import { Head, Link } from "@inertiajs/react";
import { ArrowLeft, PawPrint, Edit, PlusCircle, Eye, Camera } from "lucide-react";
import EditModal from "./Edit";
import ProduccionModal from "../Producciones/ProduccionModal";
import ShowProduccionModal from "../Producciones/ShowProduccionModal";
import ProduccionEditModal from "../Producciones/ProduccionEditModal";

export default function ShowAnimal({ animal, lotes, especies, razasPorEspecie, estadosProductivos }) {
    const [selectedAnimal, setSelectedAnimal] = useState(null); 
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [showAddProduccion, setShowAddProduccion] = useState(false);
    const [showProduccionList, setShowProduccionList] = useState(false);
    const [editProduccion, setEditProduccion] = useState(null);


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
        <div className="min-h-screen bg-gray-50 flex flex-col items-center py-10 px-4">
            <Head title={`Animal ${animal.arete}`} />

            <div className="w-full max-w-4xl bg-white shadow-xl rounded-2xl p-8 border border-gray-200">

                {/* Header */}
                <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-3">
                        <PawPrint className="text-green-600 w-7 h-7" />
                        <h1 className="text-2xl font-semibold text-gray-800">
                            {animal.alias || animal.especie}
                        </h1>
                    </div>
                    <a
                        href="/animales"
                        className="flex items-center text-sm text-green-700 hover:text-green-800 transition"
                    >
                        <ArrowLeft className="w-4 h-4 mr-1" /> Volver
                    </a>
                </div>

                {/* Datos generales */}
                <div className="flex justify-between items-center mb-4">
                    <h2 className="text-lg font-semibold text-gray-700">Datos Generales</h2>

                    <div className="flex gap-2">
                        <button
                            onClick={() => setShowProduccionList(true)}
                            className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition"
                        >
                            <Eye className="w-4 h-4" /> Ver Producción Diaria
                        </button>

                        <button
                            onClick={() => { setSelectedAnimal(animal); setIsEditOpen(true); }}
                            className="flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition"
                        >
                            <Edit className="w-4 h-4" /> Editar Datos
                        </button>
                    </div>
                </div>

                <div className="grid md:grid-cols-2 gap-8">
                    {/* Círculo con icono de agregar imagen */}
                    <div className="flex justify-center items-center">
                        <div className="relative">
                            <div className="w-48 h-48 rounded-full border-2 border-dashed border-gray-300 bg-gray-50 flex flex-col items-center justify-center hover:bg-gray-100 transition cursor-pointer">
                                <Camera className="w-12 h-12 text-gray-400 mb-2" />
                                <span className="text-sm text-gray-500 text-center px-4">
                                    Agregar imagen
                                </span>
                            </div>
                            {/* Indicador de especie (opcional) */}
                            <div className="absolute -bottom-2 left-1/2 transform -translate-x-1/2 bg-green-600 text-white px-3 py-1 rounded-full text-xs font-medium">
                                {animal.especie}
                            </div>
                        </div>
                    </div>

                    <div className="space-y-3">
                        <Data label="Especie" value={animal.especie} />
                        <Data label="Raza" value={animal.raza || "N/D"} />
                        <Data label="Sexo" value={animal.sexo === "M" ? "Macho" : "Hembra"} />
                        <Data label="Arete" value={animal.arete} />
                        <Data label="Estado Productivo" value={animal.estado_productivo || "N/D"} />
                        <Data label="Fecha de Nacimiento" value={animal.fecha_nac ? new Date(animal.fecha_nac).toLocaleDateString() : "N/D"} />
                        <Data label="Edad" value={calcularEdad(animal.fecha_nac)} />
                        <Data label="Peso" value={animal.peso ? `${animal.peso} kg` : "N/D"} />
                        <Data label="BCS" value={animal.BCS || "N/D"} />
                        <Data label="Lote" value={animal.lote?.nombre || "Sin lote"} />
                        <Data label="Fecha de Registro" value={new Date(animal.created_at).toLocaleDateString()} />
                    </div>
                </div>

                {/* Producción diaria compacta */}
                <div className="mt-10">
                    <div className="flex justify-between items-center mb-3">
                        <h2 className="text-lg font-semibold text-gray-700">Últimos Registros</h2>

                        <button
                            onClick={() => setShowAddProduccion(true)}
                            className="flex items-center gap-2 px-3 py-2 bg-green-500 text-white text-sm rounded-lg
                             hover:bg-green-600 transition"
                        >
                            <PlusCircle className="w-4 h-4" /> Agregar Registro
                        </button>
                    </div>

                    {animal.producciones && animal.producciones.length > 0 ? (
                        <table className="w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
                            <thead className="bg-gray-100 text-gray-700">
                                <tr>
                                    <th className="p-2">Fecha</th>
                                    <th className="p-2">Tipo</th>
                                    <th className="p-2">Valor</th>
                                    <th className="p-2">Unidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                {animal.producciones.slice(0, 3).map((p) => (
                                    <tr key={p.id} className="border-t text-center hover:bg-gray-50">
                                        <td className="p-2">{new Date(p.fecha).toLocaleDateString()}</td>
                                        <td className="p-2 capitalize">{p.tipo}</td>
                                        <td className="p-2">{p.valor ?? "N/D"}</td>
                                        <td className="p-2">{p.unidad ?? "N/D"}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    ) : (
                        <p className="text-gray-500 text-sm">No hay registros de producción.</p>
                    )}
                </div>
            </div>

            {/* MODALES */}
            {isEditOpen && selectedAnimal && (
             <EditModal
                animal={selectedAnimal}
                lotes={lotes}
                especies={especies}
                razasPorEspecie={razasPorEspecie}
                estadosProductivos={estadosProductivos}
                onClose={() => setIsEditOpen(false)}
                />
            )}

            {showAddProduccion && (
                <ProduccionModal
                    show={showAddProduccion}
                    onClose={() => setShowAddProduccion(false)}
                    animal={animal}
                />
            )}

            {showProduccionList && (
                <ShowProduccionModal
                    producciones={animal.producciones}
                    onClose={() => setShowProduccionList(false)}
                    onEdit={(prod) => {
                        setEditProduccion(prod);
                        setShowProduccionList(false);
                    }}
                />
            )}

            {editProduccion && (
                <ProduccionEditModal
                    produccion={editProduccion}
                    onClose={() => setEditProduccion(null)}
                />
            )}
        </div>
    );
}

function Data({ label, value }) {
    return (
        <div className="flex justify-between border-b border-gray-100 py-1">
            <span className="text-gray-600 font-medium">{label}</span>
            <span className="text-gray-800">{value}</span>
        </div>
    );
}