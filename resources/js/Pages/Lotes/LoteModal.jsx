import React, { useEffect, useState } from "react";
import { useForm } from "@inertiajs/react";
import { X, Save } from "lucide-react";

export default function LoteModal({ show, onClose, lote = {}, usuarios = [], especies, razasPorEspecie, estadosProductivos }) {
  const [animalData, setAnimalData] = useState({
    especie: "",
    raza: "",
    arete_inicio: "",
    arete_fin: "",
    sexo: "",
    fecha_nac: "",
    peso: "",
    estado_productivo: "",
  });

  const { data, setData, post, put, processing, errors, reset } = useForm({
    nombre: "",
    corral_potrero: "",
    descripcion: "",
    responsable_id: "",
    animal: {
      especie: "",
      raza: "",
      arete_inicio: "",
      arete_fin: "",
      sexo: "",
      fecha_nac: "",
      peso: "",
      estado_productivo: "",
    }
  });

  // Reset completo cuando cambia la visibilidad o el lote
  useEffect(() => {
    if (show) {
      if (lote.id) {
        // Modo edición - solo datos básicos del lote
        setData({
          nombre: lote.nombre || "",
          corral_potrero: lote.corral_potrero || "",
          descripcion: lote.descripcion || "",
          responsable_id: lote.responsable_id || "",
          animal: {
            especie: "",
            raza: "",
            arete_inicio: "",
            arete_fin: "",
            sexo: "",
            fecha_nac: "",
            peso: "",
            estado_productivo: "",
          }
        });
      } else {
        // Modo creación - reset completo
        setData({
          nombre: "",
          corral_potrero: "",
          descripcion: "",
          responsable_id: "",
          animal: {
            especie: "",
            raza: "",
            arete_inicio: "",
            arete_fin: "",
            sexo: "",
            fecha_nac: "",
            peso: "",
            estado_productivo: "",
          }
        });
      }
      // Reset animalData para la UI
      setAnimalData({
        especie: "",
        raza: "",
        arete_inicio: "",
        arete_fin: "",
        sexo: "",
        fecha_nac: "",
        peso: "",
        estado_productivo: "",
      });
    }
  }, [show, lote]);

  // Actualizar animalData en el form data
  useEffect(() => {
    setData("animal", animalData);
  }, [animalData]);

  const handleAnimalChange = (field, value) => {
    setAnimalData(prev => ({
      ...prev,
      [field]: value
    }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    
    // Validaciones
    if (!animalData.especie || !animalData.sexo || !animalData.arete_inicio || !animalData.arete_fin) {
      alert("Especie, sexo y rango de aretes son obligatorios");
      return;
    }

    const inicio = parseInt(animalData.arete_inicio);
    const fin = parseInt(animalData.arete_fin);
    
    if (isNaN(inicio) || isNaN(fin) || inicio > fin) {
      alert("Rango de aretes inválido");
      return;
    }

    if ((fin - inicio) > 100) {
      alert("El rango no puede ser mayor a 100 animales");
      return;
    }

    // Preparar payload final
    const payload = {
      ...data,
      animal: {
        ...animalData,
        arete_inicio: inicio,
        arete_fin: fin,
        // Asegurar que campos opcionales sean null si están vacíos
        raza: animalData.raza || null,
        fecha_nac: animalData.fecha_nac || null,
        peso: animalData.peso ? parseFloat(animalData.peso) : null,
        estado_productivo: animalData.estado_productivo || null,
      }
    };

    if (lote.id) {
      put(route("lotes.update", lote.id), { 
        data: payload, 
        onSuccess: () => onClose() 
      });
    } else {
      post(route("lotes.store"), { 
        data: payload, 
        onSuccess: () => { 
          reset(); 
          onClose(); 
        } 
      });
    }
  };

  if (!show) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50">
      <div className="bg-white rounded-2xl w-full max-w-2xl p-6 relative max-h-[90vh] overflow-y-auto">
        <button className="absolute top-4 right-4 text-gray-500 hover:text-gray-700" onClick={onClose}>
          <X className="w-5 h-5" />
        </button>
        
        <h2 className="text-xl font-semibold mb-4">
          {lote.id ? "Editar Lote" : "Agregar Lote"}
        </h2>

        <form onSubmit={handleSubmit} className="space-y-4">
          {/* Datos del Lote */}
          <div className="space-y-4">
            <h3 className="font-semibold text-lg">Datos del Lote</h3>
            
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
              <input 
                type="text"
                value={data.nombre} 
                onChange={(e) => setData("nombre", e.target.value)} 
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                required 
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Corral/Potrero *</label>
              <input 
                type="text"
                value={data.corral_potrero} 
                onChange={(e) => setData("corral_potrero", e.target.value)} 
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                required
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
              <input 
                type="text"
                value={data.descripcion} 
                onChange={(e) => setData("descripcion", e.target.value)} 
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                placeholder="Descripción opcional del lote"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Responsable *</label>
              <select 
                value={data.responsable_id} 
                onChange={(e) => setData("responsable_id", e.target.value)} 
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                required
              >
                <option value="">Selecciona responsable</option>
                {usuarios.map(u => (
                  <option key={u.id} value={u.id}>{u.name}</option>
                ))}
              </select>
            </div>
          </div>

          <hr className="my-4" />

          {/* Datos de los Animales */}
          <div className="space-y-4">
            <h3 className="font-semibold text-lg">Datos de los Animales</h3>
            <p className="text-sm text-gray-600">
              Estos datos se aplicarán a todos los animales en el rango de aretes especificado.
            </p>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {/* Especie */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Especie *</label>
                <select 
                  value={animalData.especie} 
                  onChange={(e) => handleAnimalChange("especie", e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                  required
                >
                  <option value="">Selecciona especie</option>
                  {especies.map(s => (
                    <option key={s} value={s}>{s}</option>
                  ))}
                </select>
              </div>

              {/* Raza */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Raza</label>
                <select 
                  value={animalData.raza} 
                  onChange={(e) => handleAnimalChange("raza", e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                >
                  <option value="">Selecciona raza</option>
                  {(razasPorEspecie[animalData.especie] || []).map(r => (
                    <option key={r} value={r}>{r}</option>
                  ))}
                </select>
              </div>

              {/* Rango de Aretes */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Arete Inicio *</label>
                <input 
                  type="number" 
                  value={animalData.arete_inicio} 
                  onChange={(e) => handleAnimalChange("arete_inicio", e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                  placeholder="Ej: 1001"
                  min="1"
                  required
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Arete Fin *</label>
                <input 
                  type="number" 
                  value={animalData.arete_fin} 
                  onChange={(e) => handleAnimalChange("arete_fin", e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                  placeholder="Ej: 1050"
                  min="1"
                  required
                />
              </div>

              {/* Sexo */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Sexo *</label>
                <select 
                  value={animalData.sexo} 
                  onChange={(e) => handleAnimalChange("sexo", e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                  required
                >
                  <option value="">Selecciona sexo</option>
                  <option value="M">Macho</option>
                  <option value="F">Hembra</option>
                </select>
              </div>

              {/* Estado Productivo */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Estado Productivo</label>
                <select 
                  value={animalData.estado_productivo} 
                  onChange={(e) => handleAnimalChange("estado_productivo", e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                >
                  <option value="">Selecciona estado</option>
                  {(estadosProductivos[animalData.especie] || []).map(s => (
                    <option key={s} value={s}>{s}</option>
                  ))}
                </select>
              </div>

              {/* Fecha Nacimiento */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Fecha Nacimiento</label>
                <input 
                  type="date" 
                  value={animalData.fecha_nac} 
                  onChange={(e) => handleAnimalChange("fecha_nac", e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                />
              </div>

              {/* Peso */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Peso (kg)</label>
                <input 
                  type="number" 
                  step="0.1"
                  value={animalData.peso} 
                  onChange={(e) => handleAnimalChange("peso", e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                  placeholder="Ej: 350.5"
                  min="0"
                />
              </div>
            </div>

            {/* Información del rango */}
            {animalData.arete_inicio && animalData.arete_fin && (
              <div className="bg-blue-50 border border-blue-200 rounded-lg p-3">
                <p className="text-sm text-blue-800">
                  Se crearán <strong>{parseInt(animalData.arete_fin) - parseInt(animalData.arete_inicio) + 1}</strong> animales 
                  con aretes del <strong>{animalData.arete_inicio}</strong> al <strong>{animalData.arete_fin}</strong>
                </p>
              </div>
            )}
          </div>

          {/* Botones */}
          <div className="flex justify-end gap-3 pt-6 border-t">
            <button 
              type="button" 
              onClick={onClose}
              className="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition"
            >
              Cancelar
            </button>
            <button 
              type="submit" 
              disabled={processing}
              className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 flex items-center gap-2 transition"
            >
              <Save className="w-4 h-4" />
              {processing ? "Guardando..." : "Guardar Lote y Animales"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}