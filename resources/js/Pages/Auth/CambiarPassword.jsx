import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Lock, Eye, EyeOff, CheckCircle, XCircle } from 'lucide-react';

function Requisito({ ok, texto }) {
    return (
        <li className={`flex items-center gap-2 text-xs ${ok ? 'text-emerald-600' : 'text-gray-400'}`}>
            {ok
                ? <CheckCircle className="w-3.5 h-3.5 flex-shrink-0" />
                : <XCircle    className="w-3.5 h-3.5 flex-shrink-0" />
            }
            {texto}
        </li>
    );
}

export default function CambiarPassword() {
    const { errors } = usePage().props;
    const [form, setForm]           = useState({ password: '', password_confirmation: '' });
    const [verPass, setVerPass]     = useState(false);
    const [verPass2, setVerPass2]   = useState(false);
    const [processing, setProcessing] = useState(false);

    const p = form.password;
    const requisitos = {
        largo:   p.length >= 8,
        letra:   /[A-Za-z]/.test(p),
        numero:  /[0-9]/.test(p),
        coincide: p.length > 0 && p === form.password_confirmation,
    };
    const valido = Object.values(requisitos).every(Boolean);

    function submit(e) {
        e.preventDefault();
        if (!valido) return;
        setProcessing(true);
        router.post(route('password.cambiar.update'), form, {
            onError:   () => setProcessing(false),
            onFinish:  () => setProcessing(false),
        });
    }

    return (
        <>
            <Head title="Cambiar contraseña" />
            <div className="min-h-screen bg-gradient-to-br from-emerald-50 to-teal-100
                            flex items-center justify-center p-4">
                <div className="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden">

                    {/* Header */}
                    <div className="bg-gradient-to-r from-emerald-600 to-teal-500 px-8 py-8 text-center">
                        <div className="inline-flex items-center justify-center w-16 h-16
                                        bg-white/20 rounded-full mb-4">
                            <Lock className="w-8 h-8 text-white" />
                        </div>
                        <h1 className="text-2xl font-bold text-white">Crea tu contraseña</h1>
                        <p className="text-emerald-100 text-sm mt-1">
                            Por seguridad debes establecer una contraseña personal
                        </p>
                    </div>

                    {/* Form */}
                    <form onSubmit={submit} className="px-8 py-6 space-y-5">

                        {/* Nueva contraseña */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Nueva contraseña
                            </label>
                            <div className="relative">
                                <input
                                    type={verPass ? 'text' : 'password'}
                                    value={form.password}
                                    onChange={e => setForm(f => ({ ...f, password: e.target.value }))}
                                    placeholder="Mínimo 8 caracteres"
                                    className="w-full border border-gray-300 rounded-lg px-3 py-2.5 pr-10
                                               text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
                                    autoFocus
                                />
                                <button type="button" onClick={() => setVerPass(v => !v)}
                                    className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400
                                               hover:text-gray-600">
                                    {verPass ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                                </button>
                            </div>
                            {errors.password && (
                                <p className="text-red-500 text-xs mt-1">{errors.password}</p>
                            )}
                        </div>

                        {/* Requisitos */}
                        {form.password.length > 0 && (
                            <ul className="space-y-1 px-1">
                                <Requisito ok={requisitos.largo}   texto="Al menos 8 caracteres" />
                                <Requisito ok={requisitos.letra}   texto="Contiene letras" />
                                <Requisito ok={requisitos.numero}  texto="Contiene números" />
                                <Requisito ok={requisitos.coincide} texto="Las contraseñas coinciden" />
                            </ul>
                        )}

                        {/* Confirmar */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Confirmar contraseña
                            </label>
                            <div className="relative">
                                <input
                                    type={verPass2 ? 'text' : 'password'}
                                    value={form.password_confirmation}
                                    onChange={e => setForm(f => ({ ...f, password_confirmation: e.target.value }))}
                                    placeholder="Repite la contraseña"
                                    className="w-full border border-gray-300 rounded-lg px-3 py-2.5 pr-10
                                               text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
                                />
                                <button type="button" onClick={() => setVerPass2(v => !v)}
                                    className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400
                                               hover:text-gray-600">
                                    {verPass2 ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                                </button>
                            </div>
                        </div>

                        <button
                            type="submit"
                            disabled={!valido || processing}
                            className="w-full py-3 rounded-xl bg-emerald-600 text-white font-semibold
                                       text-sm hover:bg-emerald-700 disabled:opacity-50
                                       disabled:cursor-not-allowed transition-colors"
                        >
                            {processing ? 'Guardando...' : 'Guardar contraseña y entrar →'}
                        </button>
                    </form>
                </div>
            </div>
        </>
    );
}
