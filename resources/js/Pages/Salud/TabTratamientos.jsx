import { router } from '@inertiajs/react';
import { useState } from 'react';
import ModalNuevoTratamiento from './ModalNuevoTratamiento';

export default function TabTratamientos({ treatments = [], animals = [] }) {
    const [modalOpen, setModalOpen] = useState(false);

    function completar(id) {
        if (!confirm('¿Marcar este tratamiento como completado?')) return;
        router.patch(route('tratamientos.completar', id), {}, { preserveScroll: true });
    }

    const vencidos = treatments.filter(t => t.esta_vencido);
    const activos  = treatments.filter(t => !t.esta_vencido);

    return (
        <>
            <div className="card-header">
                <div>
                    <h2>Tratamientos Activos</h2>
                    <small>Medicamentos y terapias en curso</small>
                </div>
                <button
                    type="button"
                    className="btn primary"
                    style={{ fontSize: '0.8rem', whiteSpace: 'nowrap' }}
                    onClick={() => setModalOpen(true)}
                >
                    + Nuevo tratamiento
                </button>
            </div>

            <div className="list">
                {treatments.length === 0 && (
                    <div className="small-text" style={{ textAlign: 'center', padding: '2rem 0', color: '#9ca3af' }}>
                        No hay tratamientos activos registrados.
                    </div>
                )}

                {vencidos.length > 0 && (
                    <div style={{ fontSize: '0.75rem', fontWeight: 600, color: '#ef4444', padding: '0.25rem 0', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                        ⚠ Vencidos — requieren atención
                    </div>
                )}

                {[...vencidos, ...activos].map(t => (
                    <div key={t.id} className="list-item" style={{
                        borderLeft: t.esta_vencido ? '3px solid #ef4444' : '3px solid transparent',
                        paddingLeft: '0.75rem',
                    }}>
                        <div>
                            <div className="li-title">{t.nombre}</div>
                            <div className="li-sub">{t.animal}</div>
                            <div className="li-sub">{t.rango}</div>
                            {t.notas && <div className="li-sub" style={{ fontStyle: 'italic' }}>{t.notas}</div>}
                        </div>
                        <div className="li-right">
                            {t.dias_restantes !== null && (
                                <span style={{
                                    fontSize: '0.7rem', fontWeight: 600,
                                    color: t.esta_vencido ? '#ef4444' : t.dias_restantes <= 3 ? '#f59e0b' : '#22c55e',
                                }}>
                                    {t.esta_vencido ? 'Vencido'
                                        : t.dias_restantes === 0 ? 'Último día'
                                        : `${t.dias_restantes}d restantes`}
                                </span>
                            )}
                            <span className={`badge ${t.esta_vencido ? 'red' : 'yellow'}`}>
                                {t.esta_vencido ? 'Vencido' : 'Activo'}
                            </span>
                            <button type="button" className="btn" onClick={() => completar(t.id)}>
                                Completar
                            </button>
                        </div>
                    </div>
                ))}
            </div>

            <ModalNuevoTratamiento
                isOpen={modalOpen}
                onClose={() => setModalOpen(false)}
                animals={animals}
            />
        </>
    );
}