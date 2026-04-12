import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

// Importar tabs
import Raciones from './Tabs/Raciones';
import Conversion from './Tabs/Conversion';
import Inventario from './Tabs/Inventario';
import Sugerencias from './tabs/Sugerencias.jsx';
import AlimentacionModal from './tabs/AlimentaciónModal';

export default function Alimentacion() {
    const [tab, setTab] = useState('Alimentación');

    const tabs = [
        { key: 'Alimentación', label: 'Alimentacion' },
        { key: 'raciones', label: 'Raciones' },
        { key: 'inventario', label: 'Inventario' },
        { key: 'conversion', label: 'Conversión Alimenticia' },
        { key: 'sugerencias', label: 'Sugerencias' },
    ];

    return (
        <>
            <Head title="Alimentación" />

            <div className="px-6 py-5">
                <h1 className="text-2xl font-semibold text-gray-900">
                    Módulo de Alimentación
                </h1>
                <p className="text-gray-500 text-sm">
                    Optimiza la nutrición y controla los costos alimentarios.
                </p>

                {/* TABS */}
                <div className="flex gap-5 mt-6 border-b">
                    {tabs.map((t) => (
                        <button
                            key={t.key}
                            onClick={() => setTab(t.key)}
                            className={`pb-2 text-sm font-medium ${
                                tab === t.key
                                    ? "border-b-2 border-green-600 text-green-600"
                                    : "text-gray-500 hover:text-gray-700"
                            }`}
                        >
                            {t.label}
                        </button>
                    ))}
                </div>

                {/* CONTENIDO DE TABS */}
                <div className="mt-6">
                {tab === 'Alimentación' && <AlimentacionModal />}
                    {tab === 'raciones' && <Raciones />}
                    {tab === 'inventario' && <Inventario />}
                    {tab === 'conversion' && <Conversion />}
                    {tab === 'sugerencias' && <Sugerencias />}
                </div>
            </div>
        </>
    );
}

Alimentacion.layout = (page) => <AppLayout>{page}</AppLayout>;
