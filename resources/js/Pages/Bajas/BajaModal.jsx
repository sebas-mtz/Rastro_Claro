import { useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { X, Loader2 } from 'lucide-react';

export default function BajaModal({ show, onClose, animalesActivos = [], tipos = {}, tiposConPrecio = [] }) {
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        animal_id: '',
        fecha: new Date().toISOString().slice(0, 10),
        tipo_salida: '',
        causa: '',
        diagnostico: '',
        precio_salida: '',
        observaciones: '',
        documento: null,
    });

    useEffect(() => {
        if (show) {
            reset();
            clearErrors();
        }
    }, [show]);

    if (!show) return null;

    const mostrarPrecio = tiposConPrecio.includes(data.tipo_salida);

    const submit = (e) => {
        e.preventDefault();
        post(route('bajas.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div className="bg-white rounded-2xl border border-slate-200 w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div className="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                    <h2 className="text-lg font-bold text-slate-800">Registrar salida del rebaño</h2>
                    <button onClick={onClose} className="text-slate-400 hover:text-slate-600">
                        <X className="w-5 h-5" />
                    </button>
                </div>

                <form onSubmit={submit} className="px-6 py-4 space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">Ejemplar</label>
                        <select
                            value={data.animal_id}
                            onChange={(e) => setData('animal_id', e.target.value)}
                            className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-700"
                        >
                            <option value="">Selecciona un ejemplar</option>
                            {animalesActivos.map((a) => (
                                <option key={a.id} value={a.id}>
                                    {a.arete}{a.alias ? ` — ${a.alias}` : ''}
                                </option>
                            ))}
                        </select>
                        {errors.animal_id && <p className="text-xs text-red-600 mt-1">{errors.animal_id}</p>}
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Fecha</label>
                            <input
                                type="date"
                                value={data.fecha}
                                max={new Date().toISOString().slice(0, 10)}
                                onChange={(e) => setData('fecha', e.target.value)}
                                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-700"
                            />
                            {errors.fecha && <p className="text-xs text-red-600 mt-1">{errors.fecha}</p>}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Tipo de salida</label>
                            <select
                                value={data.tipo_salida}
                                onChange={(e) => setData('tipo_salida', e.target.value)}
                                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-700"
                            >
                                <option value="">Selecciona un tipo</option>
                                {Object.entries(tipos).map(([valor, etiqueta]) => (
                                    <option key={valor} value={valor}>{etiqueta}</option>
                                ))}
                            </select>
                            {errors.tipo_salida && <p className="text-xs text-red-600 mt-1">{errors.tipo_salida}</p>}
                        </div>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">Causa</label>
                        <input
                            type="text"
                            value={data.causa}
                            onChange={(e) => setData('causa', e.target.value)}
                            className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-700"
                            placeholder="Ej. Neumonía, robo reportado, donado a..."
                        />
                        {errors.causa && <p className="text-xs text-red-600 mt-1">{errors.causa}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">Diagnóstico (opcional)</label>
                        <textarea
                            value={data.diagnostico}
                            onChange={(e) => setData('diagnostico', e.target.value)}
                            rows={2}
                            className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-700"
                        />
                        {errors.diagnostico && <p className="text-xs text-red-600 mt-1">{errors.diagnostico}</p>}
                    </div>

                    {mostrarPrecio && (
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">
                                Precio / valor estimado
                            </label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                value={data.precio_salida}
                                onChange={(e) => setData('precio_salida', e.target.value)}
                                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-700"
                            />
                            {errors.precio_salida && <p className="text-xs text-red-600 mt-1">{errors.precio_salida}</p>}
                        </div>
                    )}

                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">Observaciones</label>
                        <textarea
                            value={data.observaciones}
                            onChange={(e) => setData('observaciones', e.target.value)}
                            rows={2}
                            className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-700"
                        />
                        {errors.observaciones && <p className="text-xs text-red-600 mt-1">{errors.observaciones}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">
                            Documento de respaldo (opcional)
                        </label>
                        <input
                            type="file"
                            accept=".pdf,.jpg,.jpeg,.png"
                            onChange={(e) => setData('documento', e.target.files[0])}
                            className="w-full text-sm text-slate-600"
                        />
                        {errors.documento && <p className="text-xs text-red-600 mt-1">{errors.documento}</p>}
                    </div>

                    <div className="flex justify-end gap-3 pt-2 border-t border-slate-200">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white font-medium px-4 py-2 rounded-lg flex items-center gap-2 text-sm transition"
                        >
                            {processing && <Loader2 className="w-4 h-4 animate-spin" />}
                            {processing ? 'Guardando...' : 'Registrar salida'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}