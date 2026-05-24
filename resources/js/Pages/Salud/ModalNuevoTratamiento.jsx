import { useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import ModalAnimalSelect from './ModalAnimalSelect';

/**
 * Modal para registrar un Tratamiento independiente (sin evento previo).
 * Ruta: POST /tratamientos  (tratamientos.store)
 *
 * Props:
 *  - isOpen   : boolean
 *  - onClose  : () => void
 *  - animals  : Animal[]
 *  - saludId  : number|null  — si viene de un EventoSalud específico, pre-vincula
 *  - animalId : number|null  — si viene de la ficha de un animal, pre-selecciona
 */
export default function ModalNuevoTratamiento({ isOpen, onClose, animals = [], saludId = null, animalId = null }) {
    const [animalModalOpen, setAnimalModalOpen] = useState(false);
    const firstRef = useRef(null);

    const { data, setData, post, processing, reset, errors } = useForm({
        animal_id:    animalId ?? '',
        salud_id:     saludId  ?? '',
        nombre:       '',
        fecha_inicio: new Date().toISOString().slice(0, 10), // hoy por defecto
        fecha_fin:    '',
        notas:        '',
        responsable:  '',
        estado:       'activo',
    });

    // Actualizar si cambian los props (ej: abrir desde distintos contextos)
    useEffect(() => {
        if (isOpen) {
            setData(prev => ({
                ...prev,
                animal_id: animalId ?? '',
                salud_id:  saludId  ?? '',
            }));
            setTimeout(() => firstRef.current?.focus(), 80);
        }
    }, [isOpen, animalId, saludId]);

    useEffect(() => {
        if (!isOpen) return;
        const onKey = (e) => { if (e.key === 'Escape' && !animalModalOpen) handleClose(); };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [isOpen, animalModalOpen]);

    function handleClose() { reset(); onClose(); }

    function submit(e) {
        e.preventDefault();
        post(route('tratamientos.store'), {
            preserveScroll: true,
            onSuccess: () => { reset(); onClose(); },
        });
    }

    const selectedAnimal = animals.find(a => a.id === data.animal_id);
    const animalLabel = selectedAnimal
        ? [
            selectedAnimal.arete   ? `#${selectedAnimal.arete}` : '',
            selectedAnimal.alias   ?? '',
            selectedAnimal.especie ? `(${selectedAnimal.especie})` : '',
          ].filter(Boolean).join(' ')
        : '';

    // Calcular duración estimada en días si hay fecha_fin
    const duracionDias = (() => {
        if (!data.fecha_inicio || !data.fecha_fin) return null;
        const inicio = new Date(data.fecha_inicio);
        const fin    = new Date(data.fecha_fin);
        const diff   = Math.ceil((fin - inicio) / (1000 * 60 * 60 * 24));
        return diff > 0 ? diff : null;
    })();

    if (!isOpen) return null;

    // ── Estilos ──────────────────────────────────────────────────
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
            width: '100%', maxWidth: '480px',
            maxHeight: '92vh', overflowY: 'auto',
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
        textarea: {
            padding: '0.55rem 0.75rem',
            border: '1px solid #d1d5db', borderRadius: '7px',
            fontSize: '0.875rem', width: '100%', boxSizing: 'border-box',
            resize: 'vertical', minHeight: '70px', fontFamily: 'inherit',
        },
        row2: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.75rem' },
        error: { color: '#ef4444', fontSize: '0.75rem' },
        sectionTitle: {
            fontSize: '0.72rem', fontWeight: 700, letterSpacing: '0.07em',
            color: '#9ca3af', textTransform: 'uppercase',
        },
        divider: { border: 'none', borderTop: '1px solid #f3f4f6', margin: '0.1rem 0' },
        infoBox: {
            backgroundColor: '#f0fdf4', border: '1px solid #bbf7d0',
            borderRadius: '8px', padding: '0.6rem 0.9rem',
            fontSize: '0.8rem', color: '#15803d',
        },
        warnBox: {
            backgroundColor: '#fefce8', border: '1px solid #fde68a',
            borderRadius: '8px', padding: '0.6rem 0.9rem',
            fontSize: '0.8rem', color: '#92400e',
        },
    };

    return (
        <div style={css.backdrop} onClick={handleClose}>
            <div style={css.panel} onClick={e => e.stopPropagation()}>

                {/* ── Cabecera ── */}
                <div style={css.header}>
                    <div>
                        <h2 style={{ margin: 0, fontSize: '1.1rem', fontWeight: 700, color: '#111827' }}>
                            ⌁ Nuevo tratamiento
                        </h2>
                        <p style={{ margin: '0.2rem 0 0', fontSize: '0.8rem', color: '#6b7280' }}>
                            Medicamento, terapia o protocolo en curso
                        </p>
                    </div>
                    <button type="button" onClick={handleClose}
                        style={{ background: 'none', border: 'none', fontSize: '1.1rem', cursor: 'pointer', color: '#9ca3af' }}>
                        ✕
                    </button>
                </div>

                <form onSubmit={submit} style={css.body}>

                    {/* ── Animal ── */}
                    <span style={css.sectionTitle}>Animal</span>

                    <label style={css.label}>
                        Animal *
                        <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
                            <input type="text" readOnly
                                placeholder="Ningún animal seleccionado"
                                value={animalLabel}
                                style={{ ...css.input, flex: 1, backgroundColor: '#f9fafb', cursor: 'default' }} />
                            <button type="button" className="btn primary"
                                style={{ whiteSpace: 'nowrap', flexShrink: 0 }}
                                onClick={() => setAnimalModalOpen(true)}>
                                Seleccionar
                            </button>
                        </div>
                        {errors.animal_id && <span style={css.error}>{errors.animal_id}</span>}
                    </label>

                    <hr style={css.divider} />

                    {/* ── Tratamiento ── */}
                    <span style={css.sectionTitle}>Tratamiento</span>

                    <label style={css.label}>
                        Nombre del tratamiento *
                        <input ref={firstRef} type="text"
                            value={data.nombre}
                            onChange={e => setData('nombre', e.target.value)}
                            placeholder="Ej: Penicilina G, Oxitetraciclina, Suero oral…"
                            required style={css.input} />
                        {errors.nombre && <span style={css.error}>{errors.nombre}</span>}
                    </label>

                    <label style={css.label}>
                        Dosis, vía y frecuencia <span style={css.hint}>(opcional)</span>
                        <textarea
                            value={data.notas}
                            onChange={e => setData('notas', e.target.value)}
                            placeholder="Ej: 10 ml IM cada 24h por 5 días, diluir en 500 ml de solución…"
                            style={css.textarea} />
                        {errors.notas && <span style={css.error}>{errors.notas}</span>}
                    </label>

                    <hr style={css.divider} />

                    {/* ── Fechas ── */}
                    <span style={css.sectionTitle}>Duración</span>

                    <div style={css.row2}>
                        <label style={css.label}>
                            Fecha inicio *
                            <input type="date"
                                value={data.fecha_inicio}
                                onChange={e => setData('fecha_inicio', e.target.value)}
                                required style={css.input} />
                            {errors.fecha_inicio && <span style={css.error}>{errors.fecha_inicio}</span>}
                        </label>

                        <label style={css.label}>
                            Fecha fin <span style={css.hint}>(opcional)</span>
                            <input type="date"
                                value={data.fecha_fin}
                                onChange={e => setData('fecha_fin', e.target.value)}
                                min={data.fecha_inicio || undefined}
                                style={css.input} />
                            {errors.fecha_fin && <span style={css.error}>{errors.fecha_fin}</span>}
                        </label>
                    </div>

                    {/* Feedback de duración calculada */}
                    {duracionDias && (
                        <div style={css.infoBox}>
                            ✓ Duración: <strong>{duracionDias} días</strong>. El sistema alertará cuando se acerque la fecha de fin.
                        </div>
                    )}
                    {!data.fecha_fin && (
                        <div style={css.warnBox}>
                            Sin fecha fin el tratamiento permanecerá activo indefinidamente hasta marcarlo como completado manualmente.
                        </div>
                    )}

                    <hr style={css.divider} />

                    {/* ── Responsable ── */}
                    <label style={css.label}>
                        Responsable <span style={css.hint}>(opcional)</span>
                        <input type="text"
                            value={data.responsable}
                            onChange={e => setData('responsable', e.target.value)}
                            placeholder="Ej: MVZ García, encargado del rancho…"
                            style={css.input} />
                        {errors.responsable && <span style={css.error}>{errors.responsable}</span>}
                    </label>

                    {/* Indicador de evento vinculado (solo informativo, viene por prop) */}
                    {saludId && (
                        <div style={{ ...css.infoBox, backgroundColor: '#eff6ff', borderColor: '#bfdbfe', color: '#1e40af' }}>
                            🔗 Este tratamiento quedará vinculado al evento de salud #{saludId}.
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
                            {processing ? 'Guardando…' : 'Registrar tratamiento'}
                        </button>
                    </div>
                </form>
            </div>

            <ModalAnimalSelect
                isOpen={animalModalOpen}
                onClose={() => setAnimalModalOpen(false)}
                onSelect={(animal) => {
                    setData('animal_id', animal.id);
                    setAnimalModalOpen(false);
                }}
                animals={animals}
            />
        </div>
    );
}