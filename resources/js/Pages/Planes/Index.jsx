import { Head, usePage, router } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function PlanesIndex() {
    const { precioPremium, esPremium, planActual, flash } = usePage().props;
    const [loading, setLoading] = useState(false);

    const precioFormateado = new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
    }).format(precioPremium / 100);

    const handleComprar = () => {
        if (loading) return;
        setLoading(true);
        // GET hacia el controlador que crea la sesión y redirige a Stripe
        router.visit(route('pago.premium'));
    };

    return (
        <AuthenticatedLayout>
            <Head title="Planes" />

            <div className="max-w-3xl mx-auto py-10 px-4">
                <h1 className="text-2xl font-bold mb-2">Elige tu plan</h1>
                <p className="text-sm text-gray-500 mb-6">
                    Plan actual: <span className="font-semibold">{planActual}</span>
                </p>

                {/* Flash messages */}
                {flash?.success && (
                    <div className="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
                        {flash.error}
                    </div>
                )}
                {flash?.info && (
                    <div className="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg text-blue-800 text-sm">
                        {flash.info}
                    </div>
                )}

                <div className="grid md:grid-cols-2 gap-6">
                    {/* Plan Normal */}
                    <div className="border rounded-2xl p-6 bg-white shadow-sm">
                        <h2 className="text-lg font-semibold">Plan Normal</h2>
                        <p className="text-sm text-gray-500 mt-2">
                            Acceso a registro de animales, lotes, salud y alimentación.
                        </p>
                        <p className="mt-4 text-2xl font-bold">$0 MXN</p>
                        <p className="text-xs text-gray-400 mt-1">Incluido al registrarse</p>

                        {!esPremium && (
                            <div className="mt-4 px-4 py-2 rounded-lg bg-gray-100 text-gray-600 text-sm text-center font-medium">
                                Plan actual ✓
                            </div>
                        )}
                    </div>

                    {/* Plan Premium */}
                    <div className={`border rounded-2xl p-6 shadow-sm ${esPremium ? 'bg-amber-50 border-amber-300' : 'bg-white border-amber-200'}`}>
                        <h2 className="text-lg font-semibold flex items-center gap-2">
                            Plan Premium <span>👑</span>
                        </h2>
                        <p className="text-sm text-gray-600 mt-2">
                            Incluye módulo de Predicciones con IA, análisis avanzado y reportes mejorados.
                        </p>
                        <p className="mt-4 text-2xl font-bold">{precioFormateado}</p>
                        <p className="text-xs text-gray-400 mt-1">Pago único — acceso completo</p>

                        {esPremium ? (
                            <div className="mt-4 px-4 py-2 rounded-lg bg-amber-500 text-white text-sm text-center font-semibold">
                                Plan activo ✓
                            </div>
                        ) : (
                            <button
                                onClick={handleComprar}
                                disabled={loading}
                                className="mt-4 w-full px-4 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold hover:bg-amber-600 disabled:opacity-60 disabled:cursor-not-allowed transition-colors"
                            >
                                {loading ? 'Redirigiendo a pago...' : 'Mejorar a Premium'}
                            </button>
                        )}
                    </div>
                </div>

                {/* Características Premium */}
                <div className="mt-8 border rounded-xl p-6 bg-gray-50">
                    <h3 className="font-semibold text-gray-800 mb-3">¿Qué incluye Premium?</h3>
                    <ul className="space-y-2 text-sm text-gray-600">
                        <li className="flex items-center gap-2"><span className="text-amber-500">✓</span> Módulo de Predicciones con análisis de tendencias</li>
                        <li className="flex items-center gap-2"><span className="text-amber-500">✓</span> Análisis con Inteligencia Artificial (OpenAI)</li>
                        <li className="flex items-center gap-2"><span className="text-amber-500">✓</span> Proyecciones de producción a 30 días</li>
                        <li className="flex items-center gap-2"><span className="text-amber-500">✓</span> Todos los módulos del plan Normal</li>
                    </ul>
                    <p className="text-xs text-gray-400 mt-4">
                        El pago se procesa de forma segura a través de Stripe. No almacenamos datos de tu tarjeta.
                    </p>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
