import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import TabTermos from '@Pages/Genetica/Tabtermos';
import TabPajillas from '@Pages/Genetica/Tabpajillas';
import ModalTermo from '@Pages/Genetica/Modaltermo';
import ModalPajilla from '@Pages/Genetica/ModalPajilla';
import ModalDonadorExterno from "@Pages/Genetica/ModalDonadorExterno";
import {
    FlaskConical,
    Droplets,
    PackageCheck,
    PackageX,
    BadgePlus,
    Layers,
} from 'lucide-react';

const TABS = [
    { key: 'termos',   label: 'Termos',   icon: FlaskConical },
    { key: 'pajillas', label: 'Pajillas',  icon: Droplets },
];

export default function Index({
    termos,
    pajillas,
    animales = [],
    donadoresExternos = [],
    activeTab: initialTab = 'termos',
    stats = {},
}) {
    const { flash } = usePage().props || {};

    const [tab, setTab]                   = useState(initialTab);
    const [termoModalOpen, setTermoModalOpen] = useState(false);
    const [pajillaModalOpen, setPajillaModalOpen] = useState(false);
    const [donadorModalOpen, setDonadorModalOpen] = useState(false);
    // Lista plana de termos activos para el select del modal de pajilla
    const termosList = Array.isArray(termos) ? termos : (termos?.data ?? []);

    const summaryCards = [
        {
            title: 'Termos activos',
            value: stats.termos_activos ?? termosList.filter((t) => t.estado === 'activo').length,
            subtitle: 'Contenedores operativos',
            icon: FlaskConical,
            border: 'border-blue-500',
            iconColor: 'text-blue-600',
        },
        {
            title: 'Pajillas disponibles',
            value: stats.pajillas_disponibles ?? 0,
            subtitle: 'Listas para usar',
            icon: PackageCheck,
            border: 'border-emerald-500',
            iconColor: 'text-emerald-600',
        },
        {
            title: 'Pajillas vencidas',
            value: stats.pajillas_vencidas ?? 0,
            subtitle: 'Requieren revisión',
            icon: PackageX,
            border: 'border-red-500',
            iconColor: 'text-red-500',
        },
        {
            title: 'Total pajillas',
            value: stats.pajillas_total ?? (pajillas?.meta?.total ?? pajillas?.total ?? 0),
            subtitle: 'Inventario registrado',
            icon: Layers,
            border: 'border-cyan-500',
            iconColor: 'text-cyan-600',
        },
    ];

    return (
        <>
            <Head title="Material Genético" />

            <div className="py-8 px-6 max-w-7xl mx-auto space-y-6">

                {/* ENCABEZADO */}
                <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-800">Material Genético</h1>
                        <p className="text-gray-600">
                            Gestiona termos de nitrógeno y pajillas de semen de tu ganado.
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-3">
                        <button
                            type="button"
                            onClick={() => setTermoModalOpen(true)}  
                              className="border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
                        >
                            <BadgePlus className="w-5 h-5 text-blue-600" />
                            Nuevo termo
                        </button>

                        <button
                            type="button"
                            onClick={() => setPajillaModalOpen(true)}
                            className="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
                        >
                            <Droplets className="w-5 h-5" />
                            Nueva pajilla
                        </button>

                        <button
                            type="button"
                            onClick={() => setDonadorModalOpen(true)}
                            className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                        >
                            Nuevo donador externo
                        </button>
                    </div>
                </div>

                {/* ALERTAS */}
                {stats.pajillas_vencidas > 0 && (
                    <div className="rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">
                        ⚠ Tienes {stats.pajillas_vencidas} pajilla(s) vencida(s) que requieren atención.
                    </div>
                )}

                {flash?.success && (
                    <div className="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 px-4 py-3 text-sm">
                        ✓ {flash.success}
                    </div>
                )}

                {flash?.error && (
                    <div className="rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">
                        ✕ {flash.error}
                    </div>
                )}

                {/* CARDS DE RESUMEN */}
                <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    {summaryCards.map((card) => {
                        const CardIcon = card.icon;
                        return (
                            <div
                                key={card.title}
                                className={`bg-white rounded-2xl shadow p-5 border-l-4 ${card.border}`}
                            >
                                <div className="flex justify-between items-center mb-4">
                                    <span className="text-gray-700 font-medium text-sm">{card.title}</span>
                                    <CardIcon className={`w-6 h-6 ${card.iconColor}`} />
                                </div>
                                <div className="text-2xl font-bold text-gray-800 mb-1">{card.value}</div>
                                <p className="text-sm text-gray-500">{card.subtitle}</p>
                            </div>
                        );
                    })}
                </div>

                {/* PANEL DE TABS */}
                <div className="bg-white rounded-2xl shadow border border-gray-100 overflow-hidden">

                    {/* Tab bar */}
                    <div className="flex gap-6 border-b px-5 pt-5 pb-3 text-gray-600 overflow-x-auto">
                        {TABS.map((t) => {
                            const TabIcon = t.icon;
                            return (
                                <button
                                    key={t.key}
                                    type="button"
                                    onClick={() => setTab(t.key)}
                                    className={`relative flex items-center gap-2 pb-2 whitespace-nowrap transition ${
                                        tab === t.key
                                            ? 'border-b-2 border-blue-600 text-blue-600 font-semibold'
                                            : 'hover:text-blue-600'
                                    }`}
                                >
                                    <TabIcon size={18} />
                                    {t.label}
                                </button>
                            );
                        })}
                    </div>

                    {/* Tab content */}
                    <div className="p-5">
                        {tab === 'termos' && (
                            <TabTermos termos={termos} />
                        )}
                        {tab === 'pajillas' && (
                            <TabPajillas
                                pajillas={pajillas}
                                termos={termosList}
                                animales={animales}
                                donadoresExternos={donadoresExternos}
                            />
                        )}
                    </div>
                </div>
            </div>

            {/* Modales globales (crear) */}
            <ModalTermo
                isOpen={termoModalOpen}
                onClose={() => setTermoModalOpen(false)}
            />

<ModalPajilla
    isOpen={pajillaModalOpen}
    onClose={() => setPajillaModalOpen(false)}
    termos={termosList}
    animales={animales}
    donadoresExternos={donadoresExternos}
    onAgregarDonadorExterno={() => {setPajillaModalOpen(false);setDonadorModalOpen(true);}}
/>
<ModalDonadorExterno
    isOpen={donadorModalOpen}
    onClose={() => {setDonadorModalOpen(false);setPajillaModalOpen(true);}}
/>
        </>
    );
}

Index.layout = (page) => <AppLayout>{page}</AppLayout>;