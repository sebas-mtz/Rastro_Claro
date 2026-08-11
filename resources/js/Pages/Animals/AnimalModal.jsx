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
  animales = [],
  errors
}) {
  if (!show) return null;

  const hayMadre = Boolean(data.madre_id);

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
              label="ID SINIIGA"
              value={data.siniiga_id}
              onChange={(e) => setData("siniiga_id", e.target.value)}
            />

            <Input
              label="Identificador"
              value={data.identificador}
              onChange={(e) => setData("identificador", e.target.value)}
            />

            <Input
              label="Número de registro"
              value={data.numero_registro}
              onChange={(e) => setData("numero_registro", e.target.value)}
            />
            <Input
              label="Arete interno*"
              value={data.arete}
              onChange={(e) => setData("arete", e.target.value)}
              required
            />
            <Select
              label="Sexo *"
              value={data.sexo}
              onChange={(e) => setData("sexo", e.target.value)}
              options={["M", "H"]}
              required
            />

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
            <Input
              label="Grado de pureza"
              value={data.grado_pureza}
              onChange={(e) => setData("grado_pureza", e.target.value)}
            />

            <Input
              label="Color"
              value={data.color}
              onChange={(e) => setData("color", e.target.value)}
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

            {/* Genealogía */}
            <Select
              label="Madre"
              value={data.madre_id}
              onChange={(e) => {
                const madreId = e.target.value;
                setData((current) => ({
                  ...current,
                  madre_id: madreId,
                  // Si se quita la madre, limpia también el historial —
                  // ya no aplica y no debe enviarse al backend.
                  ...(madreId
                    ? {}
                    : {
                        concepcion_historica: "",
                        tipo_nacimiento_historico: "",
                        tipo_parto_origen: "",
                      }),
                }));
              }}
              options={animales
                .filter((a) => a.sexo === "H")
                .map((a) => ({
                  value: a.id,
                  text: `${a.arete} ${a.alias ? "- " + a.alias : ""}`,
                }))}
            />

            <Select
              label="Padre"
              value={data.padre_id}
              onChange={(e) => setData("padre_id", e.target.value)}
              options={animales
                .filter((a) => a.sexo === "M")
                .map((a) => ({
                  value: a.id,
                  text: `${a.arete} ${a.alias ? "- " + a.alias : ""}`,
                }))}
            />

            {/* ── Historial reproductivo ──────────────────────────────── */}
            {/* Solo aplica si se seleccionó una madre: el backend reconstruye     */}
            {/* el servicio y parto histórico a partir de estos datos.            */}
            {hayMadre && (
              <div className="border rounded-lg p-4 bg-gray-50 space-y-4">
                <p className="text-sm font-medium text-gray-700">
                  Historial reproductivo
                </p>
                <p className="text-xs text-gray-500 -mt-2">
                  Como seleccionaste una madre, necesitamos reconstruir cómo
                  ocurrió esta concepción y nacimiento.
                </p>

                <Select
                  label="Tipo de concepción *"
                  value={data.concepcion_historica}
                  onChange={(e) =>
                    setData("concepcion_historica", e.target.value)
                  }
                  options={[
                    { value: "monta_natural", text: "Monta natural" },
                    { value: "monta_controlada", text: "Monta controlada" },
                    {
                      value: "inseminacion_artificial",
                      text: "Inseminación artificial",
                    },
                    { value: "iatf", text: "IATF" },
                    {
                      value: "transferencia_embriones",
                      text: "Transferencia de embriones",
                    },
                    { value: "fiv", text: "FIV" },
                  ]}
                  required
                />

                {data.concepcion_historica === "monta_natural" && (
                  <p className="text-xs text-amber-600">
                    La monta natural histórica requiere seleccionar un padre
                    interno arriba (no aplica donador externo).
                  </p>
                )}

                <Select
                  label="Tipo de nacimiento *"
                  value={data.tipo_nacimiento_historico}
                  onChange={(e) =>
                    setData("tipo_nacimiento_historico", e.target.value)
                  }
                  options={[
                    { value: "simple", text: "Simple" },
                    { value: "gemelar", text: "Gemelar (2 crías)" },
                    { value: "triple", text: "Triple (3 crías)" },
                    { value: "cuadruple", text: "Cuádruple (4 crías)" },
                    { value: "quintuple", text: "Quíntuple (5 crías)" },
                  ]}
                  required
                />

                <Select
                  label="Tipo de parto *"
                  value={data.tipo_parto_origen}
                  onChange={(e) =>
                    setData("tipo_parto_origen", e.target.value)
                  }
                  options={[
                    { value: "normal", text: "Normal" },
                    { value: "distocico", text: "Distócico" },
                    { value: "cesarea", text: "Cesárea" },
                  ]}
                  required
                />

                {errors.concepcion_historica && (
                  <p className="text-xs text-red-500">
                    {errors.concepcion_historica}
                  </p>
                )}
                {errors.tipo_nacimiento_historico && (
                  <p className="text-xs text-red-500">
                    {errors.tipo_nacimiento_historico}
                  </p>
                )}
                {errors.tipo_parto_origen && (
                  <p className="text-xs text-red-500">
                    {errors.tipo_parto_origen}
                  </p>
                )}
              </div>
            )}
          </form>
        </div>

        {/* Footer con botones */}
        <div className="flex justify-end gap-3 p-6 border-t border-gray-200 flex-shrink-0">
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300"
          >
            Cancelar
          </button>
          <button
            type="submit"
            onClick={onSubmit}
            className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
          >
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