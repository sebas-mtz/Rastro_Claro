import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import ModalTarea from './ModalTarea';

import { AlarmClock, CalendarClock, CheckCircle2, ChevronLeft, ChevronRight, CircleAlert, Clock3, Edit3, Eye, ListTodo, MoreVertical, PauseCircle, PlayCircle, Plus, Search, Trash2, UserRound, X } from 'lucide-react';

const FILTROS_ESTADO = [ { key: '', label: 'Todas', }, { key: 'pendiente', label: 'Pendientes', }, { key: 'vencida', label: 'Vencidas', }, { key: 'suspendida', label: 'Suspendidas', }, { key: 'completada', label: 'Completadas', }, ];

const ESTADOS = {
    pendiente: {
        label: 'Pendiente',
        className: 'bg-amber-50 text-amber-700 ring-amber-200',
        icon: Clock3,
    },

    vencida: {
        label: 'Vencida',
        className: 'bg-red-50 text-red-700 ring-red-200',
        icon: CircleAlert,
    },

    suspendida: {
        label: 'Suspendida',
        className: 'bg-slate-100 text-slate-700 ring-slate-200',
        icon: PauseCircle,
    },

    completada: {
        label: 'Completada',
        className: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        icon: CheckCircle2,
    },
};

function obtenerEstadoVisual(tarea) {
    if (tarea.estado === 'pendiente' && tarea.esta_vencida) {
        return ESTADOS.vencida;
    }

    return ESTADOS[tarea.estado] ?? ESTADOS.pendiente;
}

function formatearFecha(fecha) {
    if (!fecha) {
        return 'Sin fecha';
    }

    return new Intl.DateTimeFormat('es-MX', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    }).format(new Date(fecha));
}

function TarjetaResumen({ titulo, cantidad, descripcion, icon: Icon, className, onClick }) {
    return (
        <button type="button" onClick={onClick} className={`group w-full rounded-2xl border-l-4 bg-white p-4 text-left shadow transition hover:-translate-y-0.5 hover:shadow-md ${className}`} >
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-sm font-medium text-gray-700">
                        {titulo}
                    </p>

                    <p className="mt-1 text-2xl font-bold text-gray-800">
                        {cantidad ?? 0}
                    </p>

                    <p className="mt-1 text-sm text-gray-500">
                        {descripcion}
                    </p>
                </div>

                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600" >
                    <Icon size={22} />
                </div>
            </div>
        </button>
    );
}

function EstadoTarea({ tarea }) {
    const estado = obtenerEstadoVisual(tarea);
    const Icon = estado.icon;

    return (
        <span className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${estado.className}`} >
            <Icon size={14} />
            {estado.label}
        </span>
    );
}

function MenuAcciones({ tarea, onEditar, onVer, onCompletar, onSuspender, onReactivar, onEliminar }) {
    const [abierto, setAbierto] = useState(false);

    const ejecutar = (callback) => {
        setAbierto(false);
        callback();
    };

    return (
        <div className="relative">
            <button type="button" onClick={() => setAbierto((valor) => !valor)} className="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Abrir acciones" >
                <MoreVertical size={19} />
            </button>

            {abierto && (
                <>
                    <button type="button" aria-label="Cerrar menú" onClick={() => setAbierto(false)} className="fixed inset-0 z-20 cursor-default" />

                    <div className="absolute right-0 z-30 mt-2 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-xl">
                        <button type="button" onClick={() => ejecutar(onVer)} className="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50" >
                            <Eye size={17} />
                            Ver información
                        </button>

                        {tarea.estado !== 'completada' && (
                            <button type="button" onClick={() => ejecutar(onEditar)} className="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50" >
                                <Edit3 size={17} />
                                Editar tarea
                            </button>
                        )}

                        {tarea.estado === 'pendiente' && (
                            <>
                                <button type="button" onClick={() => ejecutar(onCompletar)} className="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-emerald-700 hover:bg-emerald-50" >
                                    <CheckCircle2 size={17} />
                                    Completar
                                </button>

                                <button type="button" onClick={() => ejecutar(onSuspender)} className="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-amber-700 hover:bg-amber-50" >
                                    <PauseCircle size={17} />
                                    Suspender
                                </button>
                            </>
                        )}

                        {tarea.estado === 'suspendida' && (
                            <button type="button" onClick={() => ejecutar(onReactivar)} className="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-blue-700 hover:bg-blue-50" >
                                <PlayCircle size={17} />
                                Reactivar
                            </button>
                        )}

                        <div className="my-1 border-t border-slate-100" />

                        <button type="button" onClick={() => ejecutar(onEliminar)} className="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50" >
                            <Trash2 size={17} />
                            Eliminar
                        </button>
                    </div>
                </>
            )}
        </div>
    );
}

function DetalleTarea({ tarea, onClose }) {
    if (!tarea) {
        return null;
    }

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <button type="button" onClick={onClose} className="absolute inset-0 bg-slate-950/50 backdrop-blur-[2px]" aria-label="Cerrar detalle" />

            <div className="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div className="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-wide text-blue-600">
                            Información de la tarea
                        </p>

                        <h2 className="mt-1 text-xl font-bold text-slate-900">
                            {tarea.titulo}
                        </h2>
                    </div>

                    <button type="button" onClick={onClose} className="rounded-lg p-2 text-slate-500 hover:bg-slate-100" >
                        <X size={20} />
                    </button>
                </div>

                <div className="space-y-5 p-6">
                    <EstadoTarea tarea={tarea} />

                    <div>
                        <p className="text-sm font-semibold text-slate-800">
                            Descripción
                        </p>

                        <p className="mt-1 whitespace-pre-line text-sm leading-6 text-slate-600">
                            {tarea.descripcion || 'Esta tarea no tiene descripción.'}
                        </p>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="rounded-xl bg-slate-50 p-4">
                            <div className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <CalendarClock size={17} />
                                Fecha y hora
                            </div>

                            <p className="mt-2 text-sm text-slate-600">
                                {formatearFecha(tarea.fecha_recordatorio)}
                            </p>
                        </div>

                        <div className="rounded-xl bg-slate-50 p-4">
                            <div className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <UserRound size={17} />
                                Responsable
                            </div>

                            <p className="mt-2 text-sm text-slate-600">
                                {tarea.asignado?.name || 'Sin asignar'}
                            </p>
                        </div>
                    </div>

                    <div className="rounded-xl border border-slate-200 p-4">
                        <p className="text-sm font-semibold text-slate-700">
                            Asignada por
                        </p>

                        <p className="mt-1 text-sm text-slate-600">
                            {tarea.creador?.name || 'Usuario no disponible'}
                        </p>
                    </div>
                </div>

                <div className="flex justify-end border-t border-slate-200 bg-slate-50 px-6 py-4">
                    <button type="button" onClick={onClose} className="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700" >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    );
}

export default function Index({
    tareas,
    usuarios = [],
    resumen = {},
    filtros = {},
}) {
    const { flash = {} } = usePage().props;

    const [modalAbierto, setModalAbierto] = useState(false);
    const [tareaSeleccionada, setTareaSeleccionada] = useState(null);
    const [tareaDetalle, setTareaDetalle] = useState(null);

    const [buscar, setBuscar] = useState(filtros.buscar ?? '');
    const [estado, setEstado] = useState(filtros.estado ?? '');

    const registros = useMemo(() => tareas?.data ?? [], [tareas]);

    const abrirNuevaTarea = () => {
        setTareaSeleccionada(null);
        setModalAbierto(true);
    };

    const abrirEdicion = (tarea) => {
        setTareaSeleccionada(tarea);
        setModalAbierto(true);
    };

    const cerrarModal = () => {
        setModalAbierto(false);
        setTareaSeleccionada(null);
    };

    const aplicarFiltros = (nuevoEstado = estado, nuevaBusqueda = buscar) => {
        router.get(
            route('tareas.index'),
            {
                estado: nuevoEstado || undefined,
                buscar: nuevaBusqueda || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const cambiarEstadoFiltro = (nuevoEstado) => {
        setEstado(nuevoEstado);
        aplicarFiltros(nuevoEstado, buscar);
    };

    const buscarTareas = (event) => {
        event.preventDefault();
        aplicarFiltros(estado, buscar);
    };

    const limpiarBusqueda = () => {
        setBuscar('');
        aplicarFiltros(estado, '');
    };

    const completarTarea = (tarea) => {
        if (!window.confirm(`¿Deseas completar la tarea "${tarea.titulo}"?`)) {
            return;
        }

        router.patch(
            route('tareas.completar', tarea.id),
            {},
            {
                preserveScroll: true,
            },
        );
    };

    const suspenderTarea = (tarea) => {
        if (!window.confirm(`¿Deseas suspender la tarea "${tarea.titulo}"?`)) {
            return;
        }

        router.patch(
            route('tareas.suspender', tarea.id),
            {},
            {
                preserveScroll: true,
            },
        );
    };

    const reactivarTarea = (tarea) => {
        router.patch(
            route('tareas.reactivar', tarea.id),
            {},
            {
                preserveScroll: true,
            },
        );
    };

    const eliminarTarea = (tarea) => {
        if (
            !window.confirm(
                `¿Seguro que deseas eliminar la tarea "${tarea.titulo}"?`,
            )
        ) {
            return;
        }

        router.delete(route('tareas.destroy', tarea.id), {
            preserveScroll: true,
        });
    };

    useEffect(() => {
        setBuscar(filtros.buscar ?? '');
        setEstado(filtros.estado ?? '');
    }, [filtros.buscar, filtros.estado]);

    return (
        <AppLayout>
            <Head title="Tareas y recordatorios" />

            <div className="bg-gray-50/50">
                <div className="mx-auto max-w-7xl space-y-5 px-6 py-8">
                    {(flash.success || flash.error) && (
                        <div className={`mb-5 rounded-xl border px-4 py-3 text-sm font-medium ${ flash.success ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700' }`} >
                            {flash.success || flash.error}
                        </div>
                    )}

                    <section className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <div className="flex items-start gap-3">
                                <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                    <AlarmClock size={25} />
                                </div>

                                <div>
                                    <h1 className="text-2xl font-bold text-gray-800">
                                        Tareas y recordatorios
                                    </h1>

                                    <p className="mt-1 text-sm text-gray-600">
                                        Asigna actividades y da seguimiento a los
                                        pendientes del rancho.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <button type="button" onClick={abrirNuevaTarea} className="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100" >
                            <Plus size={19} />
                            Asignar tarea
                        </button>
                    </section>

                    <section className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <TarjetaResumen titulo="Pendientes" cantidad={resumen.pendientes} descripcion="Tareas por realizar" icon={Clock3} className="border-amber-500" onClick={() => cambiarEstadoFiltro('pendiente')} />

                        <TarjetaResumen titulo="Vencidas" cantidad={resumen.vencidas} descripcion="Requieren atención" icon={CircleAlert} className="border-red-500" onClick={() => cambiarEstadoFiltro('vencida')} />

                        <TarjetaResumen titulo="Suspendidas" cantidad={resumen.suspendidas} descripcion="Pausadas temporalmente" icon={PauseCircle} className="border-slate-400" onClick={() => cambiarEstadoFiltro('suspendida')} />

                        <TarjetaResumen titulo="Completadas" cantidad={resumen.completadas} descripcion="Actividades finalizadas" icon={CheckCircle2} className="border-emerald-500" onClick={() => cambiarEstadoFiltro('completada')} />
                    </section>

                    <section className="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow">
                        <div className="border-b border-gray-100 px-5 py-4">
                            <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div className="flex flex-wrap gap-2">
                                    {FILTROS_ESTADO.map((filtro) => (
                                        <button key={filtro.key} type="button" onClick={() => cambiarEstadoFiltro(filtro.key) } className={`rounded-lg px-4 py-2 text-sm font-medium transition ${ estado === filtro.key ? 'border-b-2 border-blue-600 bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600' }`} >
                                            {filtro.label}
                                        </button>
                                    ))}
                                </div>

                                <form onSubmit={buscarTareas} className="flex w-full gap-2 lg:max-w-sm" >
                                    <div className="relative flex-1">
                                        <Search size={18} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />

                                        <input type="search" value={buscar} onChange={(event) => setBuscar(event.target.value) } placeholder="Buscar tarea..." className="w-full rounded-lg border border-gray-300 py-2 pl-10 pr-10 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100" />

                                        {buscar && (
                                            <button type="button" onClick={limpiarBusqueda} className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700" >
                                                <X size={17} />
                                            </button>
                                        )}
                                    </div>

                                    <button type="submit" className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50" >
                                        Buscar
                                    </button>
                                </form>
                            </div>
                        </div>

                        {registros.length === 0 ? (
                            <div className="flex flex-col items-center justify-center px-5 py-14 text-center">
                                <div className="flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <ListTodo size={30} />
                                </div>

                                <h2 className="mt-5 text-lg font-bold text-slate-800">
                                    No se encontraron tareas
                                </h2>

                                <p className="mt-2 max-w-sm text-sm leading-6 text-slate-500">
                                    No hay tareas que coincidan con los filtros
                                    seleccionados.
                                </p>

                                <button type="button" onClick={abrirNuevaTarea} className="mt-5 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700" >
                                    <Plus size={18} />
                                    Crear primera tarea
                                </button>
                            </div>
                        ) : (
                            <>
                                <div className="hidden overflow-x-auto lg:block">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                                    Tarea
                                                </th>

                                                <th className="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                                    Responsable
                                                </th>

                                                <th className="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                                    Fecha y hora
                                                </th>

                                                <th className="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                                    Estado
                                                </th>

                                                <th className="px-6 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500">
                                                    Acciones
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody className="divide-y divide-gray-100 bg-white">
                                            {registros.map((tarea) => (
                                                <tr key={tarea.id} className="transition hover:bg-blue-50/40" >
                                                    <td className="max-w-sm px-5 py-3.5">
                                                        <button type="button" onClick={() => setTareaDetalle( tarea, ) } className="block text-left" >
                                                            <p className="font-semibold text-slate-900 hover:text-blue-700">
                                                                {tarea.titulo}
                                                            </p>

                                                            <p className="mt-1 line-clamp-1 text-sm text-slate-500">
                                                                {tarea.descripcion ||
                                                                    'Sin descripción'}
                                                            </p>
                                                        </button>
                                                    </td>

                                                    <td className="px-5 py-3.5">
                                                        <div className="flex items-start gap-3">
                                                            <div className="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-700">
                                                                {tarea.asignado?.name
                                                                    ?.charAt(0)
                                                                    ?.toUpperCase() ||
                                                                    '?'}
                                                            </div>

                                                            <div>
                                                                <p className="text-sm font-semibold text-slate-800">
                                                                    {tarea
                                                                        .asignado
                                                                        ?.name ||
                                                                        'Sin asignar'}
                                                                </p>

                                                                <p className="text-xs text-slate-500">
                                                                    {tarea
                                                                        .asignado
                                                                        ?.email ||
                                                                        ''}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <td className="whitespace-nowrap px-5 py-3.5 text-sm text-gray-600">
                                                        <div className="flex items-center gap-2">
                                                            <CalendarClock size={17} className="text-slate-400" />

                                                            {formatearFecha(
                                                                tarea.fecha_recordatorio,
                                                            )}
                                                        </div>
                                                    </td>

                                                    <td className="px-5 py-3.5">
                                                        <EstadoTarea tarea={tarea} />
                                                    </td>

                                                    <td className="px-5 py-3.5 text-right">
                                                        <MenuAcciones tarea={tarea} onVer={() => setTareaDetalle( tarea, ) } onEditar={() => abrirEdicion( tarea, ) } onCompletar={() => completarTarea( tarea, ) } onSuspender={() => suspenderTarea( tarea, ) } onReactivar={() => reactivarTarea( tarea, ) } onEliminar={() => eliminarTarea( tarea, ) } />
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                <div className="divide-y divide-gray-200 lg:hidden">
                                    {registros.map((tarea) => (
                                        <article key={tarea.id} className="space-y-3 p-4" >
                                            <div className="flex items-start justify-between gap-4">
                                                <button type="button" onClick={() => setTareaDetalle(tarea) } className="min-w-0 text-left" >
                                                    <h3 className="truncate font-bold text-slate-900">
                                                        {tarea.titulo}
                                                    </h3>

                                                    <p className="mt-1 line-clamp-2 text-sm text-slate-500">
                                                        {tarea.descripcion ||
                                                            'Sin descripción'}
                                                    </p>
                                                </button>

                                                <MenuAcciones tarea={tarea} onVer={() => setTareaDetalle(tarea) } onEditar={() => abrirEdicion(tarea) } onCompletar={() => completarTarea(tarea) } onSuspender={() => suspenderTarea(tarea) } onReactivar={() => reactivarTarea(tarea) } onEliminar={() => eliminarTarea(tarea) } />
                                            </div>

                                            <EstadoTarea tarea={tarea} />

                                            <div className="grid gap-3 text-sm text-slate-600">
                                                <div className="flex items-center gap-2">
                                                    <UserRound size={17} className="text-slate-400" />

                                                    {tarea.asignado?.name ||
                                                        'Sin asignar'}
                                                </div>

                                                <div className="flex items-center gap-2">
                                                    <CalendarClock size={17} className="text-slate-400" />

                                                    {formatearFecha(
                                                        tarea.fecha_recordatorio,
                                                    )}
                                                </div>
                                            </div>

                                            {tarea.estado === 'pendiente' && (
                                                <div className="grid grid-cols-2 gap-2">
                                                    <button type="button" onClick={() => completarTarea(tarea) } className="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-50 px-3 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-100" >
                                                        <CheckCircle2 size={17} />
                                                        Completar
                                                    </button>

                                                    <button type="button" onClick={() => suspenderTarea(tarea) } className="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-50 px-3 py-2.5 text-sm font-semibold text-amber-700 hover:bg-amber-100" >
                                                        <PauseCircle size={17} />
                                                        Suspender
                                                    </button>
                                                </div>
                                            )}

                                            {tarea.estado === 'suspendida' && (
                                                <button type="button" onClick={() => reactivarTarea(tarea) } className="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-50 px-3 py-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-100" >
                                                    <PlayCircle size={17} />
                                                    Reactivar tarea
                                                </button>
                                            )}
                                        </article>
                                    ))}
                                </div>

                                {tareas?.links?.length > 3 && (
                                    <div className="flex flex-col items-center justify-between gap-3 border-t border-gray-200 px-5 py-3 sm:flex-row">
                                        <p className="text-sm text-slate-500">
                                            Mostrando{' '}
                                            <span className="font-semibold text-slate-700">
                                                {tareas.from ?? 0}
                                            </span>{' '}
                                            a{' '}
                                            <span className="font-semibold text-slate-700">
                                                {tareas.to ?? 0}
                                            </span>{' '}
                                            de{' '}
                                            <span className="font-semibold text-slate-700">
                                                {tareas.total ?? 0}
                                            </span>{' '}
                                            tareas
                                        </p>

                                        <div className="flex items-center gap-1">
                                            {tareas.links.map((link, index) => {
                                                const esAnterior =
                                                    index === 0;
                                                const esSiguiente =
                                                    index ===
                                                    tareas.links.length - 1;

                                                return (
                                                    <button key={`${link.label}-${index}`} type="button" disabled={!link.url} onClick={() => { if (!link.url) { return; } router.visit( link.url, { preserveScroll: true, preserveState: true, }, ); }} className={`flex h-9 min-w-9 items-center justify-center rounded-lg px-3 text-sm font-semibold transition ${ link.active ? 'bg-blue-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' } disabled:cursor-not-allowed disabled:opacity-40`} >
                                                        {esAnterior ? (
                                                            <ChevronLeft size={17} />
                                                        ) : esSiguiente ? (
                                                            <ChevronRight size={17} />
                                                        ) : (
                                                            <span dangerouslySetInnerHTML={{ __html: link.label, }} />
                                                        )}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}
                            </>
                        )}
                    </section>
                </div>
            </div>

            <ModalTarea open={modalAbierto} onClose={cerrarModal} usuarios={usuarios} tarea={tareaSeleccionada} />

            <DetalleTarea tarea={tareaDetalle} onClose={() => setTareaDetalle(null)} />
        </AppLayout>
    );
}