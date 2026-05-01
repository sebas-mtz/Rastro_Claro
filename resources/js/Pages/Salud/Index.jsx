import { Head, router, usePage, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import ModalNuevaCita from './ModalNuevaCita';
import TabVacunaciones from './TabVacunaciones';
import TabTratamientos from './TabTratamientos';
import TabEventos from './TabEventos';
import TabEstadisticas from './TabEstadisticas';
import TabRecomendaciones from './TabRecomendaciones';
import {
    CalendarDays,
    Syringe,
    Stethoscope,
    Activity,
    BadgePlus,
    ShieldCheck,
    TrendingUp,
    Sparkles,
    ChevronLeft,
    ChevronRight,
} from 'lucide-react';

const MONTH_NAMES = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
];

const TABS = [
    { key: 'vacunas', label: 'Vacunaciones', icon: Syringe },
    { key: 'eventos', label: 'Consultas', icon: Stethoscope },
    { key: 'tratamientos', label: 'Tratamientos', icon: Activity },
    { key: 'estadisticas', label: 'Estadísticas', icon: TrendingUp },
    { key: 'recomendaciones', label: 'Recomendaciones', icon: Sparkles },
];

function Salud({
    events = {},
    alerts = {},
    animals = [],
    vacunas = [],
    pending = [],
    done = [],
    treatments = [],
    eventos = [],
    year,
    month,
}) {
    const { flash } = usePage().props || {};
    const [tab, setTab] = useState('vacunas');
    const [citaModalOpen, setCitaModalOpen] = useState(false);

    const initY = year ?? new Date().getFullYear();
    const initM = month ? month - 1 : new Date().getMonth();
    const [y, setY] = useState(initY);
    const [m, setM] = useState(initM);

    const weeks = useMemo(() => {
        const firstDay = new Date(y, m, 1).getDay();
        const daysInMonth = new Date(y, m + 1, 0).getDate();

        const cells = Array(firstDay).fill(null).concat(
            Array.from({ length: daysInMonth }, (_, i) => i + 1)
        );

        const out = [];
        for (let i = 0; i < cells.length; i += 7) out.push(cells.slice(i, i + 7));
        return out;
    }, [y, m]);

    function changeMonth(delta) {
        const d = new Date(y, m + delta, 1);
        setY(d.getFullYear());
        setM(d.getMonth());
        router.get('/salud', { month: d.toISOString().slice(0, 7) }, { preserveScroll: true });
    }

    function markDone(id) {
        router.patch(route('eventos-salud.aplicar', id), {}, { preserveScroll: true });
    }

    const today = new Date();
    const isToday = (cell) =>
        cell === today.getDate() && m === today.getMonth() && y === today.getFullYear();

    const eventosVencidos = eventos.filter((e) => e.estado === 'vencida').length;

    const summaryCards = [
        {
            title: 'Vacunas pendientes',
            value: pending.length,
            subtitle: 'Aplicaciones programadas',
            icon: Syringe,
            border: 'border-blue-500',
            iconColor: 'text-blue-600',
        },
        {
            title: 'Tratamientos activos',
            value: treatments.length,
            subtitle: 'Seguimientos en curso',
            icon: Activity,
            border: 'border-cyan-500',
            iconColor: 'text-cyan-600',
        },
        {
            title: 'Consultas vencidas',
            value: eventosVencidos,
            subtitle: 'Requieren atención',
            icon: Stethoscope,
            border: 'border-red-500',
            iconColor: 'text-red-500',
        },
        {
            title: 'Vacunas aplicadas',
            value: done.length,
            subtitle: 'Historial completado',
            icon: ShieldCheck,
            border: 'border-emerald-500',
            iconColor: 'text-emerald-600',
        },
    ];

    const dayStatusClasses = {
        aplicada: 'bg-emerald-100 text-emerald-700 border border-emerald-200',
        pendiente: 'bg-amber-100 text-amber-700 border border-amber-200',
        vencida: 'bg-red-100 text-red-700 border border-red-200',
    };

    return (
        <>
            <Head title="Módulo de Salud" />

            <div className="py-8 px-6 max-w-7xl mx-auto space-y-6">
                {/* ENCABEZADO */}
                <div>
                    <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-800">Módulo de Salud</h1>
                            <p className="text-gray-600">
                                Gestiona vacunas, tratamientos y el bienestar de tus animales.
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-3">
                            <button
                                type="button"
                                onClick={() => setCitaModalOpen(true)}
                                className="border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
                            >
                                <BadgePlus className="w-5 h-5 text-blue-600" />
                                Nueva cita
                            </button>

                            <button
                                type="button"
                                onClick={() => setTab('vacunas')}
                                className="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
                            >
                                <Syringe className="w-5 h-5" />
                                Ver vacunas
                            </button>
                        </div>
                    </div>
                </div>

                {/* ALERTAS */}
                {alerts.overdue > 0 && (
                    <div className="rounded-xl border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">
                        ⚠ Tienes {alerts.overdue} vacuna(s) atrasada(s) que requieren atención inmediata.
                    </div>
                )}

                {alerts.due_soon > 0 && !alerts.overdue && (
                    <div className="rounded-xl border border-amber-200 bg-amber-50 text-amber-700 px-4 py-3 text-sm">
                        🕐 Tienes {alerts.due_soon} vacuna(s) próximas en los siguientes 7 días.
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

                {/* CARDS SUPERIORES */}
                <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    {summaryCards.map((card) => {
                        const Icon = card.icon;

                        return (
                            <div
                                key={card.title}
                                className={`bg-white rounded-2xl shadow p-5 border-l-4 ${card.border}`}
                            >
                                <div className="flex justify-between items-center mb-4">
                                    <span className="text-gray-700 font-medium text-sm">
                                        {card.title}
                                    </span>
                                    <Icon className={`w-6 h-6 ${card.iconColor}`} />
                                </div>

                                <div className="text-2xl font-bold text-gray-800 mb-1">
                                    {card.value}
                                </div>

                                <p className="text-sm text-gray-500">{card.subtitle}</p>
                            </div>
                        );
                    })}
                </div>

                {/* CONTENIDO PRINCIPAL */}
                <div className="grid grid-cols-1 xl:grid-cols-12 gap-6">
                    {/* CALENDARIO */}
                    <section className="xl:col-span-4 bg-white rounded-2xl shadow border border-gray-100 overflow-hidden">
                        <div className="p-5 border-b border-gray-100">
                            <div className="flex items-center gap-2 mb-1">
                                <CalendarDays className="w-5 h-5 text-blue-600" />
                                <h2 className="text-lg font-semibold text-gray-800">
                                    Calendario de Salud
                                </h2>
                            </div>
                            <p className="text-sm text-gray-500">
                                Programación de vacunas y eventos
                            </p>
                        </div>

                        <div className="p-5 space-y-5">
                            <div className="flex items-center justify-between">
                                <button
                                    type="button"
                                    onClick={() => changeMonth(-1)}
                                    className="w-10 h-10 rounded-lg border border-gray-200 flex items-center justify-center hover:bg-gray-50 transition"
                                >
                                    <ChevronLeft className="w-5 h-5 text-gray-600" />
                                </button>

                                <div className="text-sm font-semibold text-gray-800">
                                    {MONTH_NAMES[m]} {y}
                                </div>

                                <button
                                    type="button"
                                    onClick={() => changeMonth(1)}
                                    className="w-10 h-10 rounded-lg border border-gray-200 flex items-center justify-center hover:bg-gray-50 transition"
                                >
                                    <ChevronRight className="w-5 h-5 text-gray-600" />
                                </button>
                            </div>

                            <div className="grid grid-cols-7 gap-2">
                                {['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'].map((d) => (
                                    <div
                                        key={d}
                                        className="text-center text-xs font-semibold text-gray-400 py-2"
                                    >
                                        {d}
                                    </div>
                                ))}

                                {weeks.flat().map((cell, idx) => {
                                    if (cell === null) {
                                        return (
                                            <div
                                                key={idx}
                                                className="h-12 rounded-xl bg-gray-50"
                                            />
                                        );
                                    }

                                    const key = `${y}-${String(m + 1).padStart(2, '0')}-${String(cell).padStart(2, '0')}`;
                                    const status = events[key];

                                    return (
                                        <div
                                            key={idx}
                                            className={[
                                                'h-12 rounded-xl flex items-center justify-center text-sm font-medium transition',
                                                isToday(cell)
                                                    ? 'ring-2 ring-blue-500'
                                                    : 'border border-gray-100',
                                                status
                                                    ? dayStatusClasses[status] || 'bg-white text-gray-700'
                                                    : 'bg-white text-gray-700',
                                            ].join(' ')}
                                        >
                                            {cell}
                                        </div>
                                    );
                                })}
                            </div>

                            <div className="flex flex-wrap gap-3 text-xs text-gray-500">
                                <span className="inline-flex items-center gap-2">
                                    <span className="w-3 h-3 rounded-full bg-emerald-500" />
                                    Aplicada
                                </span>
                                <span className="inline-flex items-center gap-2">
                                    <span className="w-3 h-3 rounded-full bg-amber-500" />
                                    Pendiente
                                </span>
                                <span className="inline-flex items-center gap-2">
                                    <span className="w-3 h-3 rounded-full bg-red-500" />
                                    Vencida
                                </span>
                            </div>

                            <button
                                type="button"
                                onClick={() => setCitaModalOpen(true)}
                                className="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-3 rounded-xl transition"
                            >
                                + Nueva cita
                            </button>
                        </div>
                    </section>

                    {/* TABS Y CONTENIDO */}
                    <section className="xl:col-span-8 bg-white rounded-2xl shadow border border-gray-100 overflow-hidden">
                        <div className="flex gap-6 border-b px-5 pt-5 pb-3 text-gray-600 overflow-x-auto">
                            {TABS.map((t) => {
                                const Icon = t.icon;

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
                                        <Icon size={18} />
                                        {t.label}

                                        {t.key === 'eventos' && eventosVencidos > 0 && (
                                            <span className="absolute -top-1 -right-3 bg-red-500 text-white rounded-full text-[10px] font-bold min-w-[18px] h-[18px] px-1 flex items-center justify-center">
                                                {eventosVencidos}
                                            </span>
                                        )}
                                    </button>
                                );
                            })}
                        </div>

                        <div className="p-5">
                            {tab === 'vacunas' && (
                                <TabVacunaciones
                                vacunas={vacunas}
                                pending={pending}
                                done={done}
                                onMarkDone={markDone}
                            />
                        )}

                            {tab === 'eventos' && <TabEventos eventos={eventos} />}

                            {tab === 'tratamientos' && (
                                <TabTratamientos
                                    treatments={treatments}
                                    animals={animals}
                                />
                            )}

                            {tab === 'estadisticas' && <TabEstadisticas />}

                            {tab === 'recomendaciones' && (
                                <TabRecomendaciones vacunas={vacunas} />
                            )}
                        </div>
                    </section>
                </div>
            </div>

            <ModalNuevaCita
                isOpen={citaModalOpen}
                onClose={() => setCitaModalOpen(false)}
                animals={animals}
                vacunas={vacunas}
            />
        </>
    );
}

Salud.layout = (page) => <AppLayout>{page}</AppLayout>;

export default Salud;