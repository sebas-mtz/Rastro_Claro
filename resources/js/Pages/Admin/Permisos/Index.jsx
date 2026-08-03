import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { ShieldCheck, Briefcase, UserCog, ArrowLeft, Banknote, Check } from 'lucide-react';
import PermisosPuestoModal from './PermisosPuestoModal';
import PermisosPersonaModal from './PermisosPersonaModal';

export default function PermisosIndex({
    auth,
    puestos = [],
    modulos = [],
    acciones = {},
    personas = [],
}) {
    const [modalPuesto, setModalPuesto] = useState({ show: false, puesto: null });
    const [modalPersona, setModalPersona] = useState({ show: false, persona: null });

    const economicos = modulos.filter((m) => m.economico).map((m) => m.clave);

    /** Cuántos módulos toca un puesto, y si alguno es de dinero. */
    const resumenDe = (permisos) => {
        const claves = Object.keys(permisos || {}).filter((k) => (permisos[k] || []).length > 0);

        return {
            total: claves.length,
            conDinero: claves.filter((k) => economicos.includes(k)),
        };
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800">Permisos</h2>}
        >
            <Head title="Permisos" />

            <div className="py-8 px-4 sm:px-6 max-w-7xl mx-auto space-y-6">
                <Link
                    href={route('admin.usuarios.index')}
                    className="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1"
                >
                    <ArrowLeft className="w-4 h-4" /> Volver a usuarios
                </Link>

                <div>
                    <h1 className="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <ShieldCheck className="w-6 h-6 text-slate-400" /> Permisos del rancho
                    </h1>
                    <p className="text-gray-600">
                        Qué módulos puede tocar cada puesto, y las excepciones de cada persona.
                    </p>
                </div>

                <p className="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-start gap-2">
                    <Banknote className="w-4 h-4 mt-0.5 shrink-0" />
                    <span>
                        Los módulos con información económica —Costos, Valuación y Ventas— vienen
                        apagados en todos los puestos. Concédelos solo a quien deba ver el dinero
                        del rancho.
                    </span>
                </p>

                {/* ── Puestos ────────────────────────────────────────────── */}
                <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div className="px-5 py-4 border-b border-slate-200">
                        <h2 className="font-semibold text-slate-800 flex items-center gap-2">
                            <Briefcase className="w-4 h-4 text-slate-400" /> Por puesto
                        </h2>
                        <p className="text-sm text-slate-500 mt-1">
                            Lo que recibe cualquier persona con ese puesto.
                        </p>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-slate-200">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Puesto</th>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Área</th>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Módulos</th>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Dinero</th>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Personas</th>
                                    <th className="px-5 py-3 text-right text-xs font-medium text-slate-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-slate-100">
                                {puestos.map((p) => {
                                    const resumen = resumenDe(p.permisos);

                                    return (
                                        <tr key={p.id} className={'hover:bg-slate-50 ' + (p.activo ? '' : 'opacity-60')}>
                                            <td className="px-5 py-3 text-sm font-medium text-slate-800">{p.nombre}</td>
                                            <td className="px-5 py-3 text-sm text-slate-600">
                                                {p.area || <span className="text-slate-400">—</span>}
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-600 tabular-nums">
                                                {resumen.total === 0
                                                    ? <span className="text-slate-400">Ninguno</span>
                                                    : `${resumen.total} de ${modulos.length}`}
                                            </td>
                                            <td className="px-5 py-3 text-sm">
                                                {resumen.conDinero.length > 0 ? (
                                                    <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-800">
                                                        Sí ({resumen.conDinero.length})
                                                    </span>
                                                ) : (
                                                    <span className="text-slate-400 text-xs">No</span>
                                                )}
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-600 tabular-nums">{p.personas}</td>
                                            <td className="px-5 py-3 text-right">
                                                <button
                                                    onClick={() => setModalPuesto({ show: true, puesto: p })}
                                                    className="text-sm text-emerald-700 hover:underline"
                                                >
                                                    Configurar
                                                </button>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* ── Personas ───────────────────────────────────────────── */}
                <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div className="px-5 py-4 border-b border-slate-200">
                        <h2 className="font-semibold text-slate-800 flex items-center gap-2">
                            <UserCog className="w-4 h-4 text-slate-400" /> Por persona
                        </h2>
                        <p className="text-sm text-slate-500 mt-1">
                            Excepciones sobre lo que da el puesto, sin tener que cambiárselo.
                        </p>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-slate-200">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Persona</th>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Rol</th>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Puesto</th>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Módulos</th>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-slate-500 uppercase">Excepciones</th>
                                    <th className="px-5 py-3 text-right text-xs font-medium text-slate-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-slate-100">
                                {personas.map((p) => {
                                    const conceder = Object.keys(p.permisos_extra?.conceder || {}).length;
                                    const revocar = Object.keys(p.permisos_extra?.revocar || {}).length;
                                    const efectivos = Object.keys(p.permisos_efectivos || {}).length;

                                    return (
                                        <tr key={p.id} className="hover:bg-slate-50">
                                            <td className="px-5 py-3 text-sm font-medium text-slate-800">
                                                {p.name}
                                                <span className="block text-xs font-normal text-slate-400">{p.email}</span>
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-600">{p.rol_legible}</td>
                                            <td className="px-5 py-3 text-sm text-slate-600">
                                                {p.puesto_nombre || <span className="text-slate-400">Sin puesto</span>}
                                            </td>
                                            <td className="px-5 py-3 text-sm text-slate-600 tabular-nums">
                                                {p.es_dueno ? (
                                                    <span className="inline-flex items-center gap-1 text-xs text-emerald-700">
                                                        <Check className="w-3 h-3" /> Todos (dueño)
                                                    </span>
                                                ) : (
                                                    `${efectivos} de ${modulos.length}`
                                                )}
                                            </td>
                                            <td className="px-5 py-3 text-xs text-slate-600">
                                                {conceder === 0 && revocar === 0 ? (
                                                    <span className="text-slate-400">Ninguna</span>
                                                ) : (
                                                    <>
                                                        {conceder > 0 && <span className="text-emerald-700">+{conceder} </span>}
                                                        {revocar > 0 && <span className="text-red-600">−{revocar}</span>}
                                                    </>
                                                )}
                                            </td>
                                            <td className="px-5 py-3 text-right">
                                                {p.es_dueno ? (
                                                    <span className="text-xs text-slate-400">Sin límites</span>
                                                ) : (
                                                    <button
                                                        onClick={() => setModalPersona({ show: true, persona: p })}
                                                        className="text-sm text-emerald-700 hover:underline"
                                                    >
                                                        Configurar
                                                    </button>
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </div>

                <p className="text-xs text-slate-500">
                    Estos permisos los aplica el servidor en cada petición. Ocultar un botón no
                    basta: quien escriba la dirección a mano de un módulo que no le toca recibe un
                    error 403.
                </p>
            </div>

            <PermisosPuestoModal
                show={modalPuesto.show}
                puesto={modalPuesto.puesto}
                modulos={modulos}
                acciones={acciones}
                onClose={() => setModalPuesto({ show: false, puesto: null })}
            />

            <PermisosPersonaModal
                show={modalPersona.show}
                persona={modalPersona.persona}
                puestos={puestos}
                modulos={modulos}
                acciones={acciones}
                onClose={() => setModalPersona({ show: false, persona: null })}
            />
        </AuthenticatedLayout>
    );
}
