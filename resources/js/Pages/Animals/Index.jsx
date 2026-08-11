import React, { useState, useMemo } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm, router } from "@inertiajs/react";
import {
  PlusCircle,
  Eye,
  Edit,
  PawPrint,
  Search,
  ChevronDown,
  ChevronUp,
} from "lucide-react";
import AnimalModal from "./AnimalModal";
import EditModal from "./Edit";
import ShowModal from "./Show";

import AppLayout from "@/Layouts/AppLayout";

const LABELS_TIPO_PARTO = {
  normal: "Normal",
  distocico: "Distócico",
  cesarea: "Cesárea",
};

// Antes esta lista vivía hardcodeada aquí ("Muerto" con mayúscula) y no
// coincidía con el valor real que guarda el backend ('muerto', minúscula,
// definido en EstadoProductivoService::estadosSistema()). Por eso esos
// animales nunca caían en la sección de historial. Ahora se recibe como
// prop desde AnimalController::index(); este arreglo solo queda como
// respaldo por si algún día se renderiza la página sin esa prop.
const ESTADOS_SISTEMA_FALLBACK = ["Faeneado", "Vendido", "Sacrificado", "muerto"];

// ── Color de viñeta ──────────────────────────────────────────────────────
function colorViñeta(animal) {
  return "bg-gray-100 text-gray-700";
}

function AnimalCard({ animal, onShow, onEdit, compact = false }) {
  const esCordero = animal.estado_productivo === "Cordero";
  const colorSexo = esCordero
    ? "bg-purple-400"
    : animal.sexo === "M"
    ? "bg-blue-600"
    : "bg-pink-400";

  return (
    <div
      className={`bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition ${
        compact ? "p-2.5" : esCordero ? "px-5 py-2" : "p-5"
      }`}
    >
      <div className={`flex justify-between items-start ${compact ? "mb-1" : "mb-2"}`}>
        <div>
          <div className="flex items-center gap-1.5">
            <PawPrint className={`text-gray-700 ${compact ? "w-3.5 h-3.5" : "w-5 h-5"}`} />
            <h3 className={`font-semibold text-gray-800 ${compact ? "text-xs" : "text-base"}`}>
              {animal.especie}
            </h3>
          </div>

          <div className={`mt-1 h-1 rounded-full ${colorSexo}`} />
          <p className={`text-gray-500 ${compact ? "text-[11px]" : "text-sm"}`}>
            {animal.alias || "Sin Alias"}
          </p>
        </div>

        <span
          className={`font-medium rounded-full ${colorViñeta(animal)} ${
            compact ? "text-[9px] px-1.5 py-0.5" : "text-xs px-2 py-1"
          }`}
        >
          {animal.estado_productivo || "Sin estado"}
        </span>
      </div>

      <div
        className={`text-gray-700 space-y-0.5 ${compact ? "text-[11px]" : "text-sm"}`}
      >
        <p>
          <span className="font-semibold">Arete interno:</span>{" "}
          {animal.arete || "N/D"}
        </p>
        <p>
          <span className="font-semibold">Identificador:</span>{" "}
          {animal.identificador || "N/D"}
        </p>
        {!compact && (
          <>
            <p>
              <span className="font-semibold">Tipo de parto:</span>{" "}
              {LABELS_TIPO_PARTO[animal.tipo_parto_origen] || "N/D"}
            </p>
            <p>
              <span className="font-semibold">N° de registro:</span>{" "}
              {animal.numero_registro || "N/D"}
            </p>
          </>
        )}
      </div>

      <div className={`flex justify-between ${compact ? "mt-1.5" : "mt-2"}`}>
        <button
          onClick={() => onShow(animal)}
          className={`flex items-center gap-1 bg-green-100 text-green-700 font-medium rounded-lg hover:bg-green-200 transition ${
            compact ? "px-1.5 py-1 text-[10px]" : "px-3 py-1.5 text-sm"
          }`}
        >
          <Eye className={compact ? "w-3 h-3" : "w-4 h-4"} />
          Ver ficha
        </button>

        <button
          onClick={() => onEdit(animal)}
          className={`flex items-center gap-1 bg-blue-100 text-blue-700 font-medium rounded-lg hover:bg-blue-200 transition ${
            compact ? "px-1.5 py-1 text-[10px]" : "px-3 py-1.5 text-sm"
          }`}
        >
          <Edit className={compact ? "w-3 h-3" : "w-4 h-4"} />
          Editar
        </button>
      </div>
    </div>
  );
}

// ── Sección reutilizable con límite de 2 filas + botón "Ver todos" ──────
function SeccionAnimales({
  titulo,
  items,
  columnas,
  expandido,
  onToggleExpandido,
  onShow,
  onEdit,
}) {
  if (items.length === 0) return null;

  // 2 filas = columnas * 2 tarjetas visibles antes de expandir
  const maxVisible = columnas * 2;
  const mostrarBoton = items.length > maxVisible;
  const visibles = expandido ? items : items.slice(0, maxVisible);

  const gridClass =
    columnas === 8
      ? "grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3"
      : "grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-4 gap-6";

  return (
    <div className="mb-10">
      <div className="flex items-center justify-between mb-4">
        <h2 className="text-lg font-bold text-gray-800">
          {titulo}{" "}
          <span className="text-gray-400 font-normal">({items.length})</span>
        </h2>

        {mostrarBoton && (
          <button
            onClick={onToggleExpandido}
            className="flex items-center gap-1 text-sm font-medium text-green-700 hover:text-green-800 transition"
          >
            {expandido ? (
              <>
                Ver menos <ChevronUp className="w-4 h-4" />
              </>
            ) : (
              <>
                Ver todos <ChevronDown className="w-4 h-4" />
              </>
            )}
          </button>
        )}
      </div>

      <div className={gridClass}>
        {visibles.map((animal) => (
          <AnimalCard
            key={animal.id}
            animal={animal}
            onShow={onShow}
            onEdit={onEdit}
            compact={columnas === 8}
          />
        ))}
      </div>
    </div>
  );
}

export default function Index({
  auth,
  animales,
  lotes = [],
  especies = [],
  razasPorEspecie = {},
  estadosProductivos = {},
  estadosSistema = [],
}) {
  const [showModal, setShowModal] = useState(false);
  const [busqueda, setBusqueda] = useState("");
  const [isEditOpen, setIsEditOpen] = useState(false);
  const [isShowOpen, setIsShowOpen] = useState(false);
  const [selectedAnimal, setSelectedAnimal] = useState(null);

  // Filtro por estado productivo (reemplaza al filtro por especie)
  const [filtroEstado, setFiltroEstado] = useState("");

  // Cantidad de columnas del grid: 4 (normal) u 8 (tarjetas compactas)
  const [columnas, setColumnas] = useState(4);

  // Control de expansión independiente para cada sección
  const [expandido, setExpandido] = useState({
    animales: false,
    corderos: false,
    sistema: false,
  });

  const toggleExpandido = (seccion) => {
    setExpandido((prev) => ({ ...prev, [seccion]: !prev[seccion] }));
  };

  // Si el backend no manda la prop (por ejemplo, una versión vieja del
  // controlador cacheada), se usa el fallback para no romper la vista.
  const estadosSistemaActivos = estadosSistema.length > 0 ? estadosSistema : ESTADOS_SISTEMA_FALLBACK;

  const { data, setData, post, reset, errors } = useForm({
    especie: "", alias: "", arete: "", sexo: "", raza: "", fecha_nac: "", peso: "", BCS: "", estado_productivo: "", lote_id: "", siniiga_id: "", identificador: "", numero_registro: "",
    grado_pureza: "", color: "",
    madre_id: "", padre_id: "", padre_externo_id: "",
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
    if (confirm("¿Seguro que deseas eliminar este animal?")) {
      router.delete(route("animales.destroy", id));
    }
  };

  const razas = data.especie ? razasPorEspecie[data.especie] || [] : [];
  const estados = data.especie ? estadosProductivos[data.especie] || [] : [];

  // ── Estados productivos únicos presentes en los animales (para el filtro) ──
  const todosEstados = useMemo(() => {
    const set = new Set();
    animales.forEach((a) => {
      if (a.estado_productivo) set.add(a.estado_productivo);
    });
    return Array.from(set).sort((a, b) => a.localeCompare(b));
  }, [animales]);

  // ── Filtro por búsqueda (alias/arete) y por estado productivo ──────────
  const animalesFiltrados = useMemo(() => {
    return animales.filter((a) => {
      const coincideBusqueda =
        a.alias?.toLowerCase().includes(busqueda.toLowerCase()) ||
        a.arete?.toLowerCase().includes(busqueda.toLowerCase());
      const coincideEstado = filtroEstado
        ? a.estado_productivo === filtroEstado
        : true;
      return coincideBusqueda && coincideEstado;
    });
  }, [animales, busqueda, filtroEstado]);

  // ── División en las 3 secciones: Animales, Corderos, Sistema ───────────
  const { seccionAnimales, seccionCorderos, seccionSistema } = useMemo(() => {
    const sistema = [];
    const corderos = [];
    const resto = [];

    animalesFiltrados.forEach((a) => {
      if (estadosSistemaActivos.includes(a.estado_productivo)) {
        sistema.push(a);
      } else if (a.estado_productivo === "Cordero") {
        corderos.push(a);
      } else {
        resto.push(a);
      }
    });

    return {
      seccionAnimales: resto,
      seccionCorderos: corderos,
      seccionSistema: sistema,
    };
  }, [animalesFiltrados, estadosSistemaActivos]);

  const abrirShow = (animal) => {
    setSelectedAnimal(animal);
    setIsShowOpen(true);
  };

  const abrirEdit = (animal) => {
    setSelectedAnimal(animal);
    setIsEditOpen(true);
  };

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={<h2 className="font-semibold text-xl text-gray-800">Gestión de Animales</h2>}
    >
      <Head title="Gestión de Animales" />
      <div className="py-8 px-6 max-w-7xl mx-auto">
        <div className="flex justify-between items-center mb-4">
          <h1 className="text-2xl font-bold text-gray-800">Animales Registrados</h1>
          <div className="flex gap-2">
            <button
              onClick={() => setShowModal(true)}
              className="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
            >
              <PlusCircle className="w-5 h-5" />
              Agregar Animal
            </button>
          </div>
        </div>

        <div className="flex flex-col sm:flex-row gap-3 mb-6">
          <div className="flex items-center bg-white border border-gray-300 rounded-lg px-3 py-2 w-full sm:w-1/2">
            <Search className="w-5 h-5 text-gray-500 mr-2" />
            <input
              type="text"
              placeholder="Buscar por alias o arete..."
              value={busqueda}
              onChange={(e) => setBusqueda(e.target.value)}
              className="w-full outline-none text-gray-700"
            />
          </div>

          {/* Filtro por estado productivo (antes era por especie) */}
          <select
            value={filtroEstado}
            onChange={(e) => setFiltroEstado(e.target.value)}
            className="border border-gray-300 rounded-lg px-3 py-2 text-gray-700 w-full sm:w-1/4"
          >
            <option value="">Todos los estados</option>
            {todosEstados.map((estado) => (
              <option key={estado} value={estado}>
                {estado}
              </option>
            ))}
          </select>

          {/* Selector de cantidad de columnas / densidad de tarjetas */}
          <select
            value={columnas}
            onChange={(e) => setColumnas(Number(e.target.value))}
            className="border border-gray-300 rounded-lg px-3 py-2 text-gray-700 w-full sm:w-1/4"
          >
            <option value={4}>4 columnas</option>
            <option value={8}>8 columnas</option>
          </select>
        </div>

        {animalesFiltrados.length === 0 ? (
          <div className="text-center py-8">
            <p className="text-gray-500 text-lg">No hay animales registrados aún.</p>
          </div>
        ) : (
          <>
            <SeccionAnimales
              titulo="Animales"
              items={seccionAnimales}
              columnas={columnas}
              expandido={expandido.animales}
              onToggleExpandido={() => toggleExpandido("animales")}
              onShow={abrirShow}
              onEdit={abrirEdit}
            />

            <SeccionAnimales
              titulo="Corderos"
              items={seccionCorderos}
              columnas={columnas}
              expandido={expandido.corderos}
              onToggleExpandido={() => toggleExpandido("corderos")}
              onShow={abrirShow}
              onEdit={abrirEdit}
            />

            <SeccionAnimales
              titulo="Historial (Faeneado, Vendido, Sacrificado, Muerto)"
              items={seccionSistema}
              columnas={columnas}
              expandido={expandido.sistema}
              onToggleExpandido={() => toggleExpandido("sistema")}
              onShow={abrirShow}
              onEdit={abrirEdit}
            />
          </>
        )}
      </div>

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
        animales={animales}
      />

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
    </AuthenticatedLayout>
  );
}