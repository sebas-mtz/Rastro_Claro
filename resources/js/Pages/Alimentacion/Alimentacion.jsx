import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { UtensilsCrossed, Package, BarChart3, Sparkles, ClipboardList } from 'lucide-react';

// Importar tabs
import Raciones from './Tabs/Raciones';
import Inventario from './Tabs/Inventario';
import Sugerencias from './tabs/Sugerencias.jsx';
import AlimentacionModal from './tabs/AlimentaciónModal';
import ConversionAlimenticia from './tabs/ConversionAlimenticia';

// Forzando compilación en Render
export default function Alimentacion() {
    const [tab, setTab] = useState('Alimentación');

    const tabs = [
        { key: 'Alimentación', label: 'Alimentación', icon: UtensilsCrossed },
        { key: 'raciones', label: 'Raciones', icon: ClipboardList },
        { key: 'inventario', label: 'Inventario', icon: Package },
        { key: 'conversion', label: 'Conversión Alimenticia', icon: BarChart3 },
        { key: 'sugerencias', label: 'Sugerencias', icon: Sparkles },
    ];

    return (
        <>
            <Head title="Alimentación" />

            <div className="py-8 px-6 max-w-7xl mx-auto space-y-6">
                {/* ENCABEZADO */}
                <div>
                    <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-800">
                                Módulo de Alimentación
                            </h1>
                            <p className="text-gray-600">
                                Optimiza la nutrición y controla los costos alimentarios.
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-3">
                            <button
                                type="button"
                                onClick={() => setTab('inventario')}
                                className="border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
                            >
                                <Package className="w-5 h-5 text-blue-600" />
                                Inventario
                            </button>

                            <button
                                type="button"
                                onClick={() => setTab('conversion')}
                                className="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
                            >
                                <BarChart3 className="w-5 h-5" />
                                Conversión
                            </button>
                        </div>
                    </div>
                </div>

                {/* TABS */}
                <div className="flex gap-6 border-b pb-3 text-gray-600 overflow-x-auto">
                    {tabs.map((t) => {
                        const Icon = t.icon;

                        return (
                            <button
                                key={t.key}
                                type="button"
                                onClick={() => setTab(t.key)}
                                className={`flex items-center gap-2 pb-2 whitespace-nowrap transition ${
                                    tab === t.key
                                        ? 'border-b-2 border-blue-600 text-blue-600 font-semibold'
                                        : 'hover:text-blue-600'
                                }`}
                            >
                                <Icon size={18} />
                                {t.label}
                            </button>
                        );
                    })}
                </div>

                {/* CONTENIDO */}
                <div className="bg-transparent">
                    {tab === 'Alimentación' && <AlimentacionModal />}
                    {tab === 'raciones' && <Raciones />}
                    {tab === 'inventario' && <Inventario />}
                    {tab === 'conversion' && <ConversionAlimenticia />}
                    {tab === 'sugerencias' && <Sugerencias />}
                </div>
            </div>
        </>
    );
}

Alimentacion.layout = (page) => <AppLayout>{page}</AppLayout>;