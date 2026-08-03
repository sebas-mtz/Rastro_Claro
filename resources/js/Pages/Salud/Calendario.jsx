import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { CalendarDays, AlertTriangle, Clock, CheckCircle2, Baby } from 'lucide-react';

function fmtFecha(f) {
    return f ? new Date(f).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
}

function Seccion({ titulo, descripcion, icono: Icono, color, eventos = [], vacio }) {
    return (
        <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div className={`px-5 py-3 border-b border-slate-200 flex items-center gap-2 ${color}`}>
                <Icono className="w-4 h-4" />
                <div>
                    <h2 className="text-sm font-semibold">{titulo}</h2>
                    <p className="text-xs opacity-80">{descripcion}</p>
                </div>
                <span className="ml-auto text-sm font-bold">{eventos.length}</span>
            </div>

            {eventos.length === 0 ? (
                <p className="px-5 py-6 text-sm text-slate-400 text-center">{vacio}</p>
            ) : (
                <ul className="divide-y divide-slate-100">
                    {eventos.map((ev) => (
                        <li key={ev.id} className="px-5 py-3 flex flex-wrap items-start justify-between gap-2">
                            <div className="min-w-0">
                                <p className="text-sm font-medium text-slate-800">
                                    {ev.vacuna?.nombre || ev.diagnostico || ev.tipo}
                                </p>
                                <p className="text-xs text-slate-500">
                                    {ev.animal
                                        ? `${ev.animal.arete}${ev.animal.alias ? ` — ${ev.animal.alias}` : ''}`
                                        : ev.lote?.nombre
                                            ? `Lote: ${ev.lote.nombre}`
                                            : 'Sin destino asignado'}
                                </p>
                                {ev.responsable && (
                                    <p className="text-xs text-slate-400">Responsable: {ev.responsable}</p>
                                )}
                            </div>
                            <div className="text-right shrink-0">
                                <p className="text-xs text-slate-500">
                                    {fmtFecha(ev.fecha_aplicacion || ev.fecha_programada)}
                                </p>
                                <span className="inline-flex mt-1 px-2 py-0.5 text-[11px] font-medium rounded-full bg-slate-100 text-slate-600">
                                    {ev.tipo}
                                </span>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

export default function Calendario({
    auth, vencidas = [], delDia = [], proximas = [], completadas = [],
    partosProximos = [], filtros = {}, tipos = {},
}) {
    const filtrar = (cambios) => {
        router.get(route('calendario.index'), { ...filtros, ...cambios }, {
            preserveState: true, preserveScroll: true, replace: true,
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800">Calendario sanitario</h2>}
        >
            <Head title="Calendario sanitario" />

            <div className="py-8 px-4 sm:px-6 max-w-5xl mx-auto space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <CalendarDays className="w-7 h-7 text-emerald-600" />
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900">Calendario sanitario</h1>
                            <p className="text-sm text-slate-600">
                                Vacunas, desparasitaciones, revisiones y atención veterinaria del rebaño.
                            </p>
                        </div>
                    </div>

                    <div className="flex gap-2">
                        <select
                            value={filtros.tipo || ''}
                            onChange={(e) => filtrar({ tipo: e.target.value })}
                            className="border border-slate-300 rounded-lg px-2 py-2 text-sm text-slate-700"
                            aria-label="Tipo de actividad"
                        >
                            <option value="">Todos los tipos</option>
                            {Object.entries(tipos).map(([valor, etiqueta]) => (
                                <option key={valor} value={valor}>{etiqueta}</option>
                            ))}
                        </select>
                        <select
                            value={filtros.dias || '30'}
                            onChange={(e) => filtrar({ dias: e.target.value })}
                            className="border border-slate-300 rounded-lg px-2 py-2 text-sm text-slate-700"
                            aria-label="Horizonte"
                        >
                            <option value="7">Próximos 7 días</option>
                            <option value="15">Próximos 15 días</option>
                            <option value="30">Próximos 30 días</option>
                            <option value="60">Próximos 60 días</option>
                        </select>
                    </div>
                </div>

                {/* Partos próximos: hito reproductivo que ocupa al equipo */}
                {partosProximos.length > 0 && (
                    <div className="bg-purple-50 border border-purple-200 rounded-2xl p-4">
                        <p className="text-sm font-semibold text-purple-900 flex items-center gap-2">
                            <Baby className="w-4 h-4" />
                            {partosProximos.length} borrega(s) con parto probable en los próximos 21 días
                        </p>
                        <ul className="mt-2 space-y-1">
                            {partosProximos.map((dx) => (
                                <li key={dx.id} className="text-xs text-purple-800 flex justify-between">
                                    <span>
                                        {dx.evento?.hembra?.arete || 'Sin arete'}
                                        {dx.evento?.hembra?.alias ? ` — ${dx.evento.hembra.alias}` : ''}
                                    </span>
                                    <span>{fmtFecha(dx.fecha_probable_parto)}</span>
                                </li>
                            ))}
                        </ul>
                        <Link href="/reproduccion" className="mt-2 inline-block text-xs text-purple-900 underline">
                            Ir a Reproducción →
                        </Link>
                    </div>
                )}

                <Seccion
                    titulo="Vencidas"
                    descripcion="Su fecha ya pasó y siguen sin aplicarse"
                    icono={AlertTriangle}
                    color="bg-red-50 text-red-800"
                    eventos={vencidas}
                    vacio="Sin actividades vencidas. Todo al día."
                />

                <Seccion
                    titulo="Hoy"
                    descripcion="Programadas para el día de hoy"
                    icono={Clock}
                    color="bg-amber-50 text-amber-800"
                    eventos={delDia}
                    vacio="No hay actividades programadas para hoy."
                />

                <Seccion
                    titulo="Próximas"
                    descripcion={`Dentro de los próximos ${filtros.dias || 30} días`}
                    icono={CalendarDays}
                    color="bg-blue-50 text-blue-800"
                    eventos={proximas}
                    vacio="Sin actividades programadas en este periodo."
                />

                <Seccion
                    titulo="Completadas"
                    descripcion="Aplicadas en los últimos 30 días"
                    icono={CheckCircle2}
                    color="bg-emerald-50 text-emerald-800"
                    eventos={completadas}
                    vacio="Sin actividades completadas recientemente."
                />
            </div>
        </AuthenticatedLayout>
    );
}
