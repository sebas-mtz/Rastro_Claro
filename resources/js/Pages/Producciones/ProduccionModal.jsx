import React, { useState, useEffect } from "react";
import { useForm } from "@inertiajs/react";
import { X } from "lucide-react";

export default function ProduccionModal({ show, onClose, animal }) {
    const { data, setData, post, processing, reset, errors } = useForm({
        animal_id: animal.id,
        fecha: "",
        tipo: "",
        valor: "",
        unidad: "",
    });

    const unidadesPorTipo = {
        leche: "litros",
        lana: "kg",
        huevo: "unidades",
        carne: "kg",
        grasa: "kg",
        peso: "kg",
        canal: "kg",
    };

    useEffect(() => {
        if (data.tipo) {
            setData("unidad", unidadesPorTipo[data.tipo] || "");
        }
    }, [data.tipo]);

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route("producciones.store"), {
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    if (!show) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50">
            <div className="bg-white rounded-xl shadow-lg w-full max-w-md p-6 relative">
                <button
                    onClick={onClose}
                    className="absolute top-3 right-3 text-gray-500 hover:text-gray-700"
                >
                    <X size={20} />
                </button>

                <h2 className="text-lg font-semibold text-gray-800 mb-4">
                    Registrar Producción de {animal.alias || animal.especie}
                </h2>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700">
                            Fecha
                        </label>
                        <input
                            type="date"
                            value={data.fecha}
                            onChange={(e) => setData("fecha", e.target.value)}
                            className="mt-1 w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 
                            focus:border-green-500"
                        />
                        {errors.fecha && <p className="text-red-500 text-sm">{errors.fecha}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">
                            Tipo de Producción
                        </label>
                        <select
                            value={data.tipo}
                            onChange={(e) => setData("tipo", e.target.value)}
                            className="mt-1 w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500"
                        >
                            <option value="">Seleccione tipo</option>
                            <option value="leche">Leche</option>
                            <option value="lana">Lana</option>
                            <option value="huevo">Huevo</option>
                            <option value="carne">Carne</option>
                            <option value="grasa">Grasa</option>
                            <option value="peso">Peso</option>
                            <option value="canal">Canal</option>
                        </select>
                        {errors.tipo && <p className="text-red-500 text-sm">{errors.tipo}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">
                            Valor
                        </label>
                        <input
                            type="number"
                            value={data.valor}
                            onChange={(e) => setData("valor", e.target.value)}
                            className="mt-1 w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500"
                            step="0.01"
                            placeholder="Ej: 12.5"
                        />
                        {errors.valor && <p className="text-red-500 text-sm">{errors.valor}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">
                            Unidad
                        </label>
                        <input
                            type="text"
                            value={data.unidad}
                            readOnly
                            className="mt-1 w-full bg-gray-100 border-gray-300 rounded-lg shadow-sm"
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition"
                    >
                        {processing ? "Guardando..." : "Guardar Registro"}
                    </button>
                </form>
            </div>
        </div>
    );
}