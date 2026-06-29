import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';

import {
    AlarmClock,
    CalendarClock,
    ClipboardList,
    Save,
    UserRound,
    X,
} from 'lucide-react';

function convertirAFormatoInput(fecha) {
    if (!fecha) {
        return '';
    }

    const fechaObjeto = new Date(fecha);

    if (Number.isNaN(fechaObjeto.getTime())) {
        return '';
    }

    const ajustar = new Date(
        fechaObjeto.getTime() - fechaObjeto.getTimezoneOffset() * 60000,
    );

    return ajustar.toISOString().slice(0, 16);
}

function obtenerFechaMinima() {
    const fecha = new Date();
    fecha.setMinutes(fecha.getMinutes() - fecha.getTimezoneOffset());

    return fecha.toISOString().slice(0, 16);
}

function MensajeError({ children }) {
    if (!children) {
        return null;
    }

    return <p className="mt-1.5 text-xs font-medium text-red-600">{children}</p>;
}

export default function ModalTarea({
    open,
    onClose,
    usuarios = [],
    tarea = null,
}) {
    const esEdicion = Boolean(tarea?.id);

    const {
        data,
        setData,
        post,
        put,
        processing,
        errors,
        reset,
        clearErrors,
    } = useForm({
        titulo: '',
        descripcion: '',
        asignado_a: '',
        fecha_recordatorio: '',
    });

    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();

        if (tarea) {
            setData({
                titulo: tarea.titulo ?? '',
                descripcion: tarea.descripcion ?? '',
                asignado_a: tarea.asignado_a
                    ? String(tarea.asignado_a)
                    : tarea.asignado?.id
                      ? String(tarea.asignado.id)
                      : '',
                fecha_recordatorio: convertirAFormatoInput(
                    tarea.fecha_recordatorio,
                ),
            });

            return;
        }

        reset();
    }, [open, tarea]);

    useEffect(() => {
        if (!open) {
            return undefined;
        }

        const cerrarConEscape = (event) => {
            if (event.key === 'Escape' && !processing) {
                cerrarModal();
            }
        };

        document.addEventListener('keydown', cerrarConEscape);
        document.body.style.overflow = 'hidden';

        return () => {
            document.removeEventListener('keydown', cerrarConEscape);
            document.body.style.overflow = '';
        };
    }, [open, processing]);

    const cerrarModal = () => {
        if (processing) {
            return;
        }

        clearErrors();
        reset();
        onClose();
    };

    const guardarTarea = (event) => {
        event.preventDefault();

        const opciones = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        };

        if (esEdicion) {
            put(route('tareas.update', tarea.id), opciones);
            return;
        }

        post(route('tareas.store'), opciones);
    };

    if (!open) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <button
                type="button"
                onClick={cerrarModal}
                className="absolute inset-0 bg-slate-950/50 backdrop-blur-[2px]"
                aria-label="Cerrar modal"
            />

            <div className="relative flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div className="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                    <div className="flex items-center gap-3">
                        <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-green-100 text-green-700">
                            <AlarmClock size={23} />
                        </div>

                        <div>
                            <h2 className="text-xl font-bold text-slate-900">
                                {esEdicion
                                    ? 'Editar tarea'
                                    : 'Asignar nueva tarea'}
                            </h2>

                            <p className="mt-0.5 text-sm text-slate-500">
                                {esEdicion
                                    ? 'Modifica la información del recordatorio.'
                                    : 'Programa una actividad para un usuario.'}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        onClick={cerrarModal}
                        disabled={processing}
                        className="rounded-xl p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 disabled:opacity-50"
                        aria-label="Cerrar"
                    >
                        <X size={21} />
                    </button>
                </div>

                <form
                    id="form-tarea"
                    onSubmit={guardarTarea}
                    className="overflow-y-auto"
                >
                    <div className="space-y-5 p-6">
                        <div>
                            <label
                                htmlFor="titulo"
                                className="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-800"
                            >
                                <ClipboardList
                                    size={17}
                                    className="text-slate-500"
                                />
                                Título de la tarea
                                <span className="text-red-500">*</span>
                            </label>

                            <input
                                id="titulo"
                                type="text"
                                value={data.titulo}
                                onChange={(event) =>
                                    setData('titulo', event.target.value)
                                }
                                maxLength={150}
                                placeholder="Ej. Aplicar vacuna al lote 3"
                                className={`w-full rounded-xl border px-4 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:ring-4 ${
                                    errors.titulo
                                        ? 'border-red-400 focus:border-red-500 focus:ring-red-100'
                                        : 'border-slate-300 focus:border-green-500 focus:ring-green-100'
                                }`}
                            />

                            <div className="mt-1 flex items-start justify-between gap-4">
                                <MensajeError>
                                    {errors.titulo}
                                </MensajeError>

                                <span className="ml-auto text-xs text-slate-400">
                                    {data.titulo.length}/150
                                </span>
                            </div>
                        </div>

                        <div>
                            <label
                                htmlFor="descripcion"
                                className="mb-2 block text-sm font-semibold text-slate-800"
                            >
                                Descripción
                            </label>

                            <textarea
                                id="descripcion"
                                value={data.descripcion}
                                onChange={(event) =>
                                    setData('descripcion', event.target.value)
                                }
                                rows={4}
                                maxLength={1000}
                                placeholder="Agrega instrucciones o información adicional..."
                                className={`w-full resize-none rounded-xl border px-4 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:ring-4 ${
                                    errors.descripcion
                                        ? 'border-red-400 focus:border-red-500 focus:ring-red-100'
                                        : 'border-slate-300 focus:border-green-500 focus:ring-green-100'
                                }`}
                            />

                            <div className="mt-1 flex items-start justify-between gap-4">
                                <MensajeError>
                                    {errors.descripcion}
                                </MensajeError>

                                <span className="ml-auto text-xs text-slate-400">
                                    {data.descripcion.length}/1000
                                </span>
                            </div>
                        </div>

                        <div className="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label
                                    htmlFor="asignado_a"
                                    className="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-800"
                                >
                                    <UserRound
                                        size={17}
                                        className="text-slate-500"
                                    />
                                    Asignar a
                                    <span className="text-red-500">*</span>
                                </label>

                                <select
                                    id="asignado_a"
                                    value={data.asignado_a}
                                    onChange={(event) =>
                                        setData(
                                            'asignado_a',
                                            event.target.value,
                                        )
                                    }
                                    className={`w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:ring-4 ${
                                        errors.asignado_a
                                            ? 'border-red-400 focus:border-red-500 focus:ring-red-100'
                                            : 'border-slate-300 focus:border-green-500 focus:ring-green-100'
                                    }`}
                                >
                                    <option value="">
                                        Selecciona un usuario
                                    </option>

                                    {usuarios.map((usuario) => (
                                        <option
                                            key={usuario.id}
                                            value={usuario.id}
                                        >
                                            {usuario.name}
                                            {usuario.email
                                                ? ` — ${usuario.email}`
                                                : ''}
                                        </option>
                                    ))}
                                </select>

                                <MensajeError>
                                    {errors.asignado_a}
                                </MensajeError>
                            </div>

                            <div>
                                <label
                                    htmlFor="fecha_recordatorio"
                                    className="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-800"
                                >
                                    <CalendarClock
                                        size={17}
                                        className="text-slate-500"
                                    />
                                    Fecha y hora
                                    <span className="text-red-500">*</span>
                                </label>

                                <input
                                    id="fecha_recordatorio"
                                    type="datetime-local"
                                    value={data.fecha_recordatorio}
                                    min={
                                        esEdicion
                                            ? undefined
                                            : obtenerFechaMinima()
                                    }
                                    onChange={(event) =>
                                        setData(
                                            'fecha_recordatorio',
                                            event.target.value,
                                        )
                                    }
                                    className={`w-full rounded-xl border px-4 py-3 text-sm text-slate-800 outline-none transition focus:ring-4 ${
                                        errors.fecha_recordatorio
                                            ? 'border-red-400 focus:border-red-500 focus:ring-red-100'
                                            : 'border-slate-300 focus:border-green-500 focus:ring-green-100'
                                    }`}
                                />

                                <MensajeError>
                                    {errors.fecha_recordatorio}
                                </MensajeError>
                            </div>
                        </div>

                        <div className="rounded-xl border border-blue-100 bg-blue-50 p-4">
                            <div className="flex items-start gap-3">
                                <CalendarClock
                                    size={19}
                                    className="mt-0.5 shrink-0 text-blue-600"
                                />

                                <div>
                                    <p className="text-sm font-semibold text-blue-800">
                                        Recordatorio automático
                                    </p>

                                    <p className="mt-1 text-xs leading-5 text-blue-700">
                                        El usuario seleccionado recibirá una
                                        notificación cuando llegue la fecha y
                                        hora programada.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <div className="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        onClick={cerrarModal}
                        disabled={processing}
                        className="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        form="form-tarea"
                        disabled={processing}
                        className="inline-flex items-center justify-center gap-2 rounded-xl bg-green-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-100 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <Save size={18} />

                        {processing
                            ? 'Guardando...'
                            : esEdicion
                              ? 'Guardar cambios'
                              : 'Asignar tarea'}
                    </button>
                </div>
            </div>
        </div>
    );
}