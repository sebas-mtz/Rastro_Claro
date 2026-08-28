import React, { useState, useEffect } from 'react';
import { X, BadgeCheck } from 'lucide-react';
import { useForm } from '@inertiajs/react';
import { formatMXN } from '@/utils/currency';

export default function ConfirmarVentaModal({ show, onClose, animal, valuacion }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        precio_real_venta: '',
        fecha_venta: new Date().toISOString().split('T')[0],
        observaciones: '',
    });

    const [avisoLocal, setAvisoLocal] = useState('');

    useEffect(() => {
        if (show) {
            reset();
            setAvisoLocal('');
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [show]);

    if (!show) return null;

    const costoProduccion = Number(valuacion?.costo_total_produccion ?? 0);
    const precioReal = parseFloat(data.precio_real_venta);
    const utilidad = !isNaN(precioReal) ? precioReal - costoProduccion : null;
    const porcentaje = utilidad != null && costoProduccion > 0
        ? (utilidad / costoProduccion) * 100
        : null;

    const handleSubmit = (e) => {
        e.preventDefault();

        if (!data.precio_real_venta || isNaN(precioReal) || precioReal < 0) {
            setAvisoLocal('Captura el precio real de venta.');
            return;
        }

        if (animal.fecha_nac && data.fecha_venta < animal.fecha_nac.substring(0, 10)) {
            setAvisoLocal('La fecha de venta no puede ser anterior al nacimiento del animal.');
            return;
        }

        post(route('valuaciones.confirmar-venta', animal.id), {
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div className="bg-white rounded-lg max-w-md w-full max-h-[90vh] overflow-y-auto">
                <div className="flex justify-between items-center border-b p-6">
                    <div className="flex items-center gap-3">
                        <BadgeCheck className="w-6 h-6 text-emerald-600" />
                        <h2 className="text-xl font-bold text-gray-800">Confirmar precio de venta</h2>
                    </div>
                    <button onClick={onClose} className="text-gray-400 hover:text-gray-600" aria-label="Cerrar">
                        <X className="w-6 h-6" />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="p-6 space-y-4">
                    <div className="bg-slate-50 border border-slate-200 rounded-lg p-3 space-y-1 text-sm">
                        <div className="flex justify-between">
                            <span className="text-slate-600">Precio estimado</span>
                            <span className="font-semibold tabular-nums">
                                {formatMXN(valuacion?.precio_estimado)}
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-slate-600">Costo de producción</span>
                            <span className="font-semibold tabular-nums">{formatMXN(costoProduccion)}</span>
                        </div>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Precio real de venta (MXN) *
                        </label>
                        <input
                            type="number"
                            value={data.precio_real_venta}
                            onChange={(e) => setData('precio_real_venta', e.target.value)}
                            step="0.01"
                            min="0"
                            required
                            autoFocus
                            className={`w-full border rounded-lg px-3 py-2 ${errors.precio_real_venta ? 'border-red-300' : 'border-gray-300'}`}
                            disabled={processing}
                        />
                        {errors.precio_real_venta && (
                            <p className="mt-1 text-sm text-red-600">{errors.precio_real_venta}</p>
                        )}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            Fecha de venta *
                        </label>
                        <input
                            type="date"
                            value={data.fecha_venta}
                            onChange={(e) => setData('fecha_venta', e.target.value)}
                            required
                            className={`w-full border rounded-lg px-3 py-2 ${errors.fecha_venta ? 'border-red-300' : 'border-gray-300'}`}
                            disabled={processing}
                        />
                        {errors.fecha_venta && (
                            <p className="mt-1 text-sm text-red-600">{errors.fecha_venta}</p>
                        )}
                    </div>

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

                    {utilidad != null && (
                        <div className={`rounded-lg p-3 text-sm ${utilidad >= 0 ? 'bg-emerald-50 border border-emerald-200' : 'bg-red-50 border border-red-200'}`}>
                            <div className="flex justify-between">
                                <span className={utilidad >= 0 ? 'text-emerald-800' : 'text-red-800'}>
                                    {utilidad >= 0 ? 'Utilidad' : 'Pérdida'}
                                </span>
                                <span className={`font-bold tabular-nums ${utilidad >= 0 ? 'text-emerald-700' : 'text-red-700'}`}>
                                    {formatMXN(utilidad)}
                                    {porcentaje != null && ` (${porcentaje.toFixed(2)} %)`}
                                </span>
                            </div>
                            {costoProduccion <= 0 && (
                                <p className="text-xs text-slate-500 mt-1">
                                    El costo de producción es cero, así que no se calcula porcentaje.
                                </p>
                            )}
                        </div>
                    )}

                    {avisoLocal && (
                        <p className="text-sm text-red-600">{avisoLocal}</p>
                    )}

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
                            className="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50"
                            disabled={processing}
                        >
                            {processing ? 'Guardando...' : 'Confirmar venta'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
