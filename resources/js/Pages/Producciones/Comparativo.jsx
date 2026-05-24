import React, { useState } from "react";
import ShowProduccionModal from "./ShowProduccionModal";
import { Link } from "@inertiajs/react";

export default function Comparativo({ mejores = [] }) {
    const [selectedAnimal, setSelectedAnimal] = useState(null);
    const [showModal, setShowModal] = useState(false);
    const [animalProducciones, setAnimalProducciones] = useState([]);
    const [loading, setLoading] = useState(false);

    const cargarProducciones = async (animalId) => {
        setLoading(true);
        try {
            const response = await fetch(`/animales/${animalId}/producciones`);
            const data = await response.json();
            setAnimalProducciones(data);
        } catch (error) {
            console.error('Error cargando producciones:', error);
            setAnimalProducciones([]);
        } finally {
            setLoading(false);
        }
    };

    const handleVerFicha = async (animal) => {
        setSelectedAnimal(animal);
        await cargarProducciones(animal.id);
        setShowModal(true);
    };

    // ✅ Función para obtener el nombre del animal de forma segura
    const getAnimalNombre = (animal) => {
        return animal.nombre || animal.alias || animal.arete || 'Sin nombre';
    };

    // ✅ Función para obtener el valor/puntuación
    const getAnimalValor = (animal) => {
        return animal.valor || animal.puntuacion_total || 0;
    };

    // ✅ Función para obtener la diferencia/rendimiento
    const getAnimalDiferencia = (animal) => {
        return animal.diferencia || "+0%";
    };

    return (
        <div className="space-y-6">
            <div className="bg-white rounded-2xl shadow p-6">
                <h3 className="text-lg font-semibold">Mejores Productores</h3>
                <p className="text-sm text-gray-500 mb-3">Mejor rendimiento del hato</p>

                <div className="divide-y">
                    {mejores.length > 0 ? (
                        mejores.map((animal, index) => (
                            <div key={animal.id || index} className="flex items-center justify-between py-3">
                                <div className="flex-1">
                                    <div className="flex items-center gap-2">
                                        <span className="font-bold text-yellow-600">
                                            #{index + 1}
                                        </span>
                                        <div>
                                            <div className="flex items-center gap-2">
                                                <span className="font-medium">{getAnimalNombre(animal)}</span>
                                                {(animal.arete || animal.alias) && (
                                                    <span className="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">
                                                        {animal.arete || animal.alias}
                                                    </span>
                                                )}
                                            </div>
                                            <div className="flex items-center gap-2 mt-1">
                                                <span className="text-xs text-gray-500">{animal.especie || 'Sin especie'}</span>
                                                <span className="text-xs text-gray-400">•</span>
                                                <span className={`text-xs ${!animal.lote_nombre || animal.lote_nombre === 'Sin lote' ? 'text-orange-500' : 'text-blue-500'}`}>
                                                    {animal.lote_nombre || 'Sin lote'}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div className="text-right mr-4">
                                    <p className="font-medium">
                                        {typeof getAnimalValor(animal) === 'number' 
                                            ? getAnimalValor(animal).toLocaleString() 
                                            : getAnimalValor(animal)
                                        } pts
                                    </p>
                                    <p className="text-green-600 text-xs">
                                        {getAnimalDiferencia(animal)}
                                    </p>
                                </div>

                                <div className="flex gap-2">
                                    {animal.id && (
                                        <>
                                            <Link
                                                href={`/animales/${animal.id}`}
                                                className="px-3 py-1 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition text-sm"
                                            >
                                                Ir al animal
                                            </Link>
                                            <button
                                                onClick={() => handleVerFicha(animal)}
                                                className="px-3 py-1 bg-green-100 text-green-700 rounded-md hover:bg-green-200 transition text-sm"
                                            >
                                                Ver historial
                                            </button>
                                        </>
                                    )}
                                </div>
                            </div>
                        ))
                    ) : (
                        <div className="text-center py-8 text-gray-500">
                            No hay datos de mejores productores disponibles
                        </div>
                    )}
                </div>
            </div>

            {showModal && selectedAnimal && (
                <ShowProduccionModal
                    producciones={animalProducciones}
                    animal={selectedAnimal}
                    loading={loading}
                    onClose={() => {
                        setShowModal(false);
                        setSelectedAnimal(null);
                        setAnimalProducciones([]);
                    }}
                    onEdit={(prod) => {
                        console.log('Editar producción:', prod);
                    }}
                />
            )}
        </div>
    );
}