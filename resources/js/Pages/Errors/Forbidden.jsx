import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { ShieldOff, ArrowLeft } from 'lucide-react';

export default function Forbidden({ modulo, accion }) {
    return (
        <>
            <Head title="Acceso denegado" />
            <div className="min-h-[60vh] flex items-center justify-center px-4">
                <div className="text-center max-w-md">
                    <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <ShieldOff className="w-8 h-8 text-red-500" />
                    </div>
                    <h1 className="text-2xl font-bold text-gray-800 mb-2">Acceso denegado</h1>
                    <p className="text-gray-500 mb-1">
                        No tienes permiso para <strong>{accion}</strong> en <strong>{modulo}</strong>.
                    </p>
                    <p className="text-gray-400 text-sm mb-6">
                        Si crees que esto es un error, contacta al administrador del sistema.
                    </p>
                    <Link href="/dashboard"
                        className="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-medium hover:bg-emerald-700">
                        <ArrowLeft className="w-4 h-4" />
                        Volver al inicio
                    </Link>
                </div>
            </div>
        </>
    );
}

Forbidden.layout = page => <AppLayout>{page}</AppLayout>;
