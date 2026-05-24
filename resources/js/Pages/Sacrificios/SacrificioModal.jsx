import React from 'react';
import { X, Calculator, Scale, AlertTriangle, FileText } from 'lucide-react';
import { useForm } from '@inertiajs/react';

export default function SacrificioModal({ show, onClose, animales = [], lotes = [] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        animal_id: '',
        lote_id: '',
        motivo: 'descarte',
        fecha: new Date().toISOString().split('T')[0],
        peso_vivo: '',
        peso_canal: '',
        cuero: false,
        grasa: false,
        visceras: false,
        plumas: false,
        observaciones: ''
    });

    const motivos = [
        { value: 'descarte', label: 'Descarte (animal viejo)' },
        { value: 'enfermedad', label: 'Enfermedad' },
        { value: 'accidente', label: 'Accidente' },
        { value: 'autoconsumo', label: 'Auto-consumo' }
    ];

    const calcularRendimiento = () => {
        if (data.peso_vivo && data.peso_canal) {
            const pesoVivo = parseFloat(data.peso_vivo) || 0;
            const pesoCanal = parseFloat(data.peso_canal) || 0;
            if (pesoVivo > 0) {
                const rendimiento = (pesoCanal / pesoVivo) * 100;
                return rendimiento.toFixed(2);
            }
        }
        return '';
    };

    // ✅ Auto-marcar plumas para aves
    const handleAnimalChange = (animalId) => {
        setData('animal_id', animalId);
        
        const animalSeleccionado = animales.find(a => a.id == animalId);
        if (animalSeleccionado && ['Gallos', 'Aves de corral (gallinas y pollitos)'].includes(animalSeleccionado.especie)) {
            // Auto-marcar plumas para aves
            setData('plumas', true);
        } else {
            // Desmarcar plumas si no es ave
            setData('plumas', false);
        }

        // ✅ Auto-completar peso vivo si el animal tiene peso registrado
        if (animalSeleccionado && animalSeleccionado.peso && !data.peso_vivo) {
            setData('peso_vivo', animalSeleccionado.peso);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        
        // Validaciones básicas
        if (!data.animal_id) {
            alert('Por favor selecciona un animal');
            return;
        }

        if (!data.peso_vivo || !data.peso_canal) {
            alert('Los pesos vivo y canal son obligatorios');
            return;
        }

        post(route('sacrificios.store'), {
            onSuccess: () => {
                reset();
                onClose();
            },
            onError: (errors) => {
                console.log('Errores:', errors);
            }
        });
    };

    const handleClose = () => {
        reset();
        onClose();
    };

    if (!show) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div className="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                {/* Header */}
                <div className="flex justify-between items-center border-b p-6">
                    <div className="flex items-center gap-3">
                        <AlertTriangle className="w-6 h-6 text-red-600" />
                        <h2 className="text-xl font-bold text-gray-800">Registrar Sacrificio</h2>
                    </div>
                    <button 
                        onClick={handleClose} 
                        className="text-gray-400 hover:text-gray-600 transition-colors"
                        disabled={processing}
                    >
                        <X className="w-6 h-6" />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="p-6 space-y-6">
                    <div className="grid grid-cols-1 gap-6">
                        {/* Selección de Animal */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Animal a Sacrificar *
                            </label>
                            <select
                                value={data.animal_id}
                                onChange={(e) => handleAnimalChange(e.target.value)}
                                className={`w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent ${
                                    errors.animal_id ? 'border-red-300' : 'border-gray-300'
                                }`}
                                required
                                disabled={processing}
                            >
                                <option value="">Seleccionar animal...</option>
                                {animales.map((animal) => (
                                    <option key={animal.id} value={animal.id}>
                                        {animal.nombre} - {animal.especie} ({animal.peso} kg) - {animal.edad} - {animal.lote_nombre}
                                    </option>
                                ))}
                            </select>
                            {errors.animal_id && (
                                <p className="mt-1 text-sm text-red-600">{errors.animal_id}</p>
                            )}
                        </div>

                        {/* Lote (opcional) */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Lote (opcional)
                            </label>
                            <select
                                value={data.lote_id}
                                onChange={(e) => setData('lote_id', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                disabled={processing}
                            >
                                <option value="">Sin lote específico</option>
                                {lotes.map((lote) => (
                                    <option key={lote.id} value={lote.id}>
                                        {lote.nombre} - {lote.especie} ({lote.animales_count} animales)
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Motivo del Sacrificio */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Motivo del Sacrificio *
                            </label>
                            <div className="grid grid-cols-2 gap-3">
                                {motivos.map((motivo) => (
                                    <label
                                        key={motivo.value}
                                        className={`flex items-center p-3 border rounded-lg cursor-pointer transition-colors ${
                                            data.motivo === motivo.value
                                                ? 'border-2 border-red-500 bg-red-50'
                                                : 'border-gray-300 hover:bg-gray-50'
                                        } ${processing ? 'opacity-50 cursor-not-allowed' : ''}`}
                                    >
                                        <input
                                            type="radio"
                                            name="motivo"
                                            value={motivo.value}
                                            checked={data.motivo === motivo.value}
                                            onChange={(e) => setData('motivo', e.target.value)}
                                            className="text-red-600 focus:ring-red-500"
                                            disabled={processing}
                                        />
                                        <span className="ml-3 text-sm font-medium text-gray-700">
                                            {motivo.label}
                                        </span>
                                    </label>
                                ))}
                            </div>
                            {errors.motivo && (
                                <p className="mt-1 text-sm text-red-600">{errors.motivo}</p>
                            )}
                        </div>

                        {/* Fecha */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Fecha de Sacrificio *
                            </label>
                            <input
                                type="date"
                                value={data.fecha}
                                onChange={(e) => setData('fecha', e.target.value)}
                                className={`w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent ${
                                    errors.fecha ? 'border-red-300' : 'border-gray-300'
                                }`}
                                required
                                disabled={processing}
                            />
                            {errors.fecha && (
                                <p className="mt-1 text-sm text-red-600">{errors.fecha}</p>
                            )}
                        </div>

                        {/* Pesos */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    <Scale className="w-4 h-4 inline mr-2" />
                                    Peso Vivo (kg) *
                                </label>
                                <input
                                    type="number"
                                    value={data.peso_vivo}
                                    onChange={(e) => setData('peso_vivo', e.target.value)}
                                    className={`w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent ${
                                        errors.peso_vivo ? 'border-red-300' : 'border-gray-300'
                                    }`}
                                    placeholder="0.00"
                                    step="0.01"
                                    min="0.1"
                                    required
                                    disabled={processing}
                                />
                                {errors.peso_vivo && (
                                    <p className="mt-1 text-sm text-red-600">{errors.peso_vivo}</p>
                                )}
                                <p className="text-xs text-gray-500 mt-1">
                                    Peso registrado del animal: {animales.find(a => a.id == data.animal_id)?.peso || 'N/D'} kg
                                </p>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    <Scale className="w-4 h-4 inline mr-2" />
                                    Peso Canal (kg) *
                                </label>
                                <input
                                    type="number"
                                    value={data.peso_canal}
                                    onChange={(e) => setData('peso_canal', e.target.value)}
                                    className={`w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent ${
                                        errors.peso_canal ? 'border-red-300' : 'border-gray-300'
                                    }`}
                                    placeholder="0.00"
                                    step="0.01"
                                    min="0.1"
                                    required
                                    disabled={processing}
                                />
                                {errors.peso_canal && (
                                    <p className="mt-1 text-sm text-red-600">{errors.peso_canal}</p>
                                )}
                            </div>
                        </div>

                        {/* Rendimiento Calculado */}
                        {data.peso_vivo && data.peso_canal && (
                            <div className={`border rounded-lg p-4 ${
                                calcularRendimiento() > 55 ? 'bg-green-50 border-green-200' :
                                calcularRendimiento() > 45 ? 'bg-yellow-50 border-yellow-200' :
                                'bg-red-50 border-red-200'
                            }`}>
                                <div className="flex items-center justify-between">
                                    <span className="font-semibold text-gray-800">Rendimiento Calculado:</span>
                                    <span className="text-2xl font-bold text-gray-700">
                                        {calcularRendimiento()}%
                                    </span>
                                </div>
                                <p className={`text-sm mt-1 ${
                                    calcularRendimiento() > 55 ? 'text-green-600' :
                                    calcularRendimiento() > 45 ? 'text-yellow-600' : 'text-red-600'
                                }`}>
                                    {calcularRendimiento() > 55 ? 'Excelente' : 
                                     calcularRendimiento() > 45 ? 'Normal' : 'Bajo'}
                                </p>
                            </div>
                        )}

                        {/* Subproductos */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-3">
                                Subproductos Obtenidos
                            </label>
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <label className={`flex items-center p-3 border rounded-lg cursor-pointer transition-colors ${
                                    processing ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'
                                }`}>
                                    <input
                                        type="checkbox"
                                        checked={data.cuero}
                                        onChange={(e) => setData('cuero', e.target.checked)}
                                        className="text-red-600 focus:ring-red-500"
                                        disabled={processing}
                                    />
                                    <span className="ml-3 text-sm font-medium text-gray-700">Cuero</span>
                                </label>
                                <label className={`flex items-center p-3 border rounded-lg cursor-pointer transition-colors ${
                                    processing ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'
                                }`}>
                                    <input
                                        type="checkbox"
                                        checked={data.grasa}
                                        onChange={(e) => setData('grasa', e.target.checked)}
                                        className="text-red-600 focus:ring-red-500"
                                        disabled={processing}
                                    />
                                    <span className="ml-3 text-sm font-medium text-gray-700">Grasa</span>
                                </label>
                                <label className={`flex items-center p-3 border rounded-lg cursor-pointer transition-colors ${
                                    processing ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'
                                }`}>
                                    <input
                                        type="checkbox"
                                        checked={data.visceras}
                                        onChange={(e) => setData('visceras', e.target.checked)}
                                        className="text-red-600 focus:ring-red-500"
                                        disabled={processing}
                                    />
                                    <span className="ml-3 text-sm font-medium text-gray-700">Vísceras</span>
                                </label>
                                <label className={`flex items-center p-3 border rounded-lg cursor-pointer transition-colors ${
                                    processing ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'
                                } ${data.plumas ? 'bg-gray-50' : ''}`}>
                                    <input
                                        type="checkbox"
                                        checked={data.plumas}
                                        onChange={(e) => setData('plumas', e.target.checked)}
                                        className="text-red-600 focus:ring-red-500"
                                        disabled={processing}
                                    />
                                    <span className="ml-3 text-sm font-medium text-gray-700">Plumas</span>
                                    {data.plumas && (
                                        <span className="ml-2 text-xs text-gray-500">(Auto)</span>
                                    )}
                                </label>
                            </div>
                        </div>

                        {/* Observaciones */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                <FileText className="w-4 h-4 inline mr-2" />
                                Observaciones
                            </label>
                            <textarea
                                value={data.observaciones}
                                onChange={(e) => setData('observaciones', e.target.value)}
                                rows="3"
                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                placeholder="Observaciones sobre el sacrificio, condición del animal, síntomas, etc."
                                disabled={processing}
                            />
                            {errors.observaciones && (
                                <p className="mt-1 text-sm text-red-600">{errors.observaciones}</p>
                            )}
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="flex justify-end gap-3 pt-6 border-t">
                        <button
                            type="button"
                            onClick={handleClose}
                            className="px-6 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50"
                            disabled={processing}
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            className="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center gap-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled={processing}
                        >
                            {processing ? (
                                <>
                                    <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                    Procesando...
                                </>
                            ) : (
                                <>
                                    <Calculator className="w-4 h-4" />
                                    Registrar Sacrificio
                                </>
                            )}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}