import React, { useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { X, KeyRound } from 'lucide-react';

/**
 * Asigna una contraseña nueva a otra cuenta.
 *
 * No muestra la contraseña anterior porque no existe forma de leerla: se
 * guarda cifrada y solo puede reemplazarse.
 */
export default function PasswordModal({ show, usuario = null, onClose }) {
    const { data, setData, patch, processing, errors, reset, clearErrors } = useForm({
        password: '',
        password_confirmation: '',
    });

    useEffect(() => {
        if (show) {
            reset();
            clearErrors();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [show, usuario?.id]);

    const enviar = (e) => {
        e.preventDefault();

        patch(route('admin.usuarios.password', usuario.id), {
            preserveScroll: true,
            onSuccess: () => { reset(); onClose(); },
        });
    };

    if (!show || !usuario) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div className="bg-white rounded-2xl shadow-xl w-full max-w-md">
                <div className="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                    <h3 className="text-lg font-semibold text-slate-800 flex items-center gap-2">
                        <KeyRound className="w-5 h-5 text-slate-400" /> Restablecer contraseña
                    </h3>
                    <button onClick={onClose} className="text-slate-400 hover:text-slate-700" aria-label="Cerrar">
                        <X className="w-5 h-5" />
                    </button>
                </div>

                <form onSubmit={enviar} className="px-6 py-5 space-y-4">
                    <p className="text-sm text-slate-600">
                        Se asignará una contraseña nueva a{' '}
                        <span className="font-medium text-slate-800">{usuario.name}</span>{' '}
                        <span className="text-slate-500">({usuario.email})</span>.
                    </p>

                    <p className="text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-lg p-3">
                        La contraseña actual no puede consultarse: el sistema solo guarda su versión
                        cifrada. Comunícale la nueva por un medio seguro y pídele que la cambie desde
                        su perfil.
                    </p>

                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">Contraseña nueva</label>
                        <input
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                            required
                            autoComplete="new-password"
                        />
                        {errors.password && <p className="text-xs text-red-600 mt-1">{errors.password}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">Confirmar</label>
                        <input
                            type="password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                            required
                            autoComplete="new-password"
                        />
                    </div>

                    <div className="flex justify-end gap-3 pt-2 border-t border-slate-200">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium disabled:opacity-60"
                        >
                            {processing ? 'Guardando…' : 'Restablecer'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
