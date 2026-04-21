import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const TIPO_CFG = {
    consulta:   { emoji: '🩺', label: 'Consulta',   color: '#3b82f6' },
    revision:   { emoji: '🔍', label: 'Revisión',   color: '#8b5cf6' },
    emergencia: { emoji: '🚨', label: 'Emergencia', color: '#ef4444' },
};

const ESTADO_CFG = {
    pendiente: { label: 'Pendiente', cls: 'badge yellow' },
    vencida:   { label: 'Atrasada',  cls: 'badge red'    },
};

/**
 * Modal interno para completar un evento (consulta/revisión/emergencia).
 * Registra diagnóstico, observaciones y opcionalmente un tratamiento vinculado.
 */
function ModalCompletarEvento({ evento, onClose }) {
    const esEmergencia = evento?.tipo === 'emergencia';

    const { data, setData, post, processing, errors, reset } = useForm({
        diagnostico:          evento?.diagnostico ?? '',
        observaciones:        evento?.observaciones ?? '',
        // Si es emergencia, puede generar tratamiento directo
        crear_tratamiento:    false,
        tratamiento_nombre:   '',
        tratamiento_notas:    '',
        tratamiento_fecha_fin:'',
    });

    function submit(e) {
        e.preventDefault();
        post(route('eventos-salud.completar', evento.id), {
            preserveScroll: true,
            onSuccess: () => { reset(); onClose(); },
        });
    }

    if (!evento) return null;

    const cfg = TIPO_CFG[evento.tipo] ?? TIPO_CFG.consulta;

    const css = {
        backdrop: {
            position: 'fixed', inset: 0, zIndex: 1100,
            backgroundColor: 'rgba(0,0,0,0.55)',
            display: 'flex', alignItems: 'center', justifyContent: 'center',
            padding: '1rem',
        },
        panel: {
            zIndex: 1101, backgroundColor: '#fff',
            borderRadius: '12px',
            boxShadow: '0 24px 64px rgba(0,0,0,0.28)',
            width: '100%', maxWidth: '480px',
            maxHeight: '90vh', overflowY: 'auto',
        },
        header: {
            display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start',
            padding: '1.25rem 1.5rem 1rem',
            borderBottom: '1px solid #e5e7eb',
            position: 'sticky', top: 0, backgroundColor: '#fff',
            borderRadius: '12px 12px 0 0',
        },
        body: { padding: '1.25rem 1.5rem 1.5rem', display: 'flex', flexDirection: 'column', gap: '1rem' },
        label: { display: 'flex', flexDirection: 'column', gap: '0.35rem', fontSize: '0.85rem', fontWeight: 600, color: '#374151' },
        input: { padding: '0.5rem 0.75rem', border: '1px solid #d1d5db', borderRadius: '6px', fontSize: '0.875rem', width: '100%', boxSizing: 'border-box' },
        textarea: { padding: '0.5rem 0.75rem', border: '1px solid #d1d5db', borderRadius: '6px', fontSize: '0.875rem', width: '100%', boxSizing: 'border-box', resize: 'vertical', minHeight: '75px', fontFamily: 'inherit' },
        error: { color: '#ef4444', fontSize: '0.75rem' },
        hint: { fontWeight: 400, color: '#9ca3af', fontSize: '0.78rem' },
        divider: { border: 'none', borderTop: '1px solid #f3f4f6', margin: '0.25rem 0' },
        sectionTitle: { fontSize: '0.72rem', fontWeight: 700, letterSpacing: '0.07em', color: '#9ca3af', textTransform: 'uppercase' },
    };

    return (
        <div style={css.backdrop} onClick={onClose}>
            <div style={css.panel} onClick={e => e.stopPropagation()}>
                <div style={css.header}>
                    <div>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                            <span style={{ fontSize: '1.3rem' }}>{cfg.emoji}</span>
                            <h2 style={{ margin: 0, fontSize: '1.05rem', fontWeight: 700, color: '#111827' }}>
                                Registrar resultado
                            </h2>
                        </div>
                        <p style={{ margin: '0.2rem 0 0', fontSize: '0.8rem', color: '#6b7280' }}>
                            {cfg.label} · {evento.animal} · {evento.fecha}
                        </p>
                    </div>
                    <button type="button" onClick={onClose}
                        style={{ background: 'none', border: 'none', fontSize: '1.1rem', cursor: 'pointer', color: '#9ca3af' }}>
                        ✕
                    </button>
                </div>

                <form onSubmit={submit} style={css.body}>

                    {/* Diagnóstico */}
                    <label style={css.label}>
                        Diagnóstico / resultado
                        <textarea
                            value={data.diagnostico}
                            onChange={e => setData('diagnostico', e.target.value)}
                            placeholder={
                                evento.tipo === 'revision'
                                    ? 'Ej: Condición corporal 3/5, gestación confirmada 3 meses…'
                                    : 'Ej: Neumonía bacteriana leve, sin complicaciones…'
                            }
                            style={css.textarea}
                        />
                        {errors.diagnostico && <span style={css.error}>{errors.diagnostico}</span>}
                    </label>

                    {/* Observaciones */}
                    <label style={css.label}>
                        Observaciones <span style={css.hint}>(opcional)</span>
                        <textarea
                            value={data.observaciones}
                            onChange={e => setData('observaciones', e.target.value)}
                            placeholder="Notas adicionales, indicaciones de seguimiento…"
                            style={{ ...css.textarea, minHeight: '60px' }}
                        />
                        {errors.observaciones && <span style={css.error}>{errors.observaciones}</span>}
                    </label>

                    <hr style={css.divider} />

                    {/* Opción de crear tratamiento vinculado */}
                    <div>
                        <label style={{ display: 'flex', alignItems: 'center', gap: '0.5rem', cursor: 'pointer', fontSize: '0.875rem', fontWeight: 600, color: '#374151' }}>
                            <input
                                type="checkbox"
                                checked={data.crear_tratamiento}
                                onChange={e => setData('crear_tratamiento', e.target.checked)}
                                style={{ width: '16px', height: '16px' }}
                            />
                            Crear tratamiento vinculado a este evento
                        </label>
                        <p style={{ margin: '0.25rem 0 0 1.5rem', fontSize: '0.78rem', color: '#6b7280' }}>
                            El tratamiento quedará registrado bajo este {cfg.label.toLowerCase()} y visible en el tab de Tratamientos.
                        </p>
                    </div>

                    {data.crear_tratamiento && (
                        <div style={{ backgroundColor: '#f9fafb', borderRadius: '8px', padding: '1rem', display: 'flex', flexDirection: 'column', gap: '0.75rem', border: '1px solid #e5e7eb' }}>
                            <span style={css.sectionTitle}>Datos del tratamiento</span>

                            <label style={css.label}>
                                Nombre del tratamiento *
                                <input type="text"
                                    value={data.tratamiento_nombre}
                                    onChange={e => setData('tratamiento_nombre', e.target.value)}
                                    placeholder="Ej: Penicilina G, suero oral, antibiótico…"
                                    style={css.input}
                                    required={data.crear_tratamiento}
                                />
                                {errors.tratamiento_nombre && <span style={css.error}>{errors.tratamiento_nombre}</span>}
                            </label>

                            <label style={css.label}>
                                Fecha fin <span style={css.hint}>(opcional, si tiene duración definida)</span>
                                <input type="date"
                                    value={data.tratamiento_fecha_fin}
                                    onChange={e => setData('tratamiento_fecha_fin', e.target.value)}
                                    style={css.input}
                                />
                            </label>

                            <label style={css.label}>
                                Notas del tratamiento <span style={css.hint}>(dosis, vía, frecuencia…)</span>
                                <textarea
                                    value={data.tratamiento_notas}
                                    onChange={e => setData('tratamiento_notas', e.target.value)}
                                    placeholder="Ej: 10 ml IM cada 24h por 5 días"
                                    style={{ ...css.textarea, minHeight: '55px' }}
                                />
                            </label>
                        </div>
                    )}

                    <div style={{ display: 'flex', gap: '0.75rem', paddingTop: '0.25rem' }}>
                        <button type="button" className="btn secondary" style={{ flex: 1 }} onClick={onClose} disabled={processing}>
                            Cancelar
                        </button>
                        <button type="submit" className="btn primary" style={{ flex: 2, opacity: processing ? 0.7 : 1 }} disabled={processing}>
                            {processing ? 'Guardando…' : 'Registrar y cerrar evento'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

// ─────────────────────────────────────────────────────────────────────────────

/**
 * Tab de consultas, revisiones y emergencias pendientes.
 *
 * Props:
 *  - eventos: EventoSalud[] (tipo: consulta | revision | emergencia, estado: pendiente | vencida)
 */
export default function TabEventos({ eventos = [] }) {
    const [eventoActivo, setEventoActivo] = useState(null);

    const vencidos  = eventos.filter(e => e.estado === 'vencida');
    const pendientes = eventos.filter(e => e.estado === 'pendiente');

    return (
        <>
            <div className="card-header">
                <h2>Consultas y Revisiones</h2>
                <small>Eventos programados que requieren seguimiento</small>
            </div>

            <div className="list">
                {eventos.length === 0 && (
                    <div className="small-text" style={{ textAlign: 'center', padding: '2rem 0', color: '#9ca3af' }}>
                        No hay consultas ni revisiones pendientes.
                    </div>
                )}

                {vencidos.length > 0 && (
                    <div style={{ fontSize: '0.75rem', fontWeight: 600, color: '#ef4444', padding: '0.25rem 0', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                        ⚠ Atrasados — sin registrar resultado
                    </div>
                )}

                {[...vencidos, ...pendientes].map(e => {
                    const cfg  = TIPO_CFG[e.tipo]   ?? TIPO_CFG.consulta;
                    const ecfg = ESTADO_CFG[e.estado] ?? ESTADO_CFG.pendiente;
                    return (
                        <div key={e.id} className="list-item" style={{
                            borderLeft: `3px solid ${e.estado === 'vencida' ? '#ef4444' : cfg.color}`,
                            paddingLeft: '0.75rem',
                        }}>
                            <div style={{ display: 'flex', gap: '0.75rem', alignItems: 'flex-start' }}>
                                <span style={{ fontSize: '1.1rem', marginTop: '2px' }}>{cfg.emoji}</span>
                                <div>
                                    <div className="li-title">
                                        {cfg.label}
                                        {e.diagnostico && (
                                            <span style={{ fontWeight: 400, color: '#6b7280' }}> · {e.diagnostico}</span>
                                        )}
                                    </div>
                                    <div className="li-sub">{e.animal}</div>
                                    <div className="li-sub">Programado: {e.fecha}</div>
                                </div>
                            </div>
                            <div className="li-right">
                                <span className={ecfg.cls}>{ecfg.label}</span>
                                <button type="button" className="btn primary" style={{ fontSize: '0.8rem' }}
                                    onClick={() => setEventoActivo(e)}>
                                    Registrar resultado
                                </button>
                            </div>
                        </div>
                    );
                })}
            </div>

            <ModalCompletarEvento
                evento={eventoActivo}
                onClose={() => setEventoActivo(null)}
            />
        </>
    );
}