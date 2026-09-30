import { useState } from 'react';
import ModalNuevaVacuna from './ModalNuevaVacuna';

const estadoBadge = {
    pendiente: { label: 'Próximo',  cls: 'badge yellow' },
    vencida:   { label: 'Atrasado', cls: 'badge red'    },
    aplicada:  { label: 'Aplicado', cls: 'badge green'  },
};

const estadoIcon = {
    pendiente: '🕐',
    vencida:   '⚠️',
    aplicada:  '✓',
};

export default function TabVacunaciones({ vacunas = [], pending = [], done = [], onMarkDone }) {    const [catalogoOpen, setCatalogoOpen] = useState(false);
    console.log('VACUNAS:', vacunas);

    function handleMarkDone(id) {
        if (!confirm('¿Marcar esta vacunación como aplicada hoy?')) return;
        onMarkDone(id);
    }

    const total = pending.length + done.length;
    const pct   = total > 0 ? Math.round((done.length / total) * 100) : 0;

    return (
        <>
            <div className="card-header">
                <div>
                    <h2>Vacunaciones Programadas</h2>
                    <small>Estado de vacunas por animal</small>
                </div>
                {/* Botón para agregar vacuna al catálogo */}
                <button
                    type="button"
                    className="btn"
                    style={{ fontSize: '0.8rem', whiteSpace: 'nowrap' }}
                    onClick={() => setCatalogoOpen(true)}
                >
                    + Vacuna al catálogo
                </button>
            </div>

            {/* Barra de progreso del mes */}
            {total > 0 && (
                <div style={{ padding: '0 0 1rem' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.75rem', color: '#6b7280', marginBottom: '0.35rem' }}>
                        <span>{done.length} de {total} aplicadas este mes</span>
                        <span>{pct}%</span>
                    </div>
                    <div style={{ height: '6px', backgroundColor: '#e5e7eb', borderRadius: '99px', overflow: 'hidden' }}>
                        <div style={{ height: '100%', width: `${pct}%`, backgroundColor: '#22c55e', borderRadius: '99px', transition: 'width 0.4s ease' }} />
                    </div>
                </div>
            )}
            {/* Catálogo de vacunas registradas */}
<div style={{ marginBottom: '1.5rem' }}>
    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '0.75rem' }}>
        <div>
            <h3 style={{ fontSize: '1rem', fontWeight: '600', color: '#1f2937' }}>
                Catálogo de vacunas
            </h3>
            <small style={{ color: '#6b7280' }}>
                Vacunas registradas en el sistema
            </small>
        </div>
    </div>

    {vacunas.length === 0 ? (
        <div
            className="small-text"
            style={{
                textAlign: 'center',
                padding: '1.5rem',
                color: '#9ca3af',
                border: '1px dashed #d1d5db',
                borderRadius: '0.75rem',
                backgroundColor: '#f9fafb',
            }}
        >
            No hay vacunas registradas en el catálogo.
        </div>
    ) : (
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: '0.75rem' }}>
            {vacunas.map((vacuna) => (
                <div
                    key={vacuna.id}
                    style={{
                        border: '1px solid #e5e7eb',
                        borderLeft: '4px solid #2563eb',
                        borderRadius: '0.75rem',
                        padding: '1rem',
                        backgroundColor: '#ffffff',
                    }}
                >
                    <div style={{ fontWeight: '600', color: '#1f2937', marginBottom: '0.35rem' }}>
                        {vacuna.nombre}
                    </div>

                    <div style={{ fontSize: '0.8rem', color: '#6b7280' }}>
                        Patógeno: {vacuna.patogeno || 'No especificado'}
                    </div>

                    <div style={{ fontSize: '0.8rem', color: '#6b7280' }}>
                        Pauta: {vacuna.pauta || 'No especificada'}
                    </div>

                    <div style={{ fontSize: '0.8rem', color: '#6b7280' }}>
                        Refuerzo: {vacuna.refuerzo_dias ? `${vacuna.refuerzo_dias} días` : 'No especificado'}
                    </div>

                    <div style={{ fontSize: '0.8rem', color: '#6b7280' }}>
                        Especie: {vacuna.especie_objetivo || 'No especificada'}
                    </div>
                </div>
            ))}
        </div>
    )}
</div>
            <div className="list">
                {total === 0 && (
                    <div className="small-text" style={{ textAlign: 'center', padding: '2rem 0', color: '#9ca3af' }}>
                        No hay vacunaciones registradas este mes.
                    </div>
                )}

                {pending.map(c => {
                    const cfg = estadoBadge[c.estado] ?? estadoBadge.pendiente;
                    return (
                        <div key={c.id} className="list-item">
                            <div style={{ display: 'flex', gap: '0.75rem', alignItems: 'flex-start' }}>
                                <span style={{ fontSize: '1.1rem', marginTop: '2px' }}>{estadoIcon[c.estado]}</span>
                                <div>
                                    <div className="li-title">{c.vacuna}</div>
                                    <div className="li-sub">{c.animal}</div>
                                    <div className="li-sub">Fecha: {c.fecha}</div>
                                </div>
                            </div>
                            <div className="li-right">
                                <span className={cfg.cls}>{cfg.label}</span>
                                <button type="button" className="btn" onClick={() => handleMarkDone(c.id)}>
                                    Marcar aplicada
                                </button>
                            </div>
                        </div>
                    );
                })}

                {done.map(c => (
                    <div key={c.id} className="list-item" style={{ opacity: 0.75 }}>
                        <div style={{ display: 'flex', gap: '0.75rem', alignItems: 'flex-start' }}>
                            <span style={{ fontSize: '1.1rem', marginTop: '2px' }}>✓</span>
                            <div>
                                <div className="li-title">{c.vacuna}</div>
                                <div className="li-sub">{c.animal}</div>
                                <div className="li-sub">Aplicada: {c.fecha}</div>
                            </div>
                        </div>
                        <div className="li-right">
                            <span className="badge green">Completado</span>
                        </div>
                    </div>
                ))}
            </div>

            <ModalNuevaVacuna isOpen={catalogoOpen} onClose={() => setCatalogoOpen(false)} />
        </>
    );
}