import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Stethoscope, Search, CheckCircle2, XCircle } from 'lucide-react';

const tipoConfig = {
    consulta:   { label: 'Consulta',   bg: 'bg-blue-50',   text: 'text-blue-700',   border: 'border-blue-200'   },
    revision:   { label: 'Revisión',   bg: 'bg-purple-50', text: 'text-purple-700', border: 'border-purple-200' },
    emergencia: { label: 'Emergencia', bg: 'bg-red-50',     text: 'text-red-700',    border: 'border-red-200'    },
};

const estadoConfig = {
    pendiente: { label: 'Pendiente', bg: 'bg-amber-50',  text: 'text-amber-700'  },
    vencida:   { label: 'Vencida',   bg: 'bg-red-50',    text: 'text-red-700'    },
    aplicada:  { label: 'Realizada', bg: 'bg-green-50',  text: 'text-green-700'  },
};

export default function TabEventos({ eventos = [] }) {
    const [search, setSearch]           = useState('');
    const [completandoId, setCompletandoId] = useState(null);
    const [form, setForm]               = useState({
        diagnostico: '', observaciones: '',
        crear_tratamiento: false,
        tratamiento_nombre: '', tratamiento_notas: '', tratamiento_fecha_fin: '',
    });

    const filtrados = eventos.filter(e =>
        !search ||
        e.animal.toLowerCase().includes(search.toLowerCase()) ||
        e.diagnostico?.toLowerCase().includes(search.toLowerCase())
    );

    function abrirCompletar(evento) {
        setCompletandoId(evento.id);
        setForm({
            diagnostico:         evento.diagnostico ?? '',
            observaciones:       '',
            crear_tratamiento:   false,
            tratamiento_nombre:  '',
            tratamiento_notas:   '',
            tratamiento_fecha_fin: '',
        });
    }

    function submitCompletar(e) {
        e.preventDefault();
        router.patch(
            route('eventos-salud.completar', completandoId),
            form,
            {
                preserveScroll: true,
                onSuccess: () => setCompletandoId(null),
            }
        );
    }

    if (eventos.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center py-16 text-gray-400 gap-3">
                <Stethoscope className="w-10 h-10 opacity-30" />
                <p className="font-medium text-gray-500">Sin consultas pendientes</p>
                <p className="text-sm text-center">
                    Las consultas, revisiones y emergencias registradas aparecerán aquí.
                </p>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            {/* Buscador */}
            <div className="relative">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input
                    type="text"
                    value={search}
                    onChange={e => setSearch(e.target.value)}
                    placeholder="Buscar por animal o diagnóstico…"
                    className="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                />
            </div>

            {/* Lista */}
            <div className="space-y-2">
                {filtrados.map(evento => {
                    const tipo   = tipoConfig[evento.tipo]   ?? tipoConfig.consulta;
                    const estado = estadoConfig[evento.estado] ?? estadoConfig.pendiente;
                    const abierto = completandoId === evento.id;

                    return (
                        <div key={evento.id} className="border border-gray-100 rounded-xl overflow-hidden">
                            {/* Fila principal */}
                            <div className="flex items-center gap-3 px-4 py-3">
                                {/* Tipo badge */}
                                <span className={`text-xs font-semibold px-2 py-0.5 rounded-md border ${tipo.bg} ${tipo.text} ${tipo.border} flex-shrink-0`}>
                                    {tipo.label}
                                </span>

                                {/* Info */}
                                <div className="flex-1 min-w-0">
                                    <p className="text-sm font-medium text-gray-800 truncate">
                                        {evento.animal}
                                    </p>
                                    {evento.diagnostico && (
                                        <p className="text-xs text-gray-500 truncate">{evento.diagnostico}</p>
                                    )}
                                    <p className="text-xs text-gray-400 mt-0.5">{evento.fecha}</p>
                                </div>

                                {/* Estado + acción */}
                                <div className="flex items-center gap-2 flex-shrink-0">
                                    <span className={`text-xs font-medium px-2 py-0.5 rounded-md ${estado.bg} ${estado.text}`}>
                                        {estado.label}
                                    </span>
                                    {evento.estado !== 'aplicada' && (
                                        <button
                                            type="button"
                                            onClick={() => abierto ? setCompletandoId(null) : abrirCompletar(evento)}
                                            className="text-xs border border-gray-200 rounded-lg px-2.5 py-1 hover:bg-gray-50 text-gray-600 transition"
                                        >
                                            {abierto ? 'Cancelar' : 'Registrar resultado'}
                                        </button>
                                    )}
                                </div>
                            </div>

                            {/* Formulario de completar — expansible */}
                            {abierto && (
                                <form
                                    onSubmit={submitCompletar}
                                    className="border-t border-gray-100 bg-gray-50 px-4 py-4 space-y-3"
                                >
                                    <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                        Resultado del evento
                                    </p>

                                    {/* Diagnóstico final */}
                                    <label className="flex flex-col gap-1 text-sm text-gray-700 font-medium">
                                        Diagnóstico / resultado
                                        <input
                                            type="text"
                                            value={form.diagnostico}
                                            onChange={e => setForm(f => ({ ...f, diagnostico: e.target.value }))}
                                            placeholder="Diagnóstico final del evento…"
                                            className="border border-gray-200 rounded-lg px-3 py-1.5 text-sm font-normal focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        />
                                    </label>

                                    {/* Observaciones */}
                                    <label className="flex flex-col gap-1 text-sm text-gray-700 font-medium">
                                        Observaciones <span className="font-normal text-gray-400">(opcional)</span>
                                        <textarea
                                            value={form.observaciones}
                                            onChange={e => setForm(f => ({ ...f, observaciones: e.target.value }))}
                                            rows={2}
                                            placeholder="Notas adicionales…"
                                            className="border border-gray-200 rounded-lg px-3 py-1.5 text-sm font-normal resize-none focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        />
                                    </label>

                                    {/* Toggle crear tratamiento */}
                                    <label className="flex items-center gap-2 text-sm text-gray-700 cursor-pointer select-none">
                                        <input
                                            type="checkbox"
                                            checked={form.crear_tratamiento}
                                            onChange={e => setForm(f => ({ ...f, crear_tratamiento: e.target.checked }))}
                                            className="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        Crear tratamiento vinculado a este evento
                                    </label>

                                    {/* Campos del tratamiento — solo si el toggle está activo */}
                                    {form.crear_tratamiento && (
                                        <div className="space-y-2 pl-6 border-l-2 border-blue-200">
                                            <label className="flex flex-col gap-1 text-sm text-gray-700 font-medium">
                                                Nombre del tratamiento *
                                                <input
                                                    type="text"
                                                    value={form.tratamiento_nombre}
                                                    onChange={e => setForm(f => ({ ...f, tratamiento_nombre: e.target.value }))}
                                                    placeholder="Ej: Antibiótico para neumonía"
                                                    required={form.crear_tratamiento}
                                                    className="border border-gray-200 rounded-lg px-3 py-1.5 text-sm font-normal focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                />
                                            </label>

                                            <div className="grid grid-cols-2 gap-2">
                                                <label className="flex flex-col gap-1 text-sm text-gray-700 font-medium">
                                                    Fecha fin <span className="font-normal text-gray-400">(opcional)</span>
                                                    <input
                                                        type="date"
                                                        value={form.tratamiento_fecha_fin}
                                                        onChange={e => setForm(f => ({ ...f, tratamiento_fecha_fin: e.target.value }))}
                                                        className="border border-gray-200 rounded-lg px-3 py-1.5 text-sm font-normal focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    />
                                                </label>

                                                <label className="flex flex-col gap-1 text-sm text-gray-700 font-medium">
                                                    Notas <span className="font-normal text-gray-400">(opcional)</span>
                                                    <input
                                                        type="text"
                                                        value={form.tratamiento_notas}
                                                        onChange={e => setForm(f => ({ ...f, tratamiento_notas: e.target.value }))}
                                                        placeholder="Dosis, frecuencia…"
                                                        className="border border-gray-200 rounded-lg px-3 py-1.5 text-sm font-normal focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    />
                                                </label>
                                            </div>
                                        </div>
                                    )}

                                    {/* Botones */}
                                    <div className="flex gap-2 pt-1">
                                        <button
                                            type="button"
                                            onClick={() => setCompletandoId(null)}
                                            className="flex-1 border border-gray-200 rounded-lg py-1.5 text-sm text-gray-600 hover:bg-gray-100 transition"
                                        >
                                            Cancelar
                                        </button>
                                        <button
                                            type="submit"
                                            className="flex-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-4 py-1.5 text-sm font-medium transition flex items-center gap-1.5"
                                        >
                                            <CheckCircle2 className="w-4 h-4" />
                                            Marcar como realizado
                                        </button>
                                    </div>
                                </form>
                            )}
                        </div>
                    );
                })}
            </div>

            {filtrados.length === 0 && search && (
                <div className="text-center py-8 text-gray-400 text-sm">
                    Sin resultados para "{search}"
                </div>
            )}
        </div>
    );
}