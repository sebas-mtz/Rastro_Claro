import React, { useState } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm, router } from "@inertiajs/react";
import { PlusCircle, Eye, Edit, Trash2, Search, DollarSign, Scissors, Drumstick, ScanLine, QrCode, IdCard } from "lucide-react";
import AnimalModal from "./AnimalModal";
import EditModal from "./Edit";
import ShowModal from "./Show";
import ScanIdentificadorModal from "./ScanIdentificadorModal";
import QrScannerModal from "./QrScannerModal";
import QrCodeModal from "./QrCodeModal";
import { Link } from "@inertiajs/react";
import AppLayout from "@/Layouts/AppLayout";


export default function Index({ 
  auth, 
  animales, 
  lotes = [], 
  especies = [], 
  razas = [], 
  estadosProductivos = {}, 
}) {
  const [showModal, setShowModal] = useState(false);
  const [busqueda, setBusqueda] = useState("");
  const [isEditOpen, setIsEditOpen] = useState(false);
  const [isShowOpen, setIsShowOpen] = useState(false);
  const [selectedAnimal, setSelectedAnimal] = useState(null);
  const [filtroEspecie, setFiltroEspecie] = useState("");
  const [isScanOpen, setIsScanOpen] = useState(false);
  const [isRegistrarOpen, setIsRegistrarOpen] = useState(false);
  const [isQrScannerOpen, setIsQrScannerOpen] = useState(false);
  const [qrAnimal, setQrAnimal] = useState(null);


  const { data, setData, post, reset, errors } = useForm({
    alias: "",
    arete: "",
    sexo: "",
    raza_id: "",
    raza_secundaria_id: "",
    fecha_nac: "",
    peso: "",
    BCS: "",
    estado_productivo: "",
    lote_id: "",
  });

  const handleSubmit = (e) => {
    e.preventDefault();

    if (!data.alias && !data.arete) {
      alert("Debe ingresar al menos Alias o Arete");
      return;
    }

    post(route("animales.store"), {
      onSuccess: () => {
        reset();
        setShowModal(false);
      },
    });
  };

  const handleDelete = (id) => {
    if (confirm("¿Seguro que deseas eliminar este ejemplar?")) {
      router.delete(route("animales.destroy", id));
    }
  };

  // Sistema exclusivamente ovino: los estados productivos son siempre los de Ovino.
  const estados = estadosProductivos?.Ovino ?? [];

  const calcularEdad = (fechaNac) => {
    if (!fechaNac) return "N/D";
    
    const nacimiento = new Date(fechaNac);
    const hoy = new Date();
    let años = hoy.getFullYear() - nacimiento.getFullYear();
    const mes = hoy.getMonth() - nacimiento.getMonth();
    
    if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
      años--;
    }
    
    return `${años} año${años !== 1 ? "s" : ""}`;
  };

  const animalesFiltrados = animales.filter((a) => {
    const coincideBusqueda =
      a.alias?.toLowerCase().includes(busqueda.toLowerCase()) ||
      a.arete?.toLowerCase().includes(busqueda.toLowerCase());
    const coincideRaza = filtroEspecie ? String(a.raza_id) === String(filtroEspecie) : true;
    return coincideBusqueda && coincideRaza;
  });

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800">Gestión del Rebaño</h2>}
    >
      <Head title="Gestión del Rebaño" />
      <div className="py-8 px-6 max-w-7xl mx-auto">
        {/* Header */}
        <div className="flex justify-between items-center mb-4">
          <h1 className="text-2xl font-bold text-gray-800">Ejemplares Registrados</h1>
          <div className="flex flex-wrap gap-2">
            <button
              onClick={() => setIsScanOpen(true)}
              className="border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
            >
              <ScanLine className="w-5 h-5" />
              Escanear microchip/RFID
            </button>
            <button
              onClick={() => setIsQrScannerOpen(true)}
              className="border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
            >
              <QrCode className="w-5 h-5" />
              Escanear QR
            </button>
            <button
              onClick={() => setIsRegistrarOpen(true)}
              className="border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
            >
              <IdCard className="w-5 h-5" />
              Registrar identificador
            </button>
            <button
              onClick={() => setShowModal(true)}
              className="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
            >
              <PlusCircle className="w-5 h-5" />
              Agregar Ejemplar
            </button>
          </div>
        </div>

        {/* 🔍 Barra de búsqueda y filtro */}
        <div className="flex flex-col sm:flex-row gap-3 mb-6">
          <div className="flex items-center bg-white border border-gray-300 rounded-lg px-3 py-2 w-full sm:w-2/3">
            <Search className="w-5 h-5 text-gray-500 mr-2" />
            <input
              type="text"
              placeholder="Buscar por alias o arete..."
              value={busqueda}
              onChange={(e) => setBusqueda(e.target.value)}
              className="w-full outline-none text-gray-700"
            />
          </div>
          <select
            value={filtroEspecie}
            onChange={(e) => setFiltroEspecie(e.target.value)}
            className="border border-gray-300 rounded-lg px-3 py-2 text-gray-700 w-full sm:w-1/3"
          >
            <option value="">Todas las razas</option>
            {razas.map((r) => (
              <option key={r.id} value={r.id}>{r.nombre}</option>
            ))}
          </select>
        </div>

        {/* 🐄 Lista de ejemplares */}
        <div className="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-4 gap-6">
          {animalesFiltrados.length === 0 ? (
            <div className="col-span-full text-center py-8">
              <p className="text-gray-500 text-lg">No hay ejemplares registrados aún.</p>
            </div>
          ) : (
            animalesFiltrados.map((animal) => (
              <div
                key={animal.id}
                className="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 hover:shadow-md transition"
              >
                <div className="flex justify-between items-start mb-2">
                  <div>
                    <h3 className="text-lg font-semibold text-gray-800">
                      {animal.raza_original || animal.raza || "Ovino"}
                    </h3>
                    <p className="text-sm text-gray-500">
                      {animal.alias && (
                        <span className="font-semibold">{animal.alias} • </span>
                      )}
                      Arete: <span className="font-semibold">{animal.arete}</span>
                    </p>
                  </div>
                  <span className="text-sm bg-green-100 text-green-700 font-medium px-3 py-1 rounded-full">
                    {animal.estado_productivo || "Desconocido"}
                  </span>
                </div>

                <div className="text-sm text-gray-700 space-y-1 mb-4">
                  <p><span className="font-semibold">Sexo:</span> {animal.sexo}</p>
                  <p><span className="font-semibold">Peso:</span> {animal.peso ? `${animal.peso} kg` : "N/D"}</p>
                  <p><span className="font-semibold">Edad:</span> {calcularEdad(animal.fecha_nac)}</p>
                </div>

                <div className="flex flex-wrap gap-2">
                  <button
                    onClick={() => { setSelectedAnimal(animal); setIsShowOpen(true); }}
                    className="flex items-center gap-1 bg-green-100 text-green-700 font-medium px-3 py-1.5 rounded-lg hover:bg-green-200 transition"
                  >
                    <Eye className="w-4 h-4" /> Ver ficha
                  </button>

                  <Link
                    href={route('valuaciones.show', animal.id)}
                    title="Ver precio estimado y desglose de costos"
                    className="flex items-center gap-1 bg-emerald-600 text-white font-medium px-3 py-1.5 rounded-lg hover:bg-emerald-700 transition"
                  >
                    <DollarSign className="w-4 h-4" /> Valuación
                  </Link>

                  <button
                    onClick={() => { setSelectedAnimal(animal); setIsEditOpen(true); }}
                    className="flex items-center gap-1 bg-blue-100 text-blue-700 font-medium px-3 py-1.5 rounded-lg hover:bg-blue-200 transition"
                  >
                    <Edit className="w-4 h-4" /> Editar
                  </button>

                  <button
                    onClick={() => setQrAnimal(animal)}
                    title="Generar QR"
                    className="flex items-center gap-1 bg-indigo-100 text-indigo-700 font-medium px-3 py-1.5 rounded-lg hover:bg-indigo-200 transition"
                  >
                    <QrCode className="w-4 h-4" />
                  </button>

                  <button
                    onClick={() => handleDelete(animal.id)}
                    className="flex items-center gap-1 bg-red-100 text-red-700 font-medium px-3 py-1.5 rounded-lg hover:bg-red-200 transition"
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                </div>
              </div>
            ))
          )}
        </div>
      </div>

      {/* Modal para agregar */}
      <AnimalModal
        show={showModal}
        onClose={() => setShowModal(false)}
        data={data}
        setData={setData}
        onSubmit={handleSubmit}
        razas={razas}
        estados={estados}
        lotes={lotes}
        especies={especies}
        errors={errors}
      />

      {/* Modal de ver ficha */}
      {isShowOpen && selectedAnimal && (
        <ShowModal
          animal={selectedAnimal}
          onClose={() => setIsShowOpen(false)}
          onEdit={(animal) => {
            setSelectedAnimal(animal);
            setIsShowOpen(false);
            setIsEditOpen(true);
          }}
        />
      )}

      {/* Modal de editar */}
      {isEditOpen && selectedAnimal && (
        <EditModal
          animal={selectedAnimal}
          lotes={lotes}
          especies={especies}
          razas={razas}
          estadosProductivos={estadosProductivos}
          onClose={() => setIsEditOpen(false)}
        />
      )}

      {/* Escanear microchip/RFID */}
      <ScanIdentificadorModal
        show={isScanOpen}
        onClose={() => setIsScanOpen(false)}
        animales={animales}
      />

      {/* Registrar identificador directamente */}
      <ScanIdentificadorModal
        show={isRegistrarOpen}
        onClose={() => setIsRegistrarOpen(false)}
        animales={animales}
        modoDirecto
      />

      {/* Escanear QR */}
      <QrScannerModal
        show={isQrScannerOpen}
        onClose={() => setIsQrScannerOpen(false)}
      />

      {/* Generar QR de un animal */}
      <QrCodeModal
        show={Boolean(qrAnimal)}
        onClose={() => setQrAnimal(null)}
        animal={qrAnimal}
      />
    </AuthenticatedLayout>
  );
}
//Index.layout = (page) => <AppLayout>{page}</AppLayout>;