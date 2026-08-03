import React, { useEffect, useState } from 'react';
import { X, LogOut, AlertTriangle } from 'lucide-react';
import { useForm } from '@inertiajs/react';

export default function BajaModal({ show, onClose, animalesActivos = [], tipos = {}, tiposConPrecio = [] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        animal_id: '',
        fecha: new Date().toISOString().split('T')[0],
        tipo_salida: 'venta',
        causa: '',
        diagnostico: '',
        precio_salida: '',
        observaciones: '',
        documento: null,
    });

    const [aviso, setAviso] = useState('');

    useEffect(() => {
        if (show) {
            reset();
            setAviso('');
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [show]);

    const aceptaPrecio = tiposConPrecio.includes(data.tipo_salida);
    const esFallecimiento = data.tipo_salida === 'fallecimiento';

    const handleSubmit = (e) => {
        e.preventDefault();

        if (!data.animal_id) {
            setAviso('Selecciona el ejemplar que sale del rebaño.');
            return;
        }

        post(route('bajas.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    };

    if (!show) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div className="bg-white rounded-lg max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div className="flex justify-between items-center border-b p-6">
                    <div className="flex items-center gap-3">
                        <LogOut className="w-6 h-6 text-red-600" />
                        <div>
                            <h2 className="text-xl font-bold text-gray-800">Registrar salida del rebaño</h2>
                            <p className="text-xs text-gray-500">
                                El historial del ejemplar se conserva completo.
                            </p>
                        </div>
                    </div>
                    <button onClick={onClose} className="text-gray-400 hover:text-gray-600" aria-label="Cerrar">
                        <X className="w-6 h-6" />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="p-6 space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Ejemplar *</label>
                        <select
                            value={data.animal_id}
                            onChange={(e) => setData('animal_id', e.target.value)}
                            className={`w-full border rounded-lg px-3 py-2 ${errors.animal_id ? 'border-red-300' : 'border-gray-300'}`}
                            required
                            disabled={processing}
                        >
                            <option value="">Selecciona un ejemplar...</option>
                            {animalesActivos.map((a) => (
                                <option key={a.id} value={a.id}>
                                    {a.arete}{a.alias ? ` — ${a.alias}` : ''}
                                </option>
                            ))}
                        </select>
                        {errors.animal_id && <p className="mt-1 text-sm text-red-600">{errors.animal_id}</p>}
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Tipo de salida *</label>
                            <select
                                value={data.tipo_salida}
                                onChange={(e) => setData('tipo_salida', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                disabled={processing}
                            >
                                {Object.entries(tipos).map(([valor, etiqueta]) => (
                                    <option key={valor} value={valor}>{etiqueta}</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                            <input
                                type="date"
                                value={data.fecha}
                                onChange={(e) => setData('fecha', e.target.value)}
                                className={`w-full border rounded-lg px-3 py-2 ${errors.fecha ? 'border-red-300' : 'border-gray-300'}`}
                                required
                                disabled={processing}
                            />
                            {errors.fecha && <p className="mt-1 text-sm text-red-600">{errors.fecha}</p>}
                        </div>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Causa</label>
                        <input
                            type="text"
                            value={data.causa}
                            onChange={(e) => setData('causa', e.target.value)}
                            placeholder="Ej: neumonía, edad avanzada, baja fertilidad…"
                            className="w-full border border-gray-300 rounded-lg px-3 py-2"
                            disabled={processing}
                        />
                    </div>

                    {esFallecimiento && (
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Diagnóstico
                            </label>
                            <textarea
                                value={data.diagnostico}
                                onChange={(e) => setData('diagnostico', e.target.value)}
                                rows="2"
                                placeholder="Hallazgos del veterinario, si los hubo…"
                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                disabled={processing}
                            />
                        </div>
                    )}

                    {aceptaPrecio && (
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Precio de salida (MXN)
                            </label>
                            <input
                                type="number"
                                value={data.precio_salida}
                                onChange={(e) => setData('precio_salida', e.target.value)}
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                disabled={processing}
                            />
                        </div>
                    )}

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                        <textarea
                            value={data.observaciones}
                            onChange={(e) => setData('observaciones', e.target.value)}
                            rows="2"
                            className="w-full border border-gray-300 rounded-lg px-3 py-2"
                            disabled={processing}
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Evidencia o comprobante <span className="text-gray-400">(PDF o imagen, máx. 5 MB)</span>
                        </label>
                        <input
                            type="file"
                            accept=".pdf,.jpg,.jpeg,.png"
                            onChange={(e) => setData('documento', e.target.files[0] ?? null)}
                            className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                            disabled={processing}
                        />
                        {errors.documento && <p className="mt-1 text-sm text-red-600">{errors.documento}</p>}
                    </div>

                    <div className="bg-amber-50 border border-amber-200 rounded-lg p-3 flex gap-2 text-xs text-amber-800">
                        <AlertTriangle className="w-4 h-4 shrink-0 mt-0.5" />
                        <span>
                            El ejemplar dejará de contar como activo, pero sus pesajes, sanidad,
                            costos, valuación y genealogía se conservan.
                        </span>
                    </div>

                    {aviso && <p className="text-sm text-red-600">{aviso}</p>}

                    <div className="flex justify-end gap-3 pt-2 border-t">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-6 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50"
                            disabled={processing}
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            className="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50"
                            disabled={processing}
                        >
                            {processing ? 'Registrando...' : 'Registrar salida'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
