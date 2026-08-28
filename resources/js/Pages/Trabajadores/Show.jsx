import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft, PlusCircle, Lock, Trash2, Clock, Coins, Users, Boxes, History,
} from 'lucide-react';
import ActividadModal from './ActividadModal';
import { formatMXN } from '@/utils/currency';

function fmtFecha(f) {
    return f ? new Date(f).toLocaleDateString('es-MX') : '—';
}

function Dato({ etiqueta, valor }) {
    return (
        <div>
            <dt className="text-xs font-medium text-slate-500">{etiqueta}</dt>
            <dd className="text-sm text-slate-800 mt-0.5">{valor || '—'}</dd>
        </div>
    );
}

export default function TrabajadorShow({
    auth,
    trabajador,
    actividades,
    resumen = {},
    costos,
    historialCambios = [],
    tiposActividad = {},
    modalidadesPago = {},
    animales = [],
    lotes = [],
    permisos = {},
}) {
    const [showActividad, setShowActividad] = useState(false);

    const filas = actividades?.data || [];

    const eliminarActividad = (id) => {
        if (confirm('¿Eliminar esta actividad? También se retirará el costo de mano de obra que generó.')) {
            router.delete(route('actividades-trabajador.destroy', id), { preserveScroll: true });
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800">Ficha del trabajador</h2>}
        >
            <Head title={trabajador.nombre_completo} />

            <div className="py-8 px-4 sm:px-6 max-w-6xl mx-auto space-y-6">
                <Link
                    href={route('trabajadores.index')}
                    className="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1"
                >
                    <ArrowLeft className="w-4 h-4" /> Volver a trabajadores
                </Link>

                {/* Encabezado */}
                <div className="bg-white rounded-2xl border border-slate-200 p-6">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900">{trabajador.nombre_completo}</h1>
                            <p className="text-slate-600">
                                {trabajador.puesto?.nombre || 'Sin puesto'}
                                {trabajador.area ? ` · ${trabajador.area}` : ''}
                            </p>
                        </div>

                        <div className="flex items-center gap-3">
                            <span className={
                                'inline-flex px-3 py-1 text-xs font-semibold rounded-full ' +
                                (trabajador.activo ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600')
                            }>
                                {trabajador.activo ? 'Activo' : 'Inactivo'}
                            </span>

                            {permisos.registrarActividad && trabajador.activo && (
                                <button
                                    onClick={() => setShowActividad(true)}
                                    className="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2 rounded-lg flex items-center gap-2 text-sm transition"
                                >
                                    <PlusCircle className="w-4 h-4" /> Registrar actividad
                                </button>
                            )}
                        </div>
                    </div>

                    {!trabajador.activo && (
                        <p className="mt-4 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg p-3">
                            Trabajador inactivo desde {fmtFecha(trabajador.fecha_baja)}.
                            {trabajador.motivo_baja ? ` Motivo: ${trabajador.motivo_baja}.` : ''}
                            {' '}Su historial se conserva; no puede recibir actividades nuevas.
                        </p>
                    )}

                    <dl className="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <Dato etiqueta="Teléfono" valor={trabajador.telefono} />
                        <Dato etiqueta="Correo" valor={trabajador.email} />
                        <Dato etiqueta="Contratación" valor={fmtFecha(trabajador.fecha_contratacion)} />
                        <Dato etiqueta="Horario" valor={trabajador.horario} />
                        <Dato
                            etiqueta="Cuenta del sistema"
                            valor={trabajador.usuario ? trabajador.usuario.email : 'Sin acceso'}
                        />
                        <Dato etiqueta="Tipo de contratación" valor={trabajador.tipo_contratacion} />
                    </dl>

                    {/* Datos reservados: el backend solo los envía a quien puede verlos */}
                    {permisos.verSensibles ? (
                        <div className="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <h3 className="text-sm font-semibold text-amber-800 uppercase tracking-wide flex items-center gap-1 mb-3">
                                <Lock className="w-3.5 h-3.5" /> Datos reservados
                            </h3>
                            <dl className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                <Dato etiqueta="CURP" valor={trabajador.curp} />
                                <Dato etiqueta="RFC" valor={trabajador.rfc} />
                                <Dato etiqueta="Nacimiento" valor={fmtFecha(trabajador.fecha_nacimiento)} />
                                <Dato etiqueta="Dirección" valor={trabajador.direccion} />
                                <Dato etiqueta="Sueldo" valor={trabajador.sueldo != null ? formatMXN(trabajador.sueldo) : null} />
                                <Dato etiqueta="Costo por jornada" valor={trabajador.costo_jornada != null ? formatMXN(trabajador.costo_jornada) : null} />
                                <Dato etiqueta="Costo por hora" valor={trabajador.costo_hora != null ? formatMXN(trabajador.costo_hora) : null} />
                                <Dato
                                    etiqueta="Emergencia"
                                    valor={trabajador.contacto_emergencia
                                        ? `${trabajador.contacto_emergencia}${trabajador.telefono_emergencia ? ` · ${trabajador.telefono_emergencia}` : ''}`
                                        : null}
                                />
                            </dl>
                        </div>
                    ) : (
                        <p className="mt-6 text-xs text-slate-500 flex items-center gap-2">
                            <Lock className="w-4 h-4 text-slate-400" />
                            Los datos personales y salariales están reservados a los administradores.
                        </p>
                    )}

                    {trabajador.observaciones && (
                        <p className="mt-4 text-sm text-slate-600 border-t border-slate-200 pt-4">
                            {trabajador.observaciones}
                        </p>
                    )}
                </div>

                {/* Cifras acumuladas */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <p className="text-xs font-medium text-slate-500 flex items-center gap-1">
                            <Clock className="w-3.5 h-3.5" /> Horas trabajadas
                        </p>
                        <p className="mt-2 text-2xl font-semibold text-slate-900 tabular-nums">
                            {resumen.horas ?? 0}
                        </p>
                        <p className="text-xs text-slate-400 mt-0.5">
                            {resumen.actividades ?? 0} actividad(es)
                        </p>
                    </div>

                    <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <p className="text-xs font-medium text-slate-500 flex items-center gap-1">
                            <Users className="w-3.5 h-3.5" /> Ejemplares atendidos
                        </p>
                        <p className="mt-2 text-2xl font-semibold text-slate-900">{resumen.animales_atendidos ?? 0}</p>
                    </div>

                    <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <p className="text-xs font-medium text-slate-500 flex items-center gap-1">
                            <Boxes className="w-3.5 h-3.5" /> Lotes y faenas
                        </p>
                        <p className="mt-2 text-2xl font-semibold text-slate-900">
                            {resumen.lotes_atendidos ?? 0}
                            <span className="text-base text-slate-400"> / {resumen.faenas ?? 0}</span>
                        </p>
                    </div>

                    <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <p className="text-xs font-medium text-slate-500 flex items-center gap-1">
                            <Coins className="w-3.5 h-3.5" /> Mano de obra
                        </p>
                        {permisos.verCostos ? (
                            <>
                                <p className="mt-2 text-2xl font-semibold text-emerald-700 tabular-nums">
                                    {formatMXN(resumen.costo_total)}
                                </p>
                                <p className="text-xs text-slate-400 mt-0.5">
                                    {resumen.costo_promedio != null
                                        ? `${formatMXN(resumen.costo_promedio)} por actividad`
                                        : 'Sin actividades todavía'}
                                </p>
                            </>
                        ) : (
                            <p className="mt-2 text-sm text-slate-400 flex items-center gap-1">
                                <Lock className="w-3.5 h-3.5" /> Reservado
                            </p>
                        )}
                    </div>
                </div>

                {/* Actividades */}
                <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div className="px-5 py-4 border-b border-slate-200">
                        <h2 className="font-semibold text-slate-800">Actividades realizadas</h2>
                    </div>

                    {filas.length === 0 ? (
                        <p className="text-center text-slate-500 py-10">
                            Todavía no hay actividades registradas para esta persona.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-slate-200">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Fecha</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Actividad</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Destino</th>
                                        <th className="px-5 py-3 text-right text-xs font-medium text-slate-500 uppercase">Tiempo</th>
                                        {permisos.verCostos && (
                                            <th className="px-5 py-3 text-right text-xs font-medium text-slate-500 uppercase">Costo</th>
                                        )}
                                        <th className="px-5 py-3" />
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-slate-100">
                                    {filas.map((a) => (
                                        <tr key={a.id} className="hover:bg-slate-50">
                                            <td className="px-5 py-3 whitespace-nowrap text-sm text-slate-700">
                                                {fmtFecha(a.fecha)}
                                                {a.hora_inicio && (
                                                    <span className="block text-xs text-slate-400">
                                                        {String(a.hora_inicio).slice(0, 5)}
                                                        {a.hora_fin ? `–${String(a.hora_fin).slice(0, 5)}` : ''}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-700">
                                                {a.tipo_legible}
                                                {a.descripcion && (
                                                    <span className="block text-xs text-slate-400">{a.descripcion}</span>
                                                )}
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-600">
                                                {a.animal ? (
                                                    <Link href={`/animales/${a.animal.id}`} className="text-emerald-700 hover:underline">
                                                        {a.animal.arete}
                                                    </Link>
                                                ) : a.lote ? (
                                                    <span>{a.lote.nombre}</span>
                                                ) : (
                                                    <span className="text-slate-400">General</span>
                                                )}
                                                {a.animales_atendidos > 1 && (
                                                    <span className="block text-xs text-slate-400">
                                                        Repartido entre {a.animales_atendidos} ejemplares
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-5 py-3 whitespace-nowrap text-sm text-right text-slate-700 tabular-nums">
                                                {a.modalidad_pago === 'jornada'
                                                    ? `${a.jornadas ?? 0} jornada(s)`
                                                    : `${a.horas_trabajadas ?? 0} h`}
                                            </td>
                                            {permisos.verCostos && (
                                                <td className="px-5 py-3 whitespace-nowrap text-sm text-right font-medium text-slate-800 tabular-nums">
                                                    {formatMXN(a.costo_total)}
                                                </td>
                                            )}
                                            <td className="px-5 py-3 whitespace-nowrap text-right">
                                                {permisos.cambiarEstado && (
                                                    <button
                                                        onClick={() => eliminarActividad(a.id)}
                                                        title="Eliminar actividad"
                                                        className="text-red-500 hover:text-red-700"
                                                    >
                                                        <Trash2 className="w-4 h-4" />
                                                    </button>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {actividades?.links && actividades.links.length > 3 && (
                        <div className="flex justify-center py-4 border-t border-slate-100">
                            <nav className="flex flex-wrap items-center gap-2">
                                {actividades.links.map((link, i) => (
                                    <Link
                                        key={i}
                                        href={link.url || '#'}
                                        className={`px-3 py-1 rounded-lg text-sm font-medium ${
                                            link.active
                                                ? 'bg-emerald-600 text-white'
                                                : 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50'
                                        } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </nav>
                        </div>
                    )}
                </div>

                {/* Costos de mano de obra generados */}
                {permisos.verCostos && costos && costos.length > 0 && (
                    <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                        <div className="px-5 py-4 border-b border-slate-200">
                            <h2 className="font-semibold text-slate-800">Costos de mano de obra</h2>
                            <p className="text-xs text-slate-500 mt-0.5">
                                Cada actividad genera su costo automáticamente. No hay que capturarlo aparte.
                            </p>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-slate-200">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Fecha</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Concepto</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Aplicado a</th>
                                        <th className="px-5 py-3 text-right text-xs font-medium text-slate-500 uppercase">Monto</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-slate-100">
                                    {costos.map((c) => (
                                        <tr key={c.id} className="hover:bg-slate-50">
                                            <td className="px-5 py-3 whitespace-nowrap text-sm text-slate-700">{fmtFecha(c.fecha)}</td>
                                            <td className="px-5 py-3 text-sm text-slate-700">
                                                {c.concepto}
                                                {c.descripcion && (
                                                    <span className="block text-xs text-slate-400">{c.descripcion}</span>
                                                )}
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-600">
                                                {c.animal?.arete || c.lote?.nombre || 'General'}
                                            </td>
                                            <td className="px-5 py-3 whitespace-nowrap text-sm text-right font-medium text-slate-800 tabular-nums">
                                                {formatMXN(c.monto)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {/* Historial de la relación laboral */}
                <div className="bg-white rounded-2xl border border-slate-200 p-5">
                    <h2 className="font-semibold text-slate-800 flex items-center gap-2 mb-4">
                        <History className="w-4 h-4 text-slate-400" /> Historial
                    </h2>
                    <ol className="space-y-3">
                        {historialCambios.map((evento, i) => (
                            <li key={i} className="flex gap-3 text-sm">
                                <span className="text-slate-400 tabular-nums whitespace-nowrap w-24">
                                    {fmtFecha(evento.fecha)}
                                </span>
                                <span className="text-slate-700">
                                    {evento.evento}
                                    {evento.detalle && (
                                        <span className="block text-xs text-slate-400">{evento.detalle}</span>
                                    )}
                                </span>
                            </li>
                        ))}
                    </ol>
                </div>
            </div>

            <ActividadModal
                show={showActividad}
                onClose={() => setShowActividad(false)}
                trabajadorFijo={trabajador}
                tiposActividad={tiposActividad}
                modalidadesPago={modalidadesPago}
                animales={animales}
                lotes={lotes}
                puedeVerCostos={permisos.verCostos}
            />
        </AuthenticatedLayout>
    );
}
