import React, { useEffect } from 'react';
import { X, DollarSign } from 'lucide-react';
import { useForm } from '@inertiajs/react';

const CATEGORIAS = [
    { value: 'alimentacion', label: 'Alimentación' },
    { value: 'medicamentos', label: 'Medicamentos' },
    { value: 'vacunas', label: 'Vacunas' },
    { value: 'consultas_veterinarias', label: 'Consultas veterinarias' },
    { value: 'mano_obra', label: 'Mano de obra' },
    { value: 'transporte', label: 'Transporte' },
    { value: 'compra_animales', label: 'Compra de animales' },
    { value: 'mantenimiento', label: 'Mantenimiento' },
    { value: 'insumos', label: 'Insumos' },
    { value: 'faenas', label: 'Faenas' },
    { value: 'sacrificios', label: 'Sacrificios' },
    { value: 'servicios', label: 'Servicios' },
    { value: 'administrativos', label: 'Costos administrativos' },
    { value: 'otros', label: 'Otros gastos' },
];

const emptyForm = {
    concepto: '',
    descripcion: '',
    categoria: 'alimentacion',
    tipo_costo: 'directo',
    monto: '',
    cantidad: '',
    unidad_medida: '',
    fecha: new Date().toISOString().split('T')[0],
    animal_id: '',
    lote_id: '',
    faena_id: '',
    sacrificio_id: '',
    proveedor: '',
    numero_comprobante: '',
    observaciones: '',
};

export default function CostoModal({ show, onClose, costo, animales = [], lotes = [], faenas = [], sacrificios = [] }) {
    const { data, setData, post, put, processing, errors, reset } = useForm(emptyForm);
    const esEdicion = Boolean(costo);

    useEffect(() => {
        if (!show) return;

        if (costo) {
            setData({
                concepto: costo.concepto || '',
                descripcion: costo.descripcion || '',
                categoria: costo.categoria || 'alimentacion',
                tipo_costo: costo.tipo_costo || 'directo',
                monto: costo.monto ?? '',
                cantidad: costo.cantidad ?? '',
                unidad_medida: costo.unidad_medida || '',
                fecha: costo.fecha ? costo.fecha.substring(0, 10) : new Date().toISOString().split('T')[0],
                animal_id: costo.animal_id || '',
                lote_id: costo.lote_id || '',
                faena_id: costo.faena_id || '',
                sacrificio_id: costo.sacrificio_id || '',
                proveedor: costo.proveedor || '',
                numero_comprobante: costo.numero_comprobante || '',
                observaciones: costo.observaciones || '',
            });
        } else {
            reset();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [show, costo]);

    const handleClose = () => {
        reset();
        onClose();
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        if (!data.concepto.trim()) {
            alert('El concepto es obligatorio.');
            return;
        }

        const monto = parseFloat(data.monto);
        if (!data.monto || isNaN(monto) || monto <= 0) {
            alert('El monto debe ser un número mayor a 0.');
            return;
        }

        const opciones = {
            onSuccess: () => {
                reset();
                onClose();
            },
        };

        if (esEdicion) {
            put(route('costos.update', costo.id), opciones);
        } else {
            post(route('costos.store'), opciones);
        }
    };

    if (!show) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div className="bg-white rounded-lg max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                <div className="flex justify-between items-center border-b p-6">
                    <div className="flex items-center gap-3">
                        <DollarSign className="w-6 h-6 text-green-600" />
                        <h2 className="text-xl font-bold text-gray-800">
                            {esEdicion ? 'Editar Costo' : 'Registrar Costo'}
                        </h2>
                    </div>
                    <button onClick={handleClose} className="text-gray-400 hover:text-gray-600" disabled={processing}>
                        <X className="w-6 h-6" />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="p-6 space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 mb-1">Concepto *</label>
                            <input
                                type="text"
                                value={data.concepto}
                                onChange={(e) => setData('concepto', e.target.value)}
                                className={`w-full border rounded-lg px-3 py-2 ${errors.concepto ? 'border-red-300' : 'border-gray-300'}`}
                                placeholder="Ej. Compra de alimento balanceado"
                                required
                                disabled={processing}
                            />
                            {errors.concepto && <p className="mt-1 text-sm text-red-600">{errors.concepto}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Categoría *</label>
                            <select
                                value={data.categoria}
                                onChange={(e) => setData('categoria', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                disabled={processing}
                            >
                                {CATEGORIAS.map((c) => (
                                    <option key={c.value} value={c.value}>{c.label}</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Tipo de costo *</label>
                            <select
                                value={data.tipo_costo}
                                onChange={(e) => setData('tipo_costo', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                disabled={processing}
                            >
                                <option value="directo">Directo</option>
                                <option value="indirecto">Indirecto</option>
                            </select>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Monto (MXN) *</label>
                            <input
                                type="number"
                                value={data.monto}
                                onChange={(e) => setData('monto', e.target.value)}
                                className={`w-full border rounded-lg px-3 py-2 ${errors.monto ? 'border-red-300' : 'border-gray-300'}`}
                                placeholder="0.00"
                                step="0.01"
                                min="0.01"
                                required
                                disabled={processing}
                            />
                            {errors.monto && <p className="mt-1 text-sm text-red-600">{errors.monto}</p>}
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

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Cantidad</label>
                            <input
                                type="number"
                                value={data.cantidad}
                                onChange={(e) => setData('cantidad', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                placeholder="Opcional"
                                step="0.01"
                                min="0"
                                disabled={processing}
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Unidad de medida</label>
                            <input
                                type="text"
                                value={data.unidad_medida}
                                onChange={(e) => setData('unidad_medida', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                placeholder="kg, litros, dosis..."
                                disabled={processing}
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Animal relacionado</label>
                            <select
                                value={data.animal_id}
                                onChange={(e) => setData('animal_id', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                disabled={processing}
                            >
                                <option value="">Sin animal específico</option>
                                {animales.map((a) => (
                                    <option key={a.id} value={a.id}>{a.alias || a.arete}</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Lote relacionado</label>
                            <select
                                value={data.lote_id}
                                onChange={(e) => setData('lote_id', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                disabled={processing}
                            >
                                <option value="">Sin lote específico</option>
                                {lotes.map((l) => (
                                    <option key={l.id} value={l.id}>{l.nombre}</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Faena relacionada</label>
                            <select
                                value={data.faena_id}
                                onChange={(e) => setData('faena_id', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                disabled={processing}
                            >
                                <option value="">Sin faena específica</option>
                                {faenas.map((f) => (
                                    <option key={f.id} value={f.id}>Faena #{f.id} — {f.fecha}</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Sacrificio relacionado</label>
                            <select
                                value={data.sacrificio_id}
                                onChange={(e) => setData('sacrificio_id', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                disabled={processing}
                            >
                                <option value="">Sin sacrificio específico</option>
                                {sacrificios.map((s) => (
                                    <option key={s.id} value={s.id}>Sacrificio #{s.id} — {s.fecha}</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Proveedor</label>
                            <input
                                type="text"
                                value={data.proveedor}
                                onChange={(e) => setData('proveedor', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                disabled={processing}
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">N° de comprobante</label>
                            <input
                                type="text"
                                value={data.numero_comprobante}
                                onChange={(e) => setData('numero_comprobante', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                disabled={processing}
                            />
                        </div>

                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                            <textarea
                                value={data.descripcion}
                                onChange={(e) => setData('descripcion', e.target.value)}
                                rows="2"
                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                disabled={processing}
                            />
                        </div>

                        <div className="md:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                            <textarea
                                value={data.observaciones}
                                onChange={(e) => setData('observaciones', e.target.value)}
                                rows="2"
                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                disabled={processing}
                            />
                        </div>
                    </div>

                    <div className="flex justify-end gap-3 pt-4 border-t">
                        <button
                            type="button"
                            onClick={handleClose}
                            className="px-6 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50"
                            disabled={processing}
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            className="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled={processing}
                        >
                            {processing ? (
                                <>
                                    <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                    Guardando...
                                </>
                            ) : (
                                esEdicion ? 'Guardar cambios' : 'Registrar Costo'
                            )}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
