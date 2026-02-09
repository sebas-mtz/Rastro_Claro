import React, { useState } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, router } from "@inertiajs/react";
import { PlusCircle, Eye, Edit, Trash2, Search } from "lucide-react";
import LoteModal from "./LoteModal";
import ShowLoteModal from "./ShowLoteModal";
import AppLayout from "@/Layouts/AppLayout";



const especies = ["Bovino","Porcino","Caprino","Ovino","Equino","Aves de corral (gallinas y pollitos)","Gallos"];
const razasPorEspecie = {
  Bovino: ["Holstein", "Angus", "Hereford", "Simmental", "Otra"],
  Porcino: ["Yorkshire", "Landrace", "Duroc", "Pietrain", "Otra"],
  Caprino: ["Saanen", "Boer", "Alpina", "Toggenburg", "Otra"],
  Ovino: ["Dorper", "Merino", "Suffolk", "Katahdin", "Otra"],
  Equino: ["Cuarto de Milla", "Pura Sangre", "Árabe", "Criollo", "Otra"],
  "Aves de corral (gallinas y pollitos)": ["Leghorn", "Rhode Island", "Plymouth Rock", "Otra"],
  Gallos : [ "Gallos de pelea (Asil)", "Gallos Kelso","Gallos Hatch", "Gallos Sweater", "Gallos Shamo", 
    "Gallos Cuban Brown", "Gallos Navajeros (LATAM)","Otra"],
};

const estadosProductivos = {
  Bovino: ["Vaca seca", "Lactante", "Gestante", "En crecimiento", "Reproductor"],
  Caprino: ["Gestante", "En crecimiento", "Lactante", "Reproductor"],
  Ovino: ["Gestante", "En crecimiento", "Reproductor"],
  Porcino: ["Gestante", "En crecimiento", "Reproductor"],
  Equino: ["En entrenamiento", "Reproductor", "En descanso"],
  "Aves de corral": ["Postura", "En descanso", "En crecimiento"],
  Gallos: ["Reproductor", "En crecimiento", "En descanso", "De pelea / exhibición", 
    "En entrenamiento"]
};

export default function Index({ auth, lotes, usuarios = [] }) {
    const [showModal, setShowModal] = useState(false);
    const [isShowOpen, setIsShowOpen] = useState(false);
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [selectedLote, setSelectedLote] = useState(null);
    const [busqueda, setBusqueda] = useState("");

    // Filtrado por búsqueda
    const lotesFiltrados = lotes.filter((lote) =>
        lote.nombre?.toLowerCase().includes(busqueda.toLowerCase()) ||
        lote.corral_potrero?.toLowerCase().includes(busqueda.toLowerCase())
    );

    const handleDelete = (id) => {
        if (confirm("¿Seguro que deseas eliminar este lote?")) {
            router.delete(route("lotes.destroy", id));
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800">Gestión de Lotes</h2>}
        >
            <Head title="Gestión de Lotes" />

            <div className="py-8 px-6 max-w-7xl mx-auto">
                {/* Header */}
                <div className="flex justify-between items-center mb-4">
                    <h1 className="text-2xl font-bold text-gray-800">Lotes Registrados</h1>
                    <button
                        onClick={() => { setSelectedLote(null); setShowModal(true); }}
                        className="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
                    >
                        <PlusCircle className="w-5 h-5" />
                        Agregar Lote
                    </button>
                </div>

                {/* 🔍 Barra de búsqueda */}
                <div className="flex flex-col sm:flex-row gap-3 mb-6">
                    <div className="flex items-center bg-white border border-gray-300 rounded-lg px-3 py-2 w-full sm:w-1/2">
                        <Search className="w-5 h-5 text-gray-500 mr-2" />
                        <input
                            type="text"
                            placeholder="Buscar por nombre o corral/potrero..."
                            value={busqueda}
                            onChange={(e) => setBusqueda(e.target.value)}
                            className="w-full outline-none text-gray-700"
                        />
                    </div>
                </div>

                {/* Lista de lotes */}
                <div className="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-2 gap-6">
                    {lotesFiltrados.length === 0 ? (
                        <p className="text-gray-500">No hay lotes registrados aún.</p>
                    ) : (
                        lotesFiltrados.map((lote) => (
                            <div
                                key={lote.id}
                                className="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 hover:shadow-md transition"
                            >
                                <div className="flex justify-between items-start mb-2">
                                    <div>
                                        <h3 className="text-lg font-semibold text-gray-800">{lote.nombre}</h3>
                                        <p className="text-sm text-gray-500">
                                            {lote.descripcion && <span className="font-semibold">{lote.descripcion} • </span>}
                                            Corral/Potrero: <span className="font-semibold">{lote.corral_potrero || "N/D"}</span>
                                        </p>
                                    </div>
                                    <span className="text-sm bg-green-100 text-green-700 font-medium px-3 py-1 rounded-full">
                                        {lote.responsable?.name || "Sin responsable"}
                                    </span>
                                </div>

                                <div className="flex justify-between mt-4">
                                    <button
                                        onClick={() => { setSelectedLote(lote); setIsShowOpen(true); }}
                                        className="flex items-center gap-1 bg-green-100 text-green-700 font-medium px-3 py-1.5 rounded-lg
                                         hover:bg-green-200 transition"
                                    >
                                        <Eye className="w-4 h-4" /> Ver
                                    </button>

                                    <button
                                        onClick={() => { setSelectedLote(lote); setIsEditOpen(true); }}
                                        className="flex items-center gap-1 bg-blue-100 text-blue-700 font-medium px-3 py-1.5 rounded-lg
                                         hover:bg-blue-200 transition"
                                    >
                                        <Edit className="w-4 h-4" /> Editar
                                    </button>

                                    <button
                                        onClick={() => handleDelete(lote.id)}
                                        className="flex items-center gap-1 bg-red-100 text-red-700 font-medium px-3 py-1.5 rounded-lg
                                         hover:bg-red-200 transition"
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        ))
                    )}
                </div>
            </div>

            {/* Modal agregar / editar - CON TODAS LAS PROPS NECESARIAS */}
            <LoteModal
                show={showModal || isEditOpen}
                onClose={() => { setShowModal(false); setIsEditOpen(false); }}
                lote={selectedLote || {}}
                usuarios={usuarios}
                especies={especies}
                razasPorEspecie={razasPorEspecie}
                estadosProductivos={estadosProductivos}
            />

            {/* Modal ver detalles */}
            {isShowOpen && selectedLote && (
                <ShowLoteModal
                    lote={selectedLote}
                    onClose={() => setIsShowOpen(false)}
                />
            )}
        </AuthenticatedLayout>
    );
}
//Index.layout = (page) => <AppLayout>{page}</AppLayout>;