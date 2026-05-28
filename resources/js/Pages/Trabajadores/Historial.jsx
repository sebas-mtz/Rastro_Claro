import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { ArrowLeft, Activity, Clock } from 'lucide-react';

const ACCION_CONFIG = {
    crear:      { label: 'Creó',      cls: 'bg-emerald-100 text-emerald-700' },
    editar:     { label: 'Editó',     cls: 'bg-blue-100 text-blue-700' },
    eliminar:   { label: 'Eliminó',   cls: 'bg-red-100 text-red-700' },
    ver:        { label: 'Consultó',  cls: 'bg-gray-100 text-gray-600' },
    login:      { label: 'Ingresó',   cls: 'bg-violet-100 text-violet-700' },
    logout:     { label: 'Salió',     cls: 'bg-gray-100 text-gray-500' },
    activar:    { label: 'Activó',    cls: 'bg-emerald-100 text-emerald-700' },
    desactivar: { label: 'Desactivó', cls: 'bg-orange-100 text-orange-700' },
};

export default function Historial({ trabajador, historial = [] }) {
    return (
        <>
            <Head title={`Historial — ${trabajador.name}`} />
            <div className="py-8 px-4 max-w-4xl mx-auto space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={route('trabajadores.index')}
                        className="p-2 rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-500">
                        <ArrowLeft className="w-4 h-4" />
                    </Link>
                    <div>
                        <h1 className="text-xl font-bold text-gray-800 flex items-center gap-2">
                            <Activity className="w-5 h-5 text-emerald-600" />
                            Historial de {trabajador.name}
                        </h1>
                        <p className="text-sm text-gray-500">{trabajador.email} · {trabajador.roleLabel}</p>
                    </div>
                </div>

                {historial.length === 0 ? (
                    <div className="bg-white rounded-2xl border p-12 text-center text-gray-400">
                        <Activity className="w-10 h-10 mx-auto mb-2 opacity-40" />
                        <p className="text-sm">Sin actividad registrada.</p>
                    </div>
                ) : (
                    <div className="bg-white rounded-2xl border divide-y">
                        {historial.map(h => {
                            const ac = ACCION_CONFIG[h.accion] ?? { label: h.accion, cls: 'bg-gray-100 text-gray-600' };
                            return (
                                <div key={h.id} className="px-6 py-3 flex items-start gap-4">
                                    <span className={`mt-0.5 px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0 ${ac.cls}`}>
                                        {ac.label}
                                    </span>
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm text-gray-800">
                                            <span className="font-medium capitalize">{h.modulo}</span>
                                            {h.registro_desc && <> — <span className="text-gray-600">{h.registro_desc}</span></>}
                                        </p>
                                        {h.datos_extra && (
                                            <p className="text-xs text-gray-400 mt-0.5 truncate">
                                                {JSON.stringify(h.datos_extra)}
                                            </p>
                                        )}
                                    </div>
                                    <div className="flex-shrink-0 flex items-center gap-1 text-xs text-gray-400">
                                        <Clock className="w-3 h-3" />{h.fecha}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </>
    );
}

Historial.layout = page => <AppLayout>{page}</AppLayout>;
