import React from 'react';
import { Banknote } from 'lucide-react';

/**
 * Cuadrícula de módulos × acciones.
 *
 * La usan tanto la configuración de un puesto como la de una persona. El
 * estado vive en el componente que la incluye; aquí solo se pinta y se avisa
 * de cada cambio.
 */
export default function MatrizPermisos({
    modulos = [],
    acciones = {},
    valor = {},
    onChange,
    tono = 'emerald',
    deshabilitado = false,
}) {
    const clavesAccion = Object.keys(acciones);

    const tiene = (modulo, accion) => (valor[modulo] || []).includes(accion);

    const alternar = (modulo, accion) => {
        if (deshabilitado) return;

        const actuales = valor[modulo] || [];
        const nuevas = actuales.includes(accion)
            ? actuales.filter((a) => a !== accion)
            : [...actuales, accion];

        const siguiente = { ...valor };

        if (nuevas.length === 0) {
            delete siguiente[modulo];
        } else {
            siguiente[modulo] = nuevas;
        }

        onChange(siguiente);
    };

    /** Marca o desmarca la fila completa de un módulo. */
    const alternarFila = (modulo) => {
        if (deshabilitado) return;

        const completo = clavesAccion.every((a) => tiene(modulo, a));
        const siguiente = { ...valor };

        if (completo) {
            delete siguiente[modulo];
        } else {
            siguiente[modulo] = [...clavesAccion];
        }

        onChange(siguiente);
    };

    const colorCasilla = tono === 'red' ? 'accent-red-600' : 'accent-emerald-600';

    return (
        <div className="overflow-x-auto border border-slate-200 rounded-xl">
            <table className="min-w-full text-sm">
                <thead className="bg-slate-50">
                    <tr>
                        <th className="px-3 py-2 text-left text-xs font-medium text-slate-500 uppercase sticky left-0 bg-slate-50">
                            Módulo
                        </th>
                        {clavesAccion.map((a) => (
                            <th key={a} className="px-3 py-2 text-center text-xs font-medium text-slate-500 uppercase">
                                {acciones[a]}
                            </th>
                        ))}
                        <th className="px-3 py-2 text-center text-xs font-medium text-slate-500 uppercase">Todo</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                    {modulos.map((m) => (
                        <tr key={m.clave} className={m.economico ? 'bg-amber-50/40' : ''}>
                            <td className="px-3 py-2 sticky left-0 bg-inherit">
                                <span className="font-medium text-slate-800 flex items-center gap-1">
                                    {m.nombre}
                                    {m.economico && (
                                        <Banknote
                                            className="w-3.5 h-3.5 text-amber-600"
                                            aria-label="Información económica"
                                        />
                                    )}
                                </span>
                                <span className="block text-xs text-slate-400">{m.descripcion}</span>
                            </td>

                            {clavesAccion.map((a) => (
                                <td key={a} className="px-3 py-2 text-center">
                                    <input
                                        type="checkbox"
                                        checked={tiene(m.clave, a)}
                                        onChange={() => alternar(m.clave, a)}
                                        disabled={deshabilitado}
                                        className={`w-4 h-4 ${colorCasilla} disabled:opacity-40`}
                                        aria-label={`${acciones[a]} en ${m.nombre}`}
                                    />
                                </td>
                            ))}

                            <td className="px-3 py-2 text-center">
                                <button
                                    type="button"
                                    onClick={() => alternarFila(m.clave)}
                                    disabled={deshabilitado}
                                    className="text-xs text-slate-500 hover:text-slate-800 disabled:opacity-40"
                                >
                                    {clavesAccion.every((a) => tiene(m.clave, a)) ? 'Quitar' : 'Todo'}
                                </button>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
