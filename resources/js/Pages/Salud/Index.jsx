import { Head, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import ModalNuevaCita from './ModalNuevaCita';
import TabVacunaciones from './TabVacunaciones';
import TabTratamientos from './TabTratamientos';
import TabEventos from './TabEventos';
import TabEstadisticas from './TabEstadisticas';
import TabRecomendaciones from './TabRecomendaciones';

const MONTH_NAMES = [
    'January','February','March','April','May','June',
    'July','August','September','October','November','December',
];

const TABS = [
    { key: 'vacunas',         label: 'Vacunaciones',    icon: '💉' },
    { key: 'eventos',         label: 'Consultas',        icon: '🩺' },
    { key: 'tratamientos',    label: 'Tratamientos',    icon: '⌁'  },
    { key: 'estadisticas',    label: 'Estadísticas',    icon: '↗'  },
    { key: 'recomendaciones', label: 'Recomendaciones', icon: '⚡' },
];

function Salud({ events = {}, alerts = {}, animals = [], vacunas = [],
                  pending = [], done = [], treatments = [], eventos = [],
                  year, month }) {

    const { flash } = usePage().props || {};
    const [tab, setTab]             = useState('vacunas');
    const [citaModalOpen, setCitaModalOpen] = useState(false);

    const initY = year  ?? new Date().getFullYear();
    const initM = month ? month - 1 : new Date().getMonth();
    const [y, setY] = useState(initY);
    const [m, setM] = useState(initM);

    const weeks = useMemo(() => {
        const firstDay    = new Date(y, m, 1).getDay();
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

    const today   = new Date();
    const isToday = (cell) =>
        cell === today.getDate() && m === today.getMonth() && y === today.getFullYear();

    // Badge de conteo para el tab de consultas
    const eventosVencidos = eventos.filter(e => e.estado === 'vencida').length;

    return (
        <>
            <Head title="Módulo de Salud" />

            <div className="health-page">
                <div className="container-centered">

                    <div className="health-header">
                        <div>
                            <h1>Módulo de Salud</h1>
                            <p>Gestiona vacunas, tratamientos y el bienestar de tus animales</p>
                        </div>
                    </div>

                    {alerts.overdue > 0 && (
                        <div className="alert danger">
                            ⚠ Tienes {alerts.overdue} vacuna(s) atrasada(s) que requieren atención inmediata.
                        </div>
                    )}
                    {alerts.due_soon > 0 && !alerts.overdue && (
                        <div className="alert warning">
                            🕐 Tienes {alerts.due_soon} vacuna(s) próximas en los siguientes 7 días.
                        </div>
                    )}
                    {flash?.success && <div className="alert success">✓ {flash.success}</div>}
                    {flash?.error   && <div className="alert danger">✕ {flash.error}</div>}

                    <div className="health-grid">

                        {/* ═══ COLUMNA IZQUIERDA: Calendario ═══ */}
                        <section className="card">
                            <div className="card-header">
                                <h2>📅 Calendario de Salud</h2>
                                <small>Programación de vacunas y eventos</small>
                            </div>

                            <div className="calendar-nav">
                                <button type="button" onClick={() => changeMonth(-1)}>{'<'}</button>
                                <div>{MONTH_NAMES[m]} {y}</div>
                                <button type="button" onClick={() => changeMonth(1)}>{'>'}</button>
                            </div>

                            <div className="calendar-grid">
                                {['Su','Mo','Tu','We','Th','Fr','Sa'].map(d => (
                                    <div key={d} className="dow">{d}</div>
                                ))}
                                {weeks.flat().map((cell, idx) => {
                                    if (cell === null) return <div key={idx} className="day empty" />;
                                    const key    = `${y}-${String(m+1).padStart(2,'0')}-${String(cell).padStart(2,'0')}`;
                                    const status = events[key];
                                    return (
                                        <div key={idx} className={[
                                            'day',
                                            status ? `status-${status}` : '',
                                            isToday(cell) ? 'today' : '',
                                        ].filter(Boolean).join(' ')}>
                                            {cell}
                                        </div>
                                    );
                                })}
                            </div>

                            <div style={{ display: 'flex', gap: '1rem', padding: '0.5rem 0', fontSize: '0.75rem', color: '#6b7280' }}>
                                <span>🟢 Aplicada</span>
                                <span>🟡 Pendiente</span>
                                <span>🔴 Vencida</span>
                            </div>

                            <div className="divider" />

                            <button
                                type="button"
                                className="btn primary"
                                style={{ width: '100%' }}
                                onClick={() => setCitaModalOpen(true)}
                            >
                                + Nueva cita
                            </button>
                        </section>

                        {/* ═══ COLUMNA DERECHA: Tabs ═══ */}
                        <section className="card">
                            <div className="tabs">
                                {TABS.map(t => (
                                    <button key={t.key} type="button"
                                        className={tab === t.key ? 'active' : ''}
                                        onClick={() => setTab(t.key)}
                                        style={{ position: 'relative' }}
                                    >
                                        {t.icon} {t.label}
                                        {/* Badge de alerta en tab Consultas */}
                                        {t.key === 'eventos' && eventosVencidos > 0 && (
                                            <span style={{
                                                position: 'absolute', top: '2px', right: '2px',
                                                backgroundColor: '#ef4444', color: '#fff',
                                                borderRadius: '99px', fontSize: '0.65rem',
                                                fontWeight: 700, padding: '0 5px', lineHeight: '16px',
                                                minWidth: '16px', textAlign: 'center',
                                            }}>
                                                {eventosVencidos}
                                            </span>
                                        )}
                                    </button>
                                ))}
                            </div>

                            {tab === 'vacunas'         && <TabVacunaciones pending={pending} done={done} onMarkDone={markDone} />}
                            {tab === 'eventos'         && <TabEventos eventos={eventos} />}
                            {tab === 'tratamientos'    && <TabTratamientos treatments={treatments} animals={animals} />}
                            {tab === 'estadisticas'    && <TabEstadisticas />}
                            {tab === 'recomendaciones' && <TabRecomendaciones vacunas={vacunas} />}
                        </section>
                    </div>
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

Salud.layout = page => <AppLayout>{page}</AppLayout>;
export default Salud;