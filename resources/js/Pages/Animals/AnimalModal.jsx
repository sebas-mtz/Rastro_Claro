import React from "react";

export default function AnimalModal({ 
  show, 
  onClose, 
  data, 
  setData, 
  onSubmit, 
  razas, 
  estados, 
  lotes = [], 
  especies, 
  errors 
}) {
  if (!show) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
      <div className="bg-white rounded-2xl shadow-lg w-full max-w-lg max-h-[90vh] flex flex-col">
        
        {/* Header */}
        <div className="flex justify-between items-center p-6 border-b border-gray-200 flex-shrink-0">
          <h2 className="text-xl font-semibold">Registrar Nuevo Animal</h2>
          <button
            onClick={onClose}
            aria-label="Cerrar modal"
            className="text-gray-500 hover:text-gray-700"
          >
            ✕
          </button>
        </div>

        {/* Contenido scrollable */}
        <div className="overflow-y-auto flex-1 p-6">
          <form onSubmit={onSubmit} className="space-y-4">
            {/* CAMPO ALIAS AGREGADO */}
            <Input 
              label="Alias (Apodo)" 
              value={data.alias} 
              onChange={(e) => setData("alias", e.target.value)} 
              placeholder="Ej: Manchado, Blanquita, etc."
            />
            
            <Select 
              label="Especie *" 
              value={data.especie} 
              onChange={(e) => setData("especie", e.target.value)} 
              options={especies} 
              required 
            />
            <Input 
              label="Arete *" 
              value={data.arete} 
              onChange={(e) => setData("arete", e.target.value)} 
              required 
            />
            <Select 
              label="Sexo *" 
              value={data.sexo} 
              onChange={(e) => setData("sexo", e.target.value)} 
              options={["M","F"]} 
              required 
            />

            {/* Campos condicionales */}
            {razas.length > 0 && (
              <Select 
                label="Raza" 
                value={data.raza} 
                onChange={(e) => setData("raza", e.target.value)} 
                options={razas} 
              />
            )}
            
            <Input 
              label="Fecha de Nacimiento" 
              type="date" 
              value={data.fecha_nac} 
              onChange={(e) => setData("fecha_nac", e.target.value)} 
            />
            <Input 
              label="Peso (kg)" 
              type="number" 
              step="0.1" 
              value={data.peso} 
              onChange={(e) => setData("peso", e.target.value)} 
            />
            <Input 
              label="BCS (1.0 a 5.0)" 
              type="number" 
              step="0.1" 
              min="1" 
              max="5" 
              value={data.BCS} 
              onChange={(e) => setData("BCS", e.target.value)} 
            />

            {estados.length > 0 && (
              <Select 
                label="Estado Productivo" 
                value={data.estado_productivo} 
                onChange={(e) => setData("estado_productivo", e.target.value)} 
                options={estados} 
              />
            )}

            {lotes.length > 0 && (
              <Select
                label="Lote"
                value={data.lote_id}
                onChange={(e) => setData("lote_id", e.target.value)}
                options={lotes.map((l) => ({ value: l.id, text: l.nombre }))}
              />
            )}
          </form>
        </div>

        {/* Footer con botones */}
        <div className="flex justify-end gap-3 p-6 border-t border-gray-200 flex-shrink-0">
          <button type="button" onClick={onClose} className="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">
            Cancelar
          </button>
          <button type="submit" onClick={onSubmit} className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            Guardar
          </button>
        </div>
      </div>
    </div>
  );
}

// Componentes reutilizables
export function Input({ label, ...props }) {
  return (
    <div>
      <label className="block text-sm font-medium mb-1">{label}</label>
      <input {...props} className="w-full border rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500" />
    </div>
  );
}

export function Select({ label, value, onChange, options, required }) {
  const id = `select-${label.replace(/\s+/g, "-").toLowerCase()}`;
  return (
    <div>
      <label htmlFor={id} className="block text-sm font-medium mb-1">{label}</label>
      <select
        id={id}
        value={value}
        onChange={onChange}
        required={required}
        className="w-full border rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
      >
        <option value="">Selecciona una opción</option>
        {options.map(opt => typeof opt === "string" ? (
          <option key={opt} value={opt}>{opt}</option>
        ) : (
          <option key={opt.value} value={opt.value}>{opt.text}</option>
        ))}
      </select>
    </div>
  );
}