import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Activity, Filter, Clock, Search } from 'lucide-react';

const ACCION_CONFIG = {
    crear:      { cls: 'bg-emerald-100 text-emerald-700' },
    editar:     { cls: 'bg-blue-100 text-blue-700' },
    eliminar:   { cls: 'bg-red-100 text-red-700' },
    ver:        { cls: 'bg-gray-100 text-gray-500' },
    login:      { cls: 'bg-violet-100 text-violet-700' },
    desactivar: { cls: 'bg-orange-100 text-orange-700' },
    activar:    { cls: 'bg-emerald-100 text-emerald-700' },
};

const MODULOS = ['animales','lotes','salud','costos','alimentacion','producciones','reproduccion','pesajes','ventas','faenas','sacrificios','trabajadores'];

export default function HistorialGlobal({ historial = [], trabajadores = [], filtros = {} }) {
    const [modulo, setModulo]   = useState(filtros.modulo ?? '');
    const [userId, setUserId]   = useState(filtros.user_id ?? '');
    const [busqueda, setBusqueda] = useState('');

    function aplicarFiltros() {
        router.get(route('trabajadores.historial-global'), { modulo: modulo || undefined, user_id: userId || undefined }, { preserveScroll: true });
    }

    const filtrados = busqueda
        ? historial.filter(h =>
            h.usuario.toLowerCase().includes(busqueda.toLowerCase()) ||
            h.modulo.toLowerCase().includes(busqueda.toLowerCase()) ||
            (h.registro_desc ?? '').toLowerCase().includes(busqueda.toLowerCase())
          )
        : historial;

    return (
        <>
            <Head title="Historial de Actividad" />
            <div className="py-8 px-4 max-w-7xl mx-auto space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <Activity className="w-7 h-7 text-emerald-600" />
                        Historial de Actividad
                    </h1>
                    <p className="text-sm text-gray-500 mt-0.5">Registro de todas las acciones realizadas en el sistema</p>
                </div>

                {/* Filtros */}
                <div className="bg-white rounded-2xl border p-4 flex flex-wrap gap-3 items-end">
                    <div className="flex-1 min-w-[160px]">
                        <label className="block text-xs font-medium text-gray-500 mb-1">Módulo</label>
                        <select value={modulo} onChange={e => setModulo(e.target.value)}
                            className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Todos</option>
                            {MODULOS.map(m => <option key={m} value={m} className="capitalize">{m}</option>)}
                        </select>
                    </div>
                    <div className="flex-1 min-w-[160px]">
                        <label className="block text-xs font-medium text-gray-500 mb-1">Trabajador</label>
                        <select value={userId} onChange={e => setUserId(e.target.value)}
                            className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Todos</option>
                            {trabajadores.map(t => <option key={t.id} value={t.id}>{t.name}</option>)}
                        </select>
                    </div>
                    <button onClick={aplicarFiltros}
                        className="flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700">
                        <Filter className="w-4 h-4" /> Filtrar
                    </button>
                    <div className="flex-1 min-w-[200px]">
                        <label className="block text-xs font-medium text-gray-500 mb-1">Buscar en resultados</label>
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
                            <input value={busqueda} onChange={e => setBusqueda(e.target.value)}
                                placeholder="Nombre, módulo, descripción..."
                                className="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg text-sm" />
                        </div>
                    </div>
                </div>

                {/* Tabla */}
                <div className="bg-white rounded-2xl border overflow-hidden">
                    {filtrados.length === 0 ? (
                        <div className="text-center py-12 text-gray-400">
                            <Activity className="w-10 h-10 mx-auto mb-2 opacity-40" />
                            <p className="text-sm">Sin registros de actividad.</p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-gray-50 border-b">
                                    <tr>
                                        <th className="text-left px-4 py-3 font-semibold text-gray-600">Fecha</th>
                                        <th className="text-left px-4 py-3 font-semibold text-gray-600">Usuario</th>
                                        <th className="text-left px-4 py-3 font-semibold text-gray-600">Módulo</th>
                                        <th className="text-left px-4 py-3 font-semibold text-gray-600">Acción</th>
                                        <th className="text-left px-4 py-3 font-semibold text-gray-600">Registro</th>
                                        <th className="text-left px-4 py-3 font-semibold text-gray-600 hidden lg:table-cell">IP</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {filtrados.map(h => {
                                        const ac = ACCION_CONFIG[h.accion] ?? { cls: 'bg-gray-100 text-gray-600' };
                                        return (
                                            <tr key={h.id} className="hover:bg-gray-50">
                                                <td className="px-4 py-2.5 text-gray-500 text-xs whitespace-nowrap">
                                                    <div className="flex items-center gap-1">
                                                        <Clock className="w-3 h-3" />{h.fecha}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-2.5 font-medium text-gray-700">{h.usuario}</td>
                                                <td className="px-4 py-2.5">
                                                    <span className="capitalize text-gray-600">{h.modulo}</span>
                                                </td>
                                                <td className="px-4 py-2.5">
                                                    <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${ac.cls}`}>
                                                        {h.accion}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-2.5 text-gray-600 max-w-xs truncate">
                                                    {h.registro_desc ?? (h.registro_id ? `ID: ${h.registro_id}` : '—')}
                                                </td>
                                                <td className="px-4 py-2.5 text-gray-400 text-xs hidden lg:table-cell">{h.ip}</td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

HistorialGlobal.layout = page => <AppLayout>{page}</AppLayout>;
