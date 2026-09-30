import React, { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { X, Users } from 'lucide-react';
import MatrizPermisos from './MatrizPermisos';

export default function PermisosPuestoModal({
    show,
    puesto = null,
    modulos = [],
    acciones = {},
    onClose,
}) {
    const [permisos, setPermisos] = useState({});
    const [guardando, setGuardando] = useState(false);

    useEffect(() => {
        if (show && puesto) {
            setPermisos(puesto.permisos || {});
        }
    }, [show, puesto?.id]);

    const guardar = () => {
        setGuardando(true);

        router.put(
            route('admin.permisos.puesto', puesto.id),
            { permisos },
            {
                preserveScroll: true,
                onFinish: () => setGuardando(false),
                onSuccess: () => onClose(),
            },
        );
    };

    if (!show || !puesto) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-start justify-center bg-black/50 overflow-y-auto p-4">
            <div className="bg-white rounded-2xl shadow-xl w-full max-w-4xl my-8">
                <div className="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                    <div>
                        <h3 className="text-lg font-semibold text-slate-800">{puesto.nombre}</h3>
                        <p className="text-sm text-slate-500">
                            {puesto.area || 'Sin área'}
                        </p>
                    </div>
                    <button onClick={onClose} className="text-slate-400 hover:text-slate-700" aria-label="Cerrar">
                        <X className="w-5 h-5" />
                    </button>
                </div>

                <div className="px-6 py-5 space-y-4">
                    {puesto.personas > 0 && (
                        <p className="text-sm text-slate-700 bg-slate-50 border border-slate-200 rounded-lg p-3 flex items-start gap-2">
                            <Users className="w-4 h-4 mt-0.5 shrink-0 text-slate-400" />
                            <span>
                                {puesto.personas === 1
                                    ? 'Hay 1 persona con este puesto. El cambio la afecta en cuanto guardes.'
                                    : `Hay ${puesto.personas} personas con este puesto. El cambio las afecta a todas en cuanto guardes.`}
                            </span>
                        </p>
                    )}

                    <MatrizPermisos
                        modulos={modulos}
                        acciones={acciones}
                        valor={permisos}
                        onChange={setPermisos}
                    />

                    <p className="text-xs text-slate-500">
                        Las filas resaltadas contienen información económica del rancho.
                    </p>
                </div>

                <div className="flex justify-end gap-3 px-6 py-4 border-t border-slate-200">
                    <button
                        onClick={onClose}
                        className="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm"
                    >
                        Cancelar
                    </button>
                    <button
                        onClick={guardar}
                        disabled={guardando}
                        className="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium disabled:opacity-60"
                    >
                        {guardando ? 'Guardando…' : 'Guardar permisos'}
                    </button>
                </div>
            </div>
        </div>
    );
}
