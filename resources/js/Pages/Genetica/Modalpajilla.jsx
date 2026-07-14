import { useEffect, useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { X, Droplets, Search } from 'lucide-react';

const EMPTY = {
    termo_id: '',
    origen: 'interno',
    animal_id: '',
    donador_externo_id: '',
    codigo_inicial: '',
    cantidad: 1,
    codigo: '',
    lote: '',
    fecha_vencimiento: '',
    estado: 'disponible',
    observaciones: '',
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

export default function ModalPajilla({
    isOpen,
    onClose,
    pajilla = null,
    termos = [],
    animales = [],
    donadoresExternos = [],
    onAgregarDonadorExterno,
}) {
    const { errors } = usePage().props;
    const isEdit = !!pajilla;

    const [form, setForm] = useState(EMPTY);
    const [processing, setProcessing] = useState(false);
    const [busquedaAnimal, setBusquedaAnimal] = useState('');

    const animalesFiltrados = useMemo(() => {
        const texto = busquedaAnimal.toLowerCase().trim();

        if (!texto) return animales;

        return animales.filter((animal) => {
            const nombre = String(animal.nombre ?? '').toLowerCase();
            const arete = String(animal.arete ?? '').toLowerCase();

            return nombre.includes(texto) || arete.includes(texto);
        });
    }, [animales, busquedaAnimal]);

    useEffect(() => {
        if (isOpen) {
            if (pajilla) {
                setForm({
                    termo_id: pajilla.termo_id ?? '',
                    origen: pajilla.donador_externo_id ? 'externo' : 'interno',
                    animal_id: pajilla.animal_id ?? '',
                    donador_externo_id: pajilla.donador_externo_id ?? '',
                    codigo_inicial: '',
                    cantidad: 1,
                    codigo: pajilla.codigo ?? '',
                    lote: pajilla.lote ?? '',
                    fecha_vencimiento: pajilla.fecha_vencimiento?.slice(0, 10) ?? '',
                    estado: pajilla.estado ?? 'disponible',
                    observaciones: pajilla.observaciones ?? '',
                });
            } else {
                setForm(EMPTY);
            }

            setBusquedaAnimal('');
        }
    }, [isOpen, pajilla]);

    useEffect(() => {
        const handler = (e) => e.key === 'Escape' && onClose();
        document.addEventListener('keydown', handler);

        return () => document.removeEventListener('keydown', handler);
    }, [onClose]);

    if (!isOpen) return null;

    const set = (field) => (e) => {
        setForm((current) => ({
            ...current,
            [field]: e.target.value,
        }));
    };

    const cambiarOrigen = (e) => {
        const origen = e.target.value;

        setForm((current) => ({
            ...current,
            origen,
            animal_id: '',
            donador_externo_id: '',
        }));

        setBusquedaAnimal('');
    };

    function handleSubmit(e) {
        e.preventDefault();

        setProcessing(true);

        const payload = {
            termo_id: form.termo_id,
            origen: form.origen,
            animal_id: form.origen === 'interno' ? form.animal_id : '',
            donador_externo_id: form.origen === 'externo' ? form.donador_externo_id : '',
            lote: form.lote,
            fecha_vencimiento: form.fecha_vencimiento,
            observaciones: form.observaciones,
        };

        if (isEdit) {
            payload.codigo = form.codigo;
            payload.estado = form.estado;
        } else {
            payload.codigo_inicial = form.codigo_inicial;
            payload.cantidad = form.cantidad;
            payload.estado = 'disponible';
        }

        const opts = {
            onFinish: () => setProcessing(false),
            onSuccess: onClose,
            preserveScroll: true,
        };

        if (isEdit) {
            router.put(route('pajillas.update', pajilla.id), payload, opts);
        } else {
            router.post(route('pajillas.store'), payload, opts);
        }
    }

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div
                className="absolute inset-0 bg-black/40 backdrop-blur-sm"
                onClick={onClose}
            />

            <div className="relative flex max-h-[90vh] w-full max-w-2xl flex-col rounded-2xl bg-white shadow-2xl">
                <div className="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <div className="flex items-center gap-2">
                        <Droplets className="h-5 w-5 text-cyan-600" />
                        <h2 className="text-base font-semibold text-gray-800">
                            {isEdit
                                ? `Editar pajilla ${pajilla.codigo}`
                                : 'Registrar pajillas'}
                        </h2>
                    </div>

                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                    >
                        <X className="h-4 w-4" />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="flex-1 overflow-y-auto px-6 py-5">
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <Field label="Termo *" error={errors?.termo_id}>
                            <select
                                className={inputCls(errors?.termo_id)}
                                value={form.termo_id}
                                onChange={set('termo_id')}
                            >
                                <option value="">Seleccionar termo…</option>

                                {termos.map((termo) => (
                                    <option key={termo.id} value={termo.id}>
                                        {termo.codigo}
                                        {termo.nombre ? ` — ${termo.nombre}` : ''}
                                    </option>
                                ))}
                            </select>
                        </Field>

                        <Field label="Origen de genética *" error={errors?.origen}>
                            <select
                                className={inputCls(errors?.origen)}
                                value={form.origen}
                                onChange={cambiarOrigen}
                            >
                                <option value="interno">Semental interno</option>
                                <option value="externo">Donador externo</option>
                            </select>
                        </Field>

                        {form.origen === 'interno' && (
                            <div className="md:col-span-2">
                                <Field label="Buscar Semental interno" error={errors?.animal_id}>
                                    <div className="relative">
                                        <Search className="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                                        <input
                                            type="text"
                                            className={`${inputCls(errors?.animal_id)} pl-9`}
                                            value={busquedaAnimal}
                                            onChange={(e) => setBusquedaAnimal(e.target.value)}
                                            placeholder="Buscar por nombre o arete..."
                                        />
                                    </div>
                                </Field>

                                <div className="mt-2 max-h-40 overflow-y-auto rounded-lg border border-gray-200 bg-white">
                                    {animalesFiltrados.length === 0 ? (
                                        <div className="px-3 py-3 text-sm text-gray-400">
                                            No se encontraron Sementales internos.
                                        </div>
                                    ) : (
                                        animalesFiltrados.map((animal) => (
                                            <button
                                                key={animal.id}
                                                type="button"
                                                onClick={() =>
                                                    setForm((current) => ({
                                                        ...current,
                                                        animal_id: animal.id,
                                                    }))
                                                }
                                                className={`flex w-full items-center justify-between px-3 py-2 text-left text-sm transition hover:bg-blue-50 ${
                                                    String(form.animal_id) === String(animal.id)
                                                        ? 'bg-blue-50 text-blue-700'
                                                        : 'text-gray-700'
                                                }`}
                                            >
                                                <span>
                                                    <span className="font-semibold">
                                                        {animal.nombre || `Animal #${animal.id}`}
                                                    </span>
                                                    <span className="ml-2 text-xs text-gray-400">
                                                        Arete: {animal.arete || 'Sin arete'}
                                                    </span>
                                                </span>

                                                {String(form.animal_id) === String(animal.id) && (
                                                    <span className="text-xs font-semibold text-blue-600">
                                                        Seleccionado
                                                    </span>
                                                )}
                                            </button>
                                        ))
                                    )}
                                </div>

                                {errors?.animal_id && (
                                    <p className="mt-1 text-xs text-red-500">
                                        {errors.animal_id}
                                    </p>
                                )}
                            </div>
                        )}

                        {form.origen === 'externo' && (
                            <div>
                                <Field
                                    label="Donador externo *"
                                    error={errors?.donador_externo_id}
                                >
                                    <select
                                        className={inputCls(errors?.donador_externo_id)}
                                        value={form.donador_externo_id}
                                        onChange={set('donador_externo_id')}
                                    >
                                        <option value="">Seleccionar donador…</option>

                                        {donadoresExternos.map((donador) => (
                                            <option key={donador.id} value={donador.id}>
                                                {donador.codigo} — {donador.nombre}
                                            </option>
                                        ))}
                                    </select>
                                </Field>

                                <button
                                    type="button"
                                    onClick={onAgregarDonadorExterno}
                                    className="mt-2 text-sm font-medium text-blue-600 transition hover:text-blue-700 hover:underline"
                                >
                                    Agregar donador externo
                                </button>

                                {donadoresExternos.length === 0 && (
                                    <p className="mt-1 text-xs text-gray-400">
                                        Todavía no hay donadores externos registrados.
                                    </p>
                                )}
                            </div>
                        )}

                        {isEdit ? (
                            <Field label="Código *" error={errors?.codigo}>
                                <input
                                    className={inputCls(errors?.codigo)}
                                    value={form.codigo}
                                    onChange={set('codigo')}
                                    placeholder="PJ-0001"
                                />
                            </Field>
                        ) : (
                            <>
                                <Field label="Código inicial *" error={errors?.codigo_inicial}>
                                    <input
                                        className={inputCls(errors?.codigo_inicial)}
                                        value={form.codigo_inicial}
                                        onChange={set('codigo_inicial')}
                                        placeholder="PJ-0001"
                                    />
                                </Field>

                                <Field label="Cantidad *" error={errors?.cantidad}>
                                    <input
                                        type="number"
                                        min="1"
                                        className={inputCls(errors?.cantidad)}
                                        value={form.cantidad}
                                        onChange={set('cantidad')}
                                        placeholder="100"
                                    />
                                </Field>
                            </>
                        )}

                        <Field label="Lote" error={errors?.lote}>
                            <input
                                className={inputCls(errors?.lote)}
                                value={form.lote}
                                onChange={set('lote')}
                                placeholder="LOT-2026-A"
                            />
                        </Field>

                        <Field label="Fecha de vencimiento" error={errors?.fecha_vencimiento}>
                            <input
                                type="date"
                                className={inputCls(errors?.fecha_vencimiento)}
                                value={form.fecha_vencimiento}
                                onChange={set('fecha_vencimiento')}
                            />
                        </Field>

                        {isEdit && (
                            <Field label="Estado *" error={errors?.estado}>
                                <select
                                    className={inputCls(errors?.estado)}
                                    value={form.estado}
                                    onChange={set('estado')}
                                >
                                    <option value="disponible">Disponible</option>
                                    <option value="utilizada">Utilizada</option>
                                    <option value="dañada">Dañada</option>
                                    <option value="vencida">Vencida</option>
                                </select>
                            </Field>
                        )}

                        <div className="md:col-span-2">
                            <Field label="Observaciones" error={errors?.observaciones}>
                                <textarea
                                    rows={3}
                                    className={`${inputCls(errors?.observaciones)} resize-none`}
                                    value={form.observaciones}
                                    onChange={set('observaciones')}
                                    placeholder="Notas adicionales…"
                                />
                            </Field>
                        </div>
                    </div>

                    <div className="mt-6 flex justify-end gap-2">
                        <button
                            type="button"
                            onClick={onClose}
                            className="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100"
                        >
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700 disabled:opacity-60"
                        >
                            {processing
                                ? 'Guardando…'
                                : isEdit
                                  ? 'Actualizar'
                                  : 'Guardar pajillas'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}