import { useForm } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

/**
 * Modal para agregar una vacuna al catálogo del sistema.
 * Ruta: POST /vacunas  (vacunas.store)
 *
 * Props:
 *  - isOpen  : boolean
 *  - onClose : () => void
 */
export default function ModalNuevaVacuna({ isOpen, onClose }) {
    const firstRef = useRef(null);

    const { data, setData, post, processing, reset, errors } = useForm({
        nombre:           '',
        patogeno:         '',
        pauta:            '',
        refuerzo_dias:    '',
        especie_objetivo: '',
    });

    useEffect(() => {
        if (isOpen) setTimeout(() => firstRef.current?.focus(), 80);
    }, [isOpen]);

    useEffect(() => {
        if (!isOpen) return;
        const onKey = (e) => { if (e.key === 'Escape') handleClose(); };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [isOpen]);

    function handleClose() { reset(); onClose(); }

    function submit(e) {
        e.preventDefault();
        post(route('vacunas.store'), {
            preserveScroll: true,
            onSuccess: () => { reset(); onClose(); },
        });
    }

    if (!isOpen) return null;

    // ── Estilos compartidos ──────────────────────────────────────
    const css = {
        backdrop: {
            position: 'fixed', inset: 0, zIndex: 1200,
            backgroundColor: 'rgba(0,0,0,0.55)',
            display: 'flex', alignItems: 'center', justifyContent: 'center',
            padding: '1rem',
        },
        panel: {
            zIndex: 1201, backgroundColor: '#fff',
            borderRadius: '14px',
            boxShadow: '0 28px 72px rgba(0,0,0,0.28)',
            width: '100%', maxWidth: '460px',
            maxHeight: '90vh', overflowY: 'auto',
        },
        header: {
            display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start',
            padding: '1.25rem 1.5rem 1rem',
            borderBottom: '1px solid #e5e7eb',
            position: 'sticky', top: 0, backgroundColor: '#fff',
            borderRadius: '14px 14px 0 0',
        },
        body: {
            padding: '1.25rem 1.5rem 1.5rem',
            display: 'flex', flexDirection: 'column', gap: '1rem',
        },
        label: {
            display: 'flex', flexDirection: 'column', gap: '0.35rem',
            fontSize: '0.85rem', fontWeight: 600, color: '#374151',
        },
        hint: { fontWeight: 400, color: '#9ca3af', fontSize: '0.78rem' },
        input: {
            padding: '0.55rem 0.75rem',
            border: '1px solid #d1d5db', borderRadius: '7px',
            fontSize: '0.875rem', width: '100%', boxSizing: 'border-box',
            outline: 'none', color: '#111827',
        },
        select: {
            padding: '0.55rem 0.75rem',
            border: '1px solid #d1d5db', borderRadius: '7px',
            fontSize: '0.875rem', width: '100%',
            backgroundColor: '#fff', color: '#111827',
        },
        row2: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.75rem' },
        error: { color: '#ef4444', fontSize: '0.75rem' },
        sectionTitle: {
            fontSize: '0.72rem', fontWeight: 700, letterSpacing: '0.07em',
            color: '#9ca3af', textTransform: 'uppercase',
        },
        divider: { border: 'none', borderTop: '1px solid #f3f4f6', margin: '0.1rem 0' },
        infoBox: {
            backgroundColor: '#eff6ff', border: '1px solid #bfdbfe',
            borderRadius: '8px', padding: '0.75rem 1rem',
            fontSize: '0.8rem', color: '#1e40af', lineHeight: 1.5,
        },
    };

    const ESPECIES = ['Bovino', 'Ovino', 'Caprino', 'Porcino', 'Aviar', 'Equino', 'General'];

    return (
        <div style={css.backdrop} onClick={handleClose}>
            <div style={css.panel} onClick={e => e.stopPropagation()}>

                {/* ── Cabecera ── */}
                <div style={css.header}>
                    <div>
                        <h2 style={{ margin: 0, fontSize: '1.1rem', fontWeight: 700, color: '#111827' }}>
                            💉 Nueva vacuna al catálogo
                        </h2>
                        <p style={{ margin: '0.2rem 0 0', fontSize: '0.8rem', color: '#6b7280' }}>
                            Disponible para todas las citas de vacunación
                        </p>
                    </div>
                    <button type="button" onClick={handleClose}
                        style={{ background: 'none', border: 'none', fontSize: '1.1rem', cursor: 'pointer', color: '#9ca3af' }}>
                        ✕
                    </button>
                </div>

                <form onSubmit={submit} style={css.body}>

                    {/* ── Identificación ── */}
                    <span style={css.sectionTitle}>Identificación</span>

                    <label style={css.label}>
                        Nombre comercial / nombre de la vacuna *
                        <input ref={firstRef} type="text"
                            value={data.nombre}
                            onChange={e => setData('nombre', e.target.value)}
                            placeholder="Ej: Bovimune Forte, Porcilis MHyo…"
                            required style={css.input} />
                        {errors.nombre && <span style={css.error}>{errors.nombre}</span>}
                    </label>

                    <label style={css.label}>
                        Patógeno que combate <span style={css.hint}>(opcional)</span>
                        <input type="text"
                            value={data.patogeno}
                            onChange={e => setData('patogeno', e.target.value)}
                            placeholder="Ej: Clostridium perfringens, Brucella abortus…"
                            style={css.input} />
                        {errors.patogeno && <span style={css.error}>{errors.patogeno}</span>}
                    </label>

                    <hr style={css.divider} />

                    {/* ── Aplicación ── */}
                    <span style={css.sectionTitle}>Aplicación</span>

                    <div style={css.row2}>
                        <label style={css.label}>
                            Especie objetivo <span style={css.hint}>(opcional)</span>
                            <select value={data.especie_objetivo}
                                onChange={e => setData('especie_objetivo', e.target.value)}
                                style={css.select}>
                                <option value="">— Todas —</option>
                                {ESPECIES.map(esp => (
                                    <option key={esp} value={esp}>{esp}</option>
                                ))}
                            </select>
                            {errors.especie_objetivo && <span style={css.error}>{errors.especie_objetivo}</span>}
                        </label>

                        <label style={css.label}>
                            Refuerzo cada <span style={css.hint}>(días)</span>
                            <input type="number" min="1" max="730"
                                value={data.refuerzo_dias}
                                onChange={e => setData('refuerzo_dias', e.target.value)}
                                placeholder="Ej: 180, 365"
                                style={css.input} />
                            {errors.refuerzo_dias && <span style={css.error}>{errors.refuerzo_dias}</span>}
                        </label>
                    </div>

                    <label style={css.label}>
                        Pauta de vacunación <span style={css.hint}>(opcional)</span>
                        <input type="text"
                            value={data.pauta}
                            onChange={e => setData('pauta', e.target.value)}
                            placeholder="Ej: 2 dosis con 3 semanas de intervalo, refuerzo anual"
                            style={css.input} />
                        {errors.pauta && <span style={css.error}>{errors.pauta}</span>}
                    </label>

                    {/* Nota informativa sobre el refuerzo automático */}
                    {data.refuerzo_dias && (
                        <div style={css.infoBox}>
                            ⚙️ Al aplicar esta vacuna, el sistema programará automáticamente
                            el siguiente refuerzo a los <strong>{data.refuerzo_dias} días</strong>.
                        </div>
                    )}

                    {/* ── Acciones ── */}
                    <div style={{ display: 'flex', gap: '0.75rem', paddingTop: '0.25rem' }}>
                        <button type="button" className="btn secondary" style={{ flex: 1 }}
                            onClick={handleClose} disabled={processing}>
                            Cancelar
                        </button>
                        <button type="submit" className="btn primary"
                            style={{ flex: 2, opacity: processing ? 0.7 : 1 }} disabled={processing}>
                            {processing ? 'Guardando…' : 'Guardar en catálogo'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}