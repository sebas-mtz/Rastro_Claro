import { useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import ModalAnimalSelect from './ModalAnimalSelect';
import ModalLoteSelect from './ModalLoteSelect';
import ModalNuevaVacuna from './ModalNuevaVacuna';
/**
 * Modal completo para registrar un EventoSalud.
 * Muestra campos dinámicos según el tipo seleccionado.
 *
 * Props:
 *  - isOpen   : boolean
 *  - onClose  : () => void
 *  - animals  : Animal[]
 *  - vacunas  : Vacuna[]  ← catálogo de vacunas del sistema
 */
export default function ModalNuevaCita({ isOpen, onClose, animals = [],   lotes = [],    vacunas = [] }) {
    const [animalModalOpen, setAnimalModalOpen] = useState(false);
    const [loteModalOpen, setLoteModalOpen] = useState(false);
    const panelRef = useRef(null);
    const [vacunaModalOpen, setVacunaModalOpen] = useState(false);

    const { data, setData, post, processing, reset, errors } = useForm({
        animal_id:        '',
        lote_id: '',
        tipo:             'vacunacion',
        fecha_programada: '',
        fecha_aplicacion: '',
        diagnostico:      '',
        tratamiento:      '',   // descripción de tratamiento (campo texto del modelo)
        vacuna_id:        '',   // FK al catálogo de Vacunas
        dosis:            '',
        lote_vacuna:      '',
        observaciones:    '',
        responsable:      '',
        estado:           'pendiente',
    });

    // Limpiar campos exclusivos del tipo anterior al cambiar
    function cambiarTipo(nuevoTipo) {
        setData(prev => ({
            ...prev,
            tipo:       nuevoTipo,
            vacuna_id:  '',
            dosis:      '',
            lote_vacuna:'',
            tratamiento:'',
            diagnostico:'',
        }));
    }

    // Cerrar con Escape
    useEffect(() => {
        if (!isOpen) return;
        const onKey = (e) => { if (e.key === 'Escape' && !animalModalOpen) handleClose(); };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [isOpen, animalModalOpen]);

    // Scroll al top del panel al abrir
    useEffect(() => {
        if (isOpen) panelRef.current?.scrollTo(0, 0);
    }, [isOpen, data.tipo]);

    function handleClose() {
        reset();
        onClose();
    }

    function submit(e) {
        e.preventDefault();
        post(route('eventos-salud.store'), {
            preserveScroll: true,
            onSuccess: () => { reset(); onClose(); },
        });
    }

    const selectedAnimal = animals.find(a => a.id === data.animal_id);
    const selectedLote = lotes.find(l => l.id === data.lote_id);

const loteLabel = selectedLote
    ? [
        selectedLote.nombre ?? '',
        selectedLote.corral_potrero ? `(${selectedLote.corral_potrero})` : '',
      ].filter(Boolean).join(' ')
    : '';
    const animalLabel = selectedAnimal
        ? [
            selectedAnimal.arete   ? `#${selectedAnimal.arete}` : '',
            selectedAnimal.alias   ?? '',
            selectedAnimal.especie ? `(${selectedAnimal.especie})` : '',
          ].filter(Boolean).join(' ')
        : '';

    if (!isOpen) return null;

    // ── Tipos disponibles ─────────────────────────────────────────
    const TIPOS = [
        { key: 'vacunacion', emoji: '💉', label: 'Vacunación',
          desc: 'Aplica una vacuna del catálogo y programa el refuerzo automático.' },
        { key: 'consulta',   emoji: '🩺', label: 'Consulta',
          desc: 'Visita veterinaria, revisión de diagnóstico o seguimiento.' },
        { key: 'revision',   emoji: '🔍', label: 'Revisión',
          desc: 'Chequeo rutinario: peso, gestación, condición corporal.' },
        { key: 'emergencia', emoji: '🚨', label: 'Emergencia',
          desc: 'Evento urgente. Puedes registrar el tratamiento aplicado.' },
    ];

    // ── Estilos ──────────────────────────────────────────────────
    const css = {
        backdrop: {
            position: 'fixed', inset: 0, zIndex: 1000,
            backgroundColor: 'rgba(0,0,0,0.55)',
            display: 'flex', alignItems: 'center', justifyContent: 'center',
            padding: '1rem',
        },
        panel: {
            position: 'relative', zIndex: 1001,
            backgroundColor: '#fff',
            borderRadius: '14px',
            boxShadow: '0 28px 72px rgba(0,0,0,0.28)',
            width: '100%', maxWidth: '520px',
            maxHeight: '92vh', overflowY: 'auto',
            display: 'flex', flexDirection: 'column',
        },
        header: {
            display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start',
            padding: '1.25rem 1.5rem 1rem',
            borderBottom: '1px solid #e5e7eb',
            position: 'sticky', top: 0, backgroundColor: '#fff',
            borderRadius: '14px 14px 0 0', zIndex: 1,
        },
        body: {
            padding: '1.25rem 1.5rem 1.5rem',
            display: 'flex', flexDirection: 'column', gap: '1.1rem',
        },
        fieldset: {
            border: 'none', padding: 0, margin: 0,
            display: 'flex', flexDirection: 'column', gap: '1.1rem',
        },
        label: {
            display: 'flex', flexDirection: 'column', gap: '0.35rem',
            fontSize: '0.85rem', fontWeight: 600, color: '#374151',
        },
        labelHint: { fontWeight: 400, color: '#9ca3af', fontSize: '0.78rem' },
        input: {
            padding: '0.55rem 0.75rem',
            border: '1px solid #d1d5db', borderRadius: '7px',
            fontSize: '0.875rem', width: '100%', boxSizing: 'border-box',
            outline: 'none', color: '#111827',
            transition: 'border-color 0.15s',
        },
        select: {
            padding: '0.55rem 0.75rem',
            border: '1px solid #d1d5db', borderRadius: '7px',
            fontSize: '0.875rem', width: '100%',
            backgroundColor: '#fff', color: '#111827',
        },
        textarea: {
            padding: '0.55rem 0.75rem',
            border: '1px solid #d1d5db', borderRadius: '7px',
            fontSize: '0.875rem', width: '100%', boxSizing: 'border-box',
            resize: 'vertical', minHeight: '70px',
            fontFamily: 'inherit', color: '#111827',
        },
        row2: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.75rem' },
        error: { color: '#ef4444', fontSize: '0.75rem', marginTop: '0.1rem' },
        divider: { border: 'none', borderTop: '1px solid #f3f4f6', margin: '0.25rem 0' },
        sectionTitle: {
            fontSize: '0.72rem', fontWeight: 700, letterSpacing: '0.07em',
            color: '#9ca3af', textTransform: 'uppercase', marginBottom: '-0.25rem',
        },
    };

    const tipoActual = TIPOS.find(t => t.key === data.tipo);

    return (
        <div style={css.backdrop} onClick={handleClose}>
            <div style={css.panel} ref={panelRef} onClick={e => e.stopPropagation()}>

                {/* ── Cabecera ─────────────────────────────────── */}
                <div style={css.header}>
                    <div>
                        <h2 style={{ margin: 0, fontSize: '1.1rem', fontWeight: 700, color: '#111827' }}>
                            Nueva cita
                        </h2>
                        <p style={{ margin: '0.2rem 0 0', fontSize: '0.8rem', color: '#6b7280' }}>
                            {tipoActual?.desc}
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={handleClose}
                        aria-label="Cerrar"
                        style={{ background: 'none', border: 'none', fontSize: '1.2rem',
                                 cursor: 'pointer', color: '#9ca3af', padding: '0.1rem',
                                 lineHeight: 1, marginTop: '0.1rem' }}
                    >✕</button>
                </div>

                <form onSubmit={submit} style={css.body}>

                    {/* ── Selector de tipo ─────────────────────── */}
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
                        <span style={css.sectionTitle}>Tipo de evento</span>
                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4,1fr)', gap: '0.4rem' }}>
                            {TIPOS.map(t => {
                                const active = data.tipo === t.key;
                                return (
                                    <button key={t.key} type="button" onClick={() => cambiarTipo(t.key)}
                                        style={{
                                            padding: '0.6rem 0.25rem', fontSize: '0.75rem',
                                            fontWeight: active ? 700 : 400,
                                            border: `2px solid ${active ? '#2563eb' : '#e5e7eb'}`,
                                            borderRadius: '8px',
                                            backgroundColor: active ? '#eff6ff' : '#f9fafb',
                                            color: active ? '#1d4ed8' : '#6b7280',
                                            cursor: 'pointer', textAlign: 'center', lineHeight: 1.5,
                                            transition: 'all 0.12s',
                                        }}>
                                        <div style={{ fontSize: '1.1rem' }}>{t.emoji}</div>
                                        {t.label}
                                    </button>
                                );
                            })}
                        </div>
                    </div>

                    <hr style={css.divider} />

                 {/* ── Destino: Animal o Lote ───────────────── */}
                        <fieldset style={css.fieldset}>
                            <span style={css.sectionTitle}>Destino</span>

                            <label style={css.label}>
                                Animal
                                <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
                                    <input
                                        type="text"
                                        readOnly
                                        placeholder="Ningún animal seleccionado"
                                        value={animalLabel}
                                        style={{
                                            ...css.input,
                                            flex: 1,
                                            backgroundColor: '#f9fafb',
                                            cursor: 'default',
                                        }}
                                    />

                                    <button
                                        type="button"
                                        className="btn primary"
                                        style={{ whiteSpace: 'nowrap', flexShrink: 0 }}
                                        onClick={() => setAnimalModalOpen(true)}
                                    >
                                        Seleccionar
                                    </button>
                                </div>

                                {errors.animal_id && (
                                    <span style={css.error}>{errors.animal_id}</span>
                                )}
                            </label>

                            <label style={css.label}>
                                Lote
                                <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
                                    <input
                                        type="text"
                                        readOnly
                                        placeholder="Ningún lote seleccionado"
                                        value={loteLabel}
                                        style={{
                                            ...css.input,
                                            flex: 1,
                                            backgroundColor: '#f9fafb',
                                            cursor: 'default',
                                        }}
                                    />

                                    <button
                                        type="button"
                                        className="btn primary"
                                        style={{ whiteSpace: 'nowrap', flexShrink: 0 }}
                                        onClick={() => setLoteModalOpen(true)}
                                    >
                                        Seleccionar
                                    </button>
                                </div>

                                {errors.lote_id && (
                                    <span style={css.error}>{errors.lote_id}</span>
                                )}
                            </label>

                            <span style={css.labelHint}>
                                Selecciona un animal o un lote, no ambos.
                            </span>
                        </fieldset>
                    
                    <hr style={css.divider} />

                    {/* ══════════════════════════════════════════
                        CAMPOS ESPECÍFICOS POR TIPO
                    ══════════════════════════════════════════ */}

                    {/* ── VACUNACIÓN ───────────────────────────── */}
                    {data.tipo === 'vacunacion' && (
                        <fieldset style={css.fieldset}>
                            <span style={css.sectionTitle}>Datos de la vacuna</span>

                            <label style={css.label}>
                                Vacuna *
                                <select
                                    value={data.vacuna_id}
                                    onChange={e => setData('vacuna_id', e.target.value)}
                                    required
                                    style={css.select}
                                >
                                    <option value="">— Seleccionar vacuna —</option>
                                    {vacunas.map(v => (
                                        <option key={v.id} value={v.id}>
                                            {v.nombre}
                                            {v.especie_objetivo ? ` · ${v.especie_objetivo}` : ''}
                                            {v.refuerzo_dias    ? ` (refuerzo ${v.refuerzo_dias}d)` : ''}
                                        </option>
                                    ))}
                                </select>
                                {errors.vacuna_id && <span style={css.error}>{errors.vacuna_id}</span>}
                                <span style={{ ...css.labelHint, marginTop: '0.15rem' }}>
                                    ¿No está en la lista?{' '}
                                    <button
                                        type="button"
                                        onClick={() => setVacunaModalOpen(true)}
                                        style={{color: '#2563eb',background: 'none', border: 'none',padding: 0,cursor: 'pointer',textDecoration: 'none',fontWeight: 600,
                                        }}>
                                        Registrar nueva vacuna
                                    </button>
                                </span>
                            </label>

                            <div style={css.row2}>
                                <label style={css.label}>
                                    Dosis <span style={css.labelHint}>(opcional)</span>
                                    <input type="text" value={data.dosis}
                                        onChange={e => setData('dosis', e.target.value)}
                                        placeholder="Ej: 2 ml, 5 cc"
                                        style={css.input} />
                                    {errors.dosis && <span style={css.error}>{errors.dosis}</span>}
                                </label>

                                <label style={css.label}>
                                    Lote <span style={css.labelHint}>(opcional)</span>
                                    <input type="text" value={data.lote_vacuna}
                                        onChange={e => setData('lote_vacuna', e.target.value)}
                                        placeholder="Ej: LOT-2024-A"
                                        style={css.input} />
                                    {errors.lote_vacuna && <span style={css.error}>{errors.lote_vacuna}</span>}
                                </label>
                            </div>
                        </fieldset>
                    )}

                    {/* ── CONSULTA / REVISIÓN ──────────────────── */}
                    {(data.tipo === 'consulta' || data.tipo === 'revision') && (
                        <fieldset style={css.fieldset}>
                            <span style={css.sectionTitle}>
                                {data.tipo === 'consulta' ? 'Datos de la consulta' : 'Datos de la revisión'}
                            </span>

                            <label style={css.label}>
                                {data.tipo === 'consulta' ? 'Diagnóstico / motivo' : 'Objetivo de la revisión'}
                                <span style={css.labelHint}>(opcional)</span>
                                <input type="text" value={data.diagnostico}
                                    onChange={e => setData('diagnostico', e.target.value)}
                                    placeholder={
                                        data.tipo === 'consulta'
                                            ? 'Ej: Fiebre, pérdida de peso, cojera…'
                                            : 'Ej: Control de gestación, evaluación de condición corporal…'
                                    }
                                    style={css.input} />
                                {errors.diagnostico && <span style={css.error}>{errors.diagnostico}</span>}
                            </label>
                        </fieldset>
                    )}

                    {/* ── EMERGENCIA ───────────────────────────── */}
                    {data.tipo === 'emergencia' && (
                        <fieldset style={css.fieldset}>
                            <span style={css.sectionTitle}>Datos de la emergencia</span>

                            <label style={css.label}>
                                Diagnóstico *
                                <input type="text" value={data.diagnostico}
                                    onChange={e => setData('diagnostico', e.target.value)}
                                    placeholder="Ej: Neumonía, timpanismo, fractura…"
                                    required
                                    style={css.input} />
                                {errors.diagnostico && <span style={css.error}>{errors.diagnostico}</span>}
                            </label>

                            <label style={css.label}>
                                Tratamiento aplicado <span style={css.labelHint}>(opcional)</span>
                                <textarea
                                    value={data.tratamiento}
                                    onChange={e => setData('tratamiento', e.target.value)}
                                    placeholder="Ej: Penicilina 10 ml IM, suero oral, reposo…"
                                    style={css.textarea}
                                />
                                {errors.tratamiento && <span style={css.error}>{errors.tratamiento}</span>}
                            </label>
                        </fieldset>
                    )}

                    <hr style={css.divider} />

                    {/* ── CAMPOS COMUNES ───────────────────────── */}
                    <fieldset style={css.fieldset}>
                        <span style={css.sectionTitle}>Programación</span>

                        <div style={css.row2}>
                            <label style={css.label}>
                                Fecha programada *
                                <input type="date" value={data.fecha_programada}
                                    onChange={e => setData('fecha_programada', e.target.value)}
                                    required style={css.input} />
                                {errors.fecha_programada && <span style={css.error}>{errors.fecha_programada}</span>}
                            </label>

                            <label style={css.label}>
                                Responsable <span style={css.labelHint}>(opcional)</span>
                                <input type="text" value={data.responsable}
                                    onChange={e => setData('responsable', e.target.value)}
                                    placeholder="Ej: Dr. García, MVZ López…"
                                    style={css.input} />
                                {errors.responsable && <span style={css.error}>{errors.responsable}</span>}
                            </label>
                        </div>

                        <label style={css.label}>
                            Observaciones <span style={css.labelHint}>(opcional)</span>
                            <textarea
                                value={data.observaciones}
                                onChange={e => setData('observaciones', e.target.value)}
                                placeholder="Cualquier nota adicional relevante…"
                                style={{ ...css.textarea, minHeight: '60px' }}
                            />
                            {errors.observaciones && <span style={css.error}>{errors.observaciones}</span>}
                        </label>
                    </fieldset>

                    {/* Errores sin campo específico */}
                    {Object.keys(errors).some(k =>
                        !['animal_id','lote_id','vacuna_id','dosis','lote_vacuna','diagnostico',
                          'tratamiento','fecha_programada','responsable','observaciones'].includes(k)
                    ) && (
                        <div style={css.error}>
                            {Object.entries(errors)
                                .filter(([k]) => !['animal_id','lote_id','vacuna_id','dosis','lote_vacuna',
                                    'diagnostico','tratamiento','fecha_programada',
                                    'responsable','observaciones'].includes(k))
                                .map(([, v]) => v).join(' · ')}
                        </div>
                    )}

                    {/* ── Acciones ─────────────────────────────── */}
                    <div style={{ display: 'flex', gap: '0.75rem', paddingTop: '0.25rem' }}>
                        <button type="button" className="btn secondary"
                            style={{ flex: 1 }} onClick={handleClose} disabled={processing}>
                            Cancelar
                        </button>
                        <button type="submit" className="btn primary"
                            style={{ flex: 2, opacity: processing ? 0.7 : 1 }} disabled={processing}>
                            {processing ? 'Guardando…' : `Registrar ${tipoActual?.label.toLowerCase()}`}
                        </button>
                    </div>
                </form>
            </div>

            {/* Sub-modal: seleccionar animal */}
            <ModalAnimalSelect
                isOpen={animalModalOpen}
                onClose={() => setAnimalModalOpen(false)}
                onSelect={(animal) => {
                    setData(prev => ({
                        ...prev,
                        animal_id: animal.id,
                        lote_id: '',
                    }));
                    setAnimalModalOpen(false);
                }}
                animals={animals}
            />
            <ModalLoteSelect
                isOpen={loteModalOpen}
                onClose={() => setLoteModalOpen(false)}
                onSelect={(lote) => {
                    setData(prev => ({
                        ...prev,
                        lote_id: lote.id,
                        animal_id: '',
                    }));
                    setLoteModalOpen(false);
                }}
                lotes={lotes}
            />
            <ModalNuevaVacuna
    isOpen={vacunaModalOpen}
    onClose={() => setVacunaModalOpen(false)}
/>
        </div>
    );
}