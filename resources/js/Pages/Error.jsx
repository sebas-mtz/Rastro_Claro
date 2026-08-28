import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { ShieldAlert, SearchX, Clock, ServerCrash, ArrowLeft } from 'lucide-react';

/**
 * Pantalla de error del sistema.
 *
 * Se muestra en lugar de la página en blanco de Laravel para que el usuario
 * sepa qué pasó. No redirige en silencio: si una acción no estaba permitida,
 * debe quedar claro que no se hizo.
 */
const PRESENTACION = {
    403: {
        icono: ShieldAlert,
        titulo: 'Acceso denegado',
        color: 'text-amber-500',
        texto: 'No tienes permiso para entrar a esta sección.',
    },
    404: {
        icono: SearchX,
        titulo: 'No encontrado',
        color: 'text-slate-400',
        texto: 'La página que buscas no existe o el registro ya no está disponible.',
    },
    419: {
        icono: Clock,
        titulo: 'La sesión expiró',
        color: 'text-blue-500',
        texto: 'Vuelve a iniciar sesión e inténtalo de nuevo.',
    },
    500: {
        icono: ServerCrash,
        titulo: 'Error del servidor',
        color: 'text-red-500',
        texto: 'Algo falló de nuestro lado. Inténtalo más tarde.',
    },
    503: {
        icono: ServerCrash,
        titulo: 'Servicio en mantenimiento',
        color: 'text-slate-500',
        texto: 'El sistema estará disponible en unos minutos.',
    },
};

export default function ErrorPage({ status, mensaje }) {
    const info = PRESENTACION[status] || PRESENTACION[500];
    const Icono = info.icono;

    return (
        <div className="min-h-screen flex items-center justify-center bg-slate-50 px-4">
            <Head title={info.titulo} />

            <div className="max-w-md w-full text-center bg-white rounded-2xl border border-slate-200 p-8">
                <Icono className={`w-16 h-16 mx-auto mb-4 ${info.color}`} />

                <p className="text-5xl font-bold text-slate-300 tabular-nums">{status}</p>
                <h1 className="mt-2 text-2xl font-bold text-slate-800">{info.titulo}</h1>

                {/* El mensaje del backend explica el motivo concreto; si no
                    viene, se usa el texto genérico del código. */}
                <p className="mt-3 text-slate-600">{mensaje || info.texto}</p>

                {status === 403 && (
                    <p className="mt-4 text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-lg p-3">
                        Si necesitas este acceso, pídeselo al superadministrador del sistema.
                    </p>
                )}

                <div className="mt-6 flex flex-wrap justify-center gap-3">
                    <Link
                        href="/dashboard"
                        className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium"
                    >
                        <ArrowLeft className="w-4 h-4" /> Ir al panel
                    </Link>
                    <button
                        onClick={() => window.history.back()}
                        className="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm"
                    >
                        Volver
                    </button>
                </div>
            </div>
        </div>
    );
}
