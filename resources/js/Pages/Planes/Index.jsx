import { Head, useForm, usePage, Link } from '@inertiajs/react'; // Asegúrate de importar Link
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function PlanesIndex() {
    const { precioPremium } = usePage().props;
    const { post, processing } = useForm({});

    const handleComprar = () => {
        post(route('pago.premium'));
    };

    return (
        <AuthenticatedLayout>
            <Head title="Planes" />
            <div className="max-w-3xl mx-auto py-10 px-4">
                <h1 className="text-2xl font-bold mb-4">Elige tu plan</h1>

                <div className="grid md:grid-cols-2 gap-6">
                    <div className="border rounded-2xl p-6 bg-white">
                        <h2 className="text-lg font-semibold">Plan Normal</h2>
                        <p className="text-sm text-gray-500 mt-2">
                            Acceso básico a registro de animales, lotes y salud.
                        </p>
                        <p className="mt-4 text-2xl font-bold">$0 MXN</p>
                        <p className="text-xs text-gray-400">Incluido</p>
                    </div>

                    <div className="border rounded-2xl p-6 bg-amber-50 border-amber-200">
                        <h2 className="text-lg font-semibold flex items-center gap-2">
                            Plan Premium <span>👑</span>
                        </h2>
                        <p className="text-sm text-gray-600 mt-2">
                            Incluye módulos de Predicciones, IA, análisis avanzado y reportes mejorados.
                        </p>
                        <p className="mt-4 text-2xl font-bold">
                            ${(precioPremium / 100).toFixed(2)} MXN
                        </p>
                        <p className="text-xs text-gray-400">Pago único (ejemplo)</p>

                        {/* Aquí cambiaremos la lógica */}
                        <Link
                          href={route('predicciones.index')} // Redirige a la página de Predicciones
                          className="mt-4 inline-block px-4 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold hover:bg-amber-600">
                          Ir a Predicciones
                        </Link>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}