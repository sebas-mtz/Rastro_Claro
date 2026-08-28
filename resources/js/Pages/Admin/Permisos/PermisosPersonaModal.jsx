import React, { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { X, PlusCircle, MinusCircle } from 'lucide-react';
import MatrizPermisos from './MatrizPermisos';

/**
 * Excepciones de una persona sobre lo que le da su puesto.
 *
 * Se separan a propósito en "conceder" y "quitar" en vez de mostrar el
 * resultado final editable: así queda a la vista qué se cambió respecto al
 * puesto, que es lo que después hay que poder explicar.
 */
export default function PermisosPersonaModal({
    show,
    persona = null,
    puestos = [],
    modulos = [],
    acciones = {},
    onClose,
}) {
    const [puestoId, setPuestoId] = useState('');
    const [conceder, setConceder] = useState({});
    const [revocar, setRevocar] = useState({});
    const [guardando, setGuardando] = useState(false);
    const [pestana, setPestana] = useState('conceder');

    useEffect(() => {
        if (show && persona) {
            setPuestoId(persona.puesto_id ?? '');
            setConceder(persona.permisos_extra?.conceder || {});
            setRevocar(persona.permisos_extra?.revocar || {});
            setPestana('conceder');
        }
    }, [show, persona?.id]);

    const guardar = () => {
        setGuardando(true);

        router.put(
            route('admin.permisos.persona', persona.id),
            { puesto_id: puestoId || null, conceder, revocar },
            {
                preserveScroll: true,
                onFinish: () => setGuardando(false),
                onSuccess: () => onClose(),
            },
        );
    };

    if (!show || !persona) return null;

    const puestoActual = puestos.find((p) => String(p.id) === String(puestoId));

    return (
        <div className="fixed inset-0 z-50 flex items-start justify-center bg-black/50 overflow-y-auto p-4">
            <div className="bg-white rounded-2xl shadow-xl w-full max-w-4xl my-8">
                <div className="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                    <div>
                        <h3 className="text-lg font-semibold text-slate-800">{persona.name}</h3>
                        <p className="text-sm text-slate-500">{persona.email} · {persona.rol_legible}</p>
                    </div>
                    <button onClick={onClose} className="text-slate-400 hover:text-slate-700" aria-label="Cerrar">
                        <X className="w-5 h-5" />
                    </button>
                </div>

                <div className="px-6 py-5 space-y-5">
                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">Puesto</label>
                        <select
                            value={puestoId}
                            onChange={(e) => setPuestoId(e.target.value)}
                            className="w-full sm:w-80 border border-slate-300 rounded-lg px-3 py-2 text-sm"
                        >
                            <option value="">Sin puesto — sin acceso a ningún módulo</option>
                            {puestos.map((p) => (
                                <option key={p.id} value={p.id}>{p.nombre}</option>
                            ))}
                        </select>
                        <p className="text-xs text-slate-500 mt-1">
                            {puestoActual
                                ? `Recibe lo configurado en «${puestoActual.nombre}», más y menos lo de abajo.`
                                : 'Sin puesto solo se puede entrar al panel; los módulos quedan cerrados.'}
                        </p>
                    </div>

                    {persona.rol_legible === 'Administrador' && (
                        <p className="text-sm text-blue-800 bg-blue-50 border border-blue-200 rounded-lg p-3">
                            Esta cuenta tiene rol de Administrador, así que maneja toda la operación
                            del rancho sin importar su puesto. Estas excepciones no le quitan ese
                            acceso: para limitarla, cámbiale el rol a Trabajador desde Usuarios.
                        </p>
                    )}

                    {/* Pestañas */}
                    <div className="flex gap-2 border-b border-slate-200">
                        <button
                            onClick={() => setPestana('conceder')}
                            className={
                                'px-4 py-2 text-sm font-medium border-b-2 flex items-center gap-1 ' +
                                (pestana === 'conceder'
                                    ? 'border-emerald-600 text-emerald-700'
                                    : 'border-transparent text-slate-500 hover:text-slate-800')
                            }
                        >
                            <PlusCircle className="w-4 h-4" /> Conceder de más
                        </button>
                        <button
                            onClick={() => setPestana('revocar')}
                            className={
                                'px-4 py-2 text-sm font-medium border-b-2 flex items-center gap-1 ' +
                                (pestana === 'revocar'
                                    ? 'border-red-600 text-red-700'
                                    : 'border-transparent text-slate-500 hover:text-slate-800')
                            }
                        >
                            <MinusCircle className="w-4 h-4" /> Quitar
                        </button>
                    </div>

                    {pestana === 'conceder' ? (
                        <>
                            <p className="text-sm text-slate-600">
                                Lo que esta persona tendrá <strong>además</strong> de lo que da su puesto.
                            </p>
                            <MatrizPermisos
                                modulos={modulos}
                                acciones={acciones}
                                valor={conceder}
                                onChange={setConceder}
                            />
                        </>
                    ) : (
                        <>
                            <p className="text-sm text-slate-600">
                                Lo que se le <strong>retira</strong> aunque su puesto lo incluya.
                            </p>
                            <MatrizPermisos
                                modulos={modulos}
                                acciones={acciones}
                                valor={revocar}
                                onChange={setRevocar}
                                tono="red"
                            />
                        </>
                    )}
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
