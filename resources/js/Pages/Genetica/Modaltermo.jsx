import { useEffect, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { X, FlaskConical } from 'lucide-react';

const EMPTY = {
    codigo: '',
    nombre: '',
    ubicacion: '',
    capacidad: '',
    estado: 'activo',
    descripcion: '',
};

function Field({ label, error, children }) {
    return (
        <div className="flex flex-col gap-1">
            <label className="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                {label}
            </label>
            {children}
            {error && <p className="text-xs text-red-500">{error}</p>}
        </div>
    );
}

const inputCls = (err) =>
    `w-full rounded-lg border px-3 py-2 text-sm text-gray-800 bg-white outline-none transition
    focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400
    ${err ? 'border-red-300' : 'border-gray-200 hover:border-gray-300'}`;

export default function ModalTermo({ isOpen, onClose, termo = null }) {
    const { errors } = usePage().props;
    const isEdit = !!termo;

    const [form, setForm] = useState(EMPTY);
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        if (isOpen) {
            setForm(
                termo
                    ? {
                          codigo: termo.codigo ?? '',
                          nombre: termo.nombre ?? '',
                          ubicacion: termo.ubicacion ?? '',
                          capacidad: termo.capacidad ?? '',
                          estado: termo.estado ?? 'activo',
                          descripcion: termo.descripcion ?? '',
                      }
                    : EMPTY,
            );
        }
    }, [isOpen, termo]);

    useEffect(() => {
        const handler = (e) => e.key === 'Escape' && onClose();
        document.addEventListener('keydown', handler);
        return () => document.removeEventListener('keydown', handler);
    }, [onClose]);

    if (!isOpen) return null;

    const set = (field) => (e) => setForm((f) => ({ ...f, [field]: e.target.value }));

    function handleSubmit(e) {
        e.preventDefault();
        setProcessing(true);
        const opts = {
            onFinish: () => setProcessing(false),
            onSuccess: onClose,
            preserveScroll: true,
        };
        if (isEdit) router.put(route('termos.update', termo.id), form, opts);
        else router.post(route('termos.store'), form, opts);
    }

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div className="absolute inset-0 bg-black/40 backdrop-blur-sm" onClick={onClose} />

            <div className="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg flex flex-col max-h-[90vh]">
                {/* Header */}
                <div className="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div className="flex items-center gap-2">
                        <FlaskConical className="w-5 h-5 text-blue-600" />
                        <h2 className="text-base font-semibold text-gray-800">
                            {isEdit ? `Editar termo ${termo.codigo}` : 'Nuevo termo'}
                        </h2>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition"
                    >
                        <X className="w-4 h-4" />
                    </button>
                </div>

                {/* Body */}
                <form onSubmit={handleSubmit} className="overflow-y-auto flex-1 px-6 py-5">
                    <div className="grid grid-cols-2 gap-4">
                        <Field label="Código *" error={errors?.codigo}>
                            <input
                                className={inputCls(errors?.codigo)}
                                value={form.codigo}
                                onChange={set('codigo')}
                                placeholder="T-001"
                            />
                        </Field>

                        <Field label="Nombre" error={errors?.nombre}>
                            <input
                                className={inputCls(errors?.nombre)}
                                value={form.nombre}
                                onChange={set('nombre')}
                                placeholder="Termo principal"
                            />
                        </Field>

                        <Field label="Ubicación" error={errors?.ubicacion}>
                            <input
                                className={inputCls(errors?.ubicacion)}
                                value={form.ubicacion}
                                onChange={set('ubicacion')}
                                placeholder="Almacén A"
                            />
                        </Field>

                        <Field label="Capacidad" error={errors?.capacidad}>
                            <input
                                type="number"
                                min="1"
                                className={inputCls(errors?.capacidad)}
                                value={form.capacidad}
                                onChange={set('capacidad')}
                                placeholder="500"
                            />
                        </Field>

                        <Field label="Estado *" error={errors?.estado}>
                            <select
                                className={inputCls(errors?.estado)}
                                value={form.estado}
                                onChange={set('estado')}
                            >
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                                <option value="mantenimiento">Mantenimiento</option>
                            </select>
                        </Field>

                        <div className="col-span-2">
                            <Field label="Descripción" error={errors?.descripcion}>
                                <textarea
                                    rows={3}
                                    className={`${inputCls(errors?.descripcion)} resize-none`}
                                    value={form.descripcion}
                                    onChange={set('descripcion')}
                                    placeholder="Notas adicionales…"
                                />
                            </Field>
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="flex justify-end gap-2 mt-6">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-4 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-60 transition"
                        >
                            {processing ? 'Guardando…' : isEdit ? 'Actualizar' : 'Guardar'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}