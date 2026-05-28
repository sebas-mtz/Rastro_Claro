import React from "react"; 
import { useForm } from "@inertiajs/react";
import { Save, X } from "lucide-react";

export default function EditModal({ 
    animal, 
    lotes = [], 
    especies = [], 
    razasPorEspecie = {}, 
    estadosProductivos = {}, 
    onClose 
}) {
    const { data, setData, put, processing, errors } = useForm({
        alias: animal.alias ?? "",
        especie: animal.especie ?? "",
        arete: animal.arete ?? "",
        sexo: animal.sexo ?? "",
        raza: animal.raza ?? "",
        fecha_nac: animal.fecha_nac ?? "",
        peso: animal.peso ?? "",
        BCS: animal.BCS ?? "",
        estado_productivo: animal.estado_productivo ?? "",
        lote_id: animal.lote_id ?? "",
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route("animales.update", animal.id), {
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    };

    // Protege arrays
    const razas = data.especie ? (razasPorEspecie[data.especie] || []) : [];
    const estados = data.especie ? (estadosProductivos[data.especie] || []) : [];
    const safeLotes = Array.isArray(lotes) ? lotes : [];
    const safeEspecies = Array.isArray(especies) ? especies : [];

    return (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] overflow-y-auto p-6 relative">
                <button
                    onClick={onClose}
                    className="absolute top-3 right-3 text-gray-500 hover:text-gray-700"
                >
                    <X className="w-5 h-5" />
                </button>

                <h2 className="text-xl font-semibold text-gray-800 mb-4">Editar Animal</h2>

                <form onSubmit={handleSubmit} className="space-y-4">
                    
                    <Input 
                        label="Alias (opcional)" 
                        value={data.alias} 
                        onChange={(e) => setData("alias", e.target.value)} 
                        error={errors.alias} 
                    />

                    <Select 
                        label="Especie *" 
                        value={data.especie} 
                        options={safeEspecies} 
                        onChange={(e) => setData("especie", e.target.value)} 
                        error={errors.especie} 
                        required 
                    />

                    <Input 
                        label="Arete *" 
                        value={data.arete} 
                        onChange={(e) => setData("arete", e.target.value)} 
                        error={errors.arete} 
                        required 
                    />

                    <Select 
                        label="Sexo *" 
                        value={data.sexo} 
                        options={[
                            { value: "M", label: "Macho" }, 
                            { value: "F", label: "Hembra" }
                        ]} 
                        onChange={(e) => setData("sexo", e.target.value)} 
                        error={errors.sexo}
                        required 
                    />


                    {razas.length > 0 && (
                        <Select 
                            label="Raza (opcional)" 
                            value={data.raza} 
                            options={razas.map(r => ({ value: r, label: r }))} 
                            onChange={(e) => setData("raza", e.target.value)} 
                            error={errors.raza}
                        />
                    )}

                    <Input 
                        label="Fecha de nacimiento (opcional)" 
                        type="date" 
                        value={data.fecha_nac} 
                        onChange={(e) => setData("fecha_nac", e.target.value)} 
                        error={errors.fecha_nac}
                    />

                    <div className="grid grid-cols-2 gap-4">
                        <Input 
                            label="Peso (kg) (opcional)" 
                            type="number" 
                            step="0.1" 
                            value={data.peso} 
                            onChange={(e) => setData("peso", e.target.value)} 
                            error={errors.peso}
                        />
                        <Input 
                            label="BCS (opcional)" 
                            type="number" 
                            step="0.1" 
                            value={data.BCS} 
                            onChange={(e) => setData("BCS", e.target.value)} 
                            error={errors.BCS}
                        />
                    </div>


                    {estados.length > 0 && (
                        <Select 
                            label="Estado productivo (opcional)" 
                            value={data.estado_productivo} 
                            options={estados.map(e => ({ value: e, label: e }))} 
                            onChange={(e) => setData("estado_productivo", e.target.value)} 
                            error={errors.estado_productivo}
                        />
                    )}


                    {safeLotes.length > 0 && (
                        <Select 
                            label="Lote (opcional)" 
                            value={data.lote_id} 
                            options={safeLotes.map(l => ({ value: l.id, label: l.nombre }))} 
                            onChange={(e) => setData("lote_id", e.target.value)} 
                            error={errors.lote_id}
                        />
                    )}

                    <div className="flex justify-end gap-3 pt-4">
                        <button 
                            type="button" 
                            onClick={onClose} 
                            className="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300"
                        >
                            Cancelar
                        </button>
                        
                        <button 
                            type="submit" 
                            disabled={processing} 
                            className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center gap-2 disabled:opacity-50"
                        >
                            <Save className="w-4 h-4" />
                            {processing ? "Guardando..." : "Guardar Cambios"}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

function Input({ label, error, ...props }) {
    return (
        <div>
            <label className="block text-sm font-medium mb-1">{label}</label>
            <input 
                {...props} 
                className={`w-full border rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500 ${
                    error ? "border-red-500" : ""
                }`} 
            />
            {error && <p className="text-red-600 text-sm mt-1">{error}</p>}
        </div>
    );
}

function Select({ label, value, options = [], onChange, error, required = false }) {

    const safeOptions = Array.isArray(options) ? options : [];

    return (
        <div>
            <label className="block text-sm font-medium mb-1">{label}</label>
            <select 
                value={value} 
                onChange={onChange} 
                required={required} 
                className={`w-full border rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500 ${
                    error ? "border-red-500" : ""
                }`}
            >
                <option value="">Selecciona...</option>
                {safeOptions.map((opt) => (
                    <option key={opt.value || opt} value={opt.value || opt}>
                        {opt.label || opt}
                    </option>
                ))}
            </select>
            {error && <p className="text-red-600 text-sm mt-1">{error}</p>}
        </div>
    );
}