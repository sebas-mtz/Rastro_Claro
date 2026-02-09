import React, { useState } from 'react';
import { X, Calculator, Scale, Package, FileText, Drumstick } from 'lucide-react';
import { useForm } from '@inertiajs/react';

export default function FaenaModal({ show, onClose, animales = [], lotes = [] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        animal_id: '',
        lote_id: '',
        fecha: new Date().toISOString().split('T')[0],
        tipo_corte: 'completo',
        peso_canal: '',
        peso_carne: '',
        peso_cuero: '',
        peso_grasa: '',
        peso_plumas: '', // ✅ Agregado plumas
        peso_hueso: '',
        peso_visceras: '',
        observaciones: ''
    });

    const tiposCorte = [
        { value: 'completo', label: 'Faena Completa' },
        { value: 'media', label: 'Media Res' },
        { value: 'cortes', label: 'Cortes Específicos' },
        { value: 'deshuesado', label: 'Deshuesado' }
    ];

    const calcularRendimiento = () => {
        if (data.peso_canal && data.peso_carne) {
            const pesoCanal = parseFloat(data.peso_canal) || 0;
            const pesoCarne = parseFloat(data.peso_carne) || 0;
            if (pesoCanal > 0) {
                const rendimiento = (pesoCarne / pesoCanal) * 100;
                return rendimiento.toFixed(2);
            }
        }
        return '';
    };

    const calcularTotalSubproductos = () => {
        const total = 
            (parseFloat(data.peso_carne) || 0) +
            (parseFloat(data.peso_cuero) || 0) +
            (parseFloat(data.peso_grasa) || 0) +
            (parseFloat(data.peso_plumas) || 0) +
            (parseFloat(data.peso_hueso) || 0) +
            (parseFloat(data.peso_visceras) || 0);
        return total.toFixed(2);
    };

    // ✅ Auto-calcular plumas para aves
    const handleAnimalChange = (animalId) => {
        setData('animal_id', animalId);
        
        const animalSeleccionado = animales.find(a => a.id == animalId);
        if (animalSeleccionado && ['Gallos', 'Aves de corral (gallinas y pollitos)'].includes(animalSeleccionado.especie)) {
            // Estimación automática de plumas para aves (5-7% del peso canal)
            if (data.peso_canal && !data.peso_plumas) {
                const plumasEstimadas = (parseFloat(data.peso_canal) * 0.06).toFixed(2);
                setData('peso_plumas', plumasEstimadas);
            }
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        
        // Validaciones básicas
        if (!data.animal_id) {
            alert('Por favor selecciona un animal');
            return;
        }

        if (!data.peso_canal || !data.peso_carne) {
            alert('Los pesos de canal y carne son obligatorios');
            return;
        }

        post(route('faenas.store'), {
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
            <div className="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                {/* Header */}
                <div className="flex justify-between items-center border-b p-6">
                    <div className="flex items-center gap-3">
                        <Drumstick className="w-6 h-6 text-yellow-600" />
                        <h2 className="text-xl font-bold text-gray-800">Registrar Faena</h2>
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
                                Animal para Faena *
                            </label>
                            <select
                                value={data.animal_id}
                                onChange={(e) => handleAnimalChange(e.target.value)}
                                className={`w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent ${
                                    errors.animal_id ? 'border-red-300' : 'border-gray-300'
                                }`}
                                required
                                disabled={processing}
                            >
                                <option value="">Seleccionar animal...</option>
                                {animales.map((animal) => (
                                    <option key={animal.id} value={animal.id}>
                                        {animal.alias || animal.arete} - {animal.especie} ({animal.peso} kg)
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
                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                                disabled={processing}
                            >
                                <option value="">Sin lote específico</option>
                                {lotes.map((lote) => (
                                    <option key={lote.id} value={lote.id}>
                                        {lote.nombre} - {lote.especie}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Tipo de Corte */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Tipo de Corte *
                            </label>
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                                {tiposCorte.map((tipo) => (
                                    <label
                                        key={tipo.value}
                                        className={`flex items-center p-3 border rounded-lg cursor-pointer transition-colors ${
                                            data.tipo_corte === tipo.value
                                                ? 'border-2 border-yellow-500 bg-yellow-50'
                                                : 'border-gray-300 hover:bg-gray-50'
                                        } ${processing ? 'opacity-50 cursor-not-allowed' : ''}`}
                                    >
                                        <input
                                            type="radio"
                                            name="tipo_corte"
                                            value={tipo.value}
                                            checked={data.tipo_corte === tipo.value}
                                            onChange={(e) => setData('tipo_corte', e.target.value)}
                                            className="text-yellow-600 focus:ring-yellow-500"
                                            disabled={processing}
                                        />
                                        <span className="ml-3 text-sm font-medium text-gray-700">
                                            {tipo.label}
                                        </span>
                                    </label>
                                ))}
                            </div>
                            {errors.tipo_corte && (
                                <p className="mt-1 text-sm text-red-600">{errors.tipo_corte}</p>
                            )}
                        </div>

                        {/* Fecha */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Fecha de Faena *
                            </label>
                            <input
                                type="date"
                                value={data.fecha}
                                onChange={(e) => setData('fecha', e.target.value)}
                                className={`w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent ${
                                    errors.fecha ? 'border-red-300' : 'border-gray-300'
                                }`}
                                required
                                disabled={processing}
                            />
                            {errors.fecha && (
                                <p className="mt-1 text-sm text-red-600">{errors.fecha}</p>
                            )}
                        </div>

                        {/* Pesos - Primera Fila (Obligatorios) */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    <Scale className="w-4 h-4 inline mr-2" />
                                    Peso Canal (kg) *
                                </label>
                                <input
                                    type="number"
                                    value={data.peso_canal}
                                    onChange={(e) => setData('peso_canal', e.target.value)}
                                    className={`w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent ${
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
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    <Package className="w-4 h-4 inline mr-2 text-red-500" />
                                    Peso Carne (kg) *
                                </label>
                                <input
                                    type="number"
                                    value={data.peso_carne}
                                    onChange={(e) => setData('peso_carne', e.target.value)}
                                    className={`w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent ${
                                        errors.peso_carne ? 'border-red-300' : 'border-gray-300'
                                    }`}
                                    placeholder="0.00"
                                    step="0.01"
                                    min="0.1"
                                    required
                                    disabled={processing}
                                />
                                {errors.peso_carne && (
                                    <p className="mt-1 text-sm text-red-600">{errors.peso_carne}</p>
                                )}
                            </div>
                        </div>

                        {/* Pesos - Subproductos (Opcionales) */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-3">
                                Subproductos (opcionales)
                            </label>
                            <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        <Package className="w-4 h-4 inline mr-2 text-amber-600" />
                                        Cuero (kg)
                                    </label>
                                    <input
                                        type="number"
                                        value={data.peso_cuero}
                                        onChange={(e) => setData('peso_cuero', e.target.value)}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                                        placeholder="0.00"
                                        step="0.01"
                                        min="0"
                                        disabled={processing}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        <Package className="w-4 h-4 inline mr-2 text-yellow-500" />
                                        Grasa (kg)
                                    </label>
                                    <input
                                        type="number"
                                        value={data.peso_grasa}
                                        onChange={(e) => setData('peso_grasa', e.target.value)}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                                        placeholder="0.00"
                                        step="0.01"
                                        min="0"
                                        disabled={processing}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        <Package className="w-4 h-4 inline mr-2 text-gray-500" />
                                        Plumas (kg)
                                    </label>
                                    <input
                                        type="number"
                                        value={data.peso_plumas}
                                        onChange={(e) => setData('peso_plumas', e.target.value)}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                                        placeholder="0.00"
                                        step="0.01"
                                        min="0"
                                        disabled={processing}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        <Package className="w-4 h-4 inline mr-2 text-stone-500" />
                                        Hueso (kg)
                                    </label>
                                    <input
                                        type="number"
                                        value={data.peso_hueso}
                                        onChange={(e) => setData('peso_hueso', e.target.value)}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                                        placeholder="0.00"
                                        step="0.01"
                                        min="0"
                                        disabled={processing}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        <Package className="w-4 h-4 inline mr-2 text-green-500" />
                                        Vísceras (kg)
                                    </label>
                                    <input
                                        type="number"
                                        value={data.peso_visceras}
                                        onChange={(e) => setData('peso_visceras', e.target.value)}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                                        placeholder="0.00"
                                        step="0.01"
                                        min="0"
                                        disabled={processing}
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Cálculos Automáticos */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {data.peso_canal && data.peso_carne && (
                                <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                                    <div className="flex items-center justify-between">
                                        <span className="font-semibold text-green-800">Rendimiento Carne:</span>
                                        <span className="text-xl font-bold text-green-600">
                                            {calcularRendimiento()}%
                                        </span>
                                    </div>
                                    <p className="text-sm text-green-600 mt-1">
                                        {(calcularRendimiento() > 60 ? 'Excelente' : 
                                          calcularRendimiento() > 50 ? 'Bueno' : 'Regular')}
                                    </p>
                                </div>
                            )}
                            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div className="flex items-center justify-between">
                                    <span className="font-semibold text-blue-800">Total Subproductos:</span>
                                    <span className="text-xl font-bold text-blue-600">
                                        {calcularTotalSubproductos()} kg
                                    </span>
                                </div>
                                {data.peso_canal && (
                                    <p className="text-sm text-blue-600 mt-1">
                                        {((parseFloat(calcularTotalSubproductos()) / parseFloat(data.peso_canal)) * 100).toFixed(1)}% del canal
                                    </p>
                                )}
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
                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                                placeholder="Observaciones sobre la faena, calidad de la carne, cortes específicos, etc."
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
                            className="px-6 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 flex items-center gap-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
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
                                    Registrar Faena
                                </>
                            )}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}