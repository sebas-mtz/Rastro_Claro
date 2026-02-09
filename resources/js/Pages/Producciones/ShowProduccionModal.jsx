import { X, Pencil } from "lucide-react";

export default function ShowProduccionModal({ producciones = [], onClose, onEdit }) {
    return (
        <div className="fixed inset-0 z-50 bg-black bg-opacity-40 flex justify-center items-center">
            <div className="bg-white w-full max-w-3xl p-6 rounded-xl shadow-xl relative">
                
                <button
                    onClick={onClose}
                    className="absolute top-3 right-3 text-gray-500 hover:text-gray-700 transition"
                >
                    <X className="w-6 h-6" />
                </button>

                <h2 className="text-xl font-semibold text-gray-800 mb-4">
                    Producción Diaria
                </h2>

                <div className="overflow-y-auto max-h-[420px] border border-gray-200 rounded-lg">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-100">
                            <tr className="text-left text-gray-700">
                                <th className="p-2">Fecha</th>
                                <th className="p-2">Tipo</th>
                                <th className="p-2">Valor</th>
                                <th className="p-2">Unidad</th>
                                <th className="p-2 text-center">Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            {producciones.map((prod) => (
                                <tr key={prod.id} className="border-t hover:bg-gray-50 text-center">
                                    <td className="p-2">{new Date(prod.fecha).toLocaleDateString()}</td>
                                    <td className="p-2 capitalize">{prod.tipo}</td>
                                    <td className="p-2">{prod.valor ?? "N/D"}</td>
                                    <td className="p-2">{prod.unidad ?? "N/D"}</td>

                                    <td className="p-2">
                                        <button
                                            onClick={() => onEdit(prod)}
                                            className="px-3 py-1 bg-blue-600 text-white text-xs rounded-md 
                                            hover:bg-blue-700 flex items-center gap-1 mx-auto"
                                        >
                                            <Pencil className="w-4 h-4" />
                                            Editar
                                        </button>
                                    </td>
                                </tr>
                            ))}

                            {producciones.length === 0 && (
                                <tr>
                                    <td colSpan="5" className="p-4 text-center text-gray-500">
                                        No hay registros.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}