import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import ModalAnimalSelect from './ModalAnimalSelect';

function Health(props) {
  const { flash } = usePage().props || {};

  const {
    events = {},
    alerts = { up_to_date: [], due_soon: [], overdue: [] },
    animals = [],
    vacunas = [],
    pending = [],
    done = [],
    treatments = [],
    recommendations = [],
    year,
    month,
    month_iso,
    especies = [], 
    razasPorEspecie = {},
    estadosProductivos = {},
  } = props;

  // Estado para el modal de selección de animal
  const [isModalOpen, setIsModalOpen] = useState(false);

  // pestañas del panel derecho
  const [tab, setTab] = useState('vacunas');

  // estado del calendario
  const initY = year ?? new Date().getFullYear();
  const initM = (month ?? (new Date().getMonth() + 1)) - 1; // 0..11
  const [y, setY] = useState(initY);
  const [m, setM] = useState(initM);

  const weeks = useMemo(() => {
    const firstDay = new Date(y, m, 1).getDay(); // 0..6
    const daysInMonth = new Date(y, m + 1, 0).getDate();
    const cells = Array(firstDay).fill(null).concat(
      Array.from({ length: daysInMonth }, (_, i) => i + 1)
    );
    const out = [];
    for (let i = 0; i < cells.length; i += 7) {
      out.push(cells.slice(i, i + 7));
    }
    return out;
  }, [y, m]);

  const monthNames = [
    'January','February','March','April','May','June',
    'July','August','September','October','November','December'
  ];

  function changeMonth(delta) {
    const d = new Date(y, m + delta, 1);
    setY(d.getFullYear());
    setM(d.getMonth());
    router.get(
      '/health',
      { month: d.toISOString().slice(0, 7) }, // YYYY-MM
      { preserveScroll: true }
    );
  }

  // --- Formulario de nueva cita ---
  const { data, setData, post, processing, reset, errors } = useForm({
    animal_id: '',
    vacuna_id: '',
    fecha: '',
    hora: '',
    notas: '',
  });

  function submit(e) {
    e.preventDefault();
    post('/health/appointments', {
      preserveScroll: true,
      onSuccess: () => reset(),
    });
  }

  // marcar vacunación como realizada
  function markDone(id, fecha) {
    router.patch(
      `/health/appointments/${id}/complete`,
      { fecha_aplicacion: fecha },
      { preserveScroll: true }
    );
  }

  // Manejador para seleccionar animal desde el modal
  const handleAnimalSelect = (animal) => {
    setData('animal_id', animal.id);
  };

  // Obtener el animal seleccionado para mostrarlo
  const selectedAnimal = animals.find(a => a.id === data.animal_id);

  return (
    <>
      <Head title="Módulo de Salud" />

      <div className="health-page">
        <div className="container-centered">
          {/* Encabezado */}
          <div className="health-header">
            <div>
              <h1>Módulo de Salud</h1>
              <p>Gestiona vacunas, tratamientos y el bienestar de tus animales</p>
            </div>
          </div>

          {/* Alertas generales */}
          {alerts.overdue?.length > 0 && (
            <div className="alert danger">
              Tienes {alerts.overdue.length} vacuna(s) atrasadas que requieren atención inmediata.
            </div>
          )}

          {flash?.success && (
            <div className="alert success">
              {flash.success}
            </div>
          )}

          {/* Grid principal: izquierda calendario, derecha pestañas */}
          <div className="health-grid">
            {/* ================= COLUMNA IZQUIERDA ================= */}
            <section className="card">
              <div className="card-header">
                <h2>Calendario de Salud</h2>
                <small>Programación de vacunas y tratamientos</small>
              </div>

              {/* navegación de mes */}
              <div className="calendar-nav">
                <button type="button" onClick={() => changeMonth(-1)}>{'<'}</button>
                <div>{monthNames[m]} {y}</div>
                <button type="button" onClick={() => changeMonth(1)}>{'>'}</button>
              </div>

              {/* calendario */}
              <div className="calendar-grid">
                {['Su','Mo','Tu','We','Th','Fr','Sa'].map(d => (
                  <div key={d} className="dow">{d}</div>
                ))}

                {weeks.flat().map((cell, idx) => {
                  if (cell === null) {
                    return <div key={idx} className="day empty" />;
                  }

                  const key = `${y}-${String(m + 1).padStart(2, '0')}-${String(cell).padStart(2,'0')}`;
                  const status = events[key]; // green | yellow | red
                  const extraClass = status ? ` status-${status}` : '';

                  return (
                    <div key={idx} className={`day${extraClass}`}>
                      {cell}
                    </div>
                  );
                })}
              </div>

              <div className="divider" />

              {/* Formulario nueva cita */}
              <div className="card-header">
                <h2>Nueva cita</h2>
              </div>

              <form onSubmit={submit} className="form-grid">
                {/* Campo de Animal con botón en lugar de select */}
                <label>
                  Animal
                  <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
                    <input
                      type="text"
                      value={selectedAnimal ? 
                        `${selectedAnimal.arete || `#${selectedAnimal.id}`}${selectedAnimal.alias ? ` - ${selectedAnimal.alias}` : ''} (${selectedAnimal.especie})` : 
                        ''
                      }
                      placeholder="Ningún animal seleccionado"
                      readOnly
                      style={{
                        flex: 1,
                        padding: '0.5rem',
                        border: '1px solid #d1d5db',
                        borderRadius: '4px',
                        backgroundColor: '#f9fafb',
                        fontSize: '0.875rem'
                      }}
                    />
                    <button
                      type="button"
                      onClick={() => setIsModalOpen(true)}
                      style={{
                        padding: '0.5rem 1rem',
                        backgroundColor: '#3b82f6',
                        color: 'white',
                        border: 'none',
                        borderRadius: '4px',
                        cursor: 'pointer',
                        fontSize: '0.875rem',
                        whiteSpace: 'nowrap'
                      }}
                      onMouseEnter={(e) => e.currentTarget.style.backgroundColor = '#2563eb'}
                      onMouseLeave={(e) => e.currentTarget.style.backgroundColor = '#3b82f6'}
                    >
                      Seleccionar
                    </button>
                  </div>
                  {errors.animal_id && (
                    <span style={{ color: '#ef4444', fontSize: '0.75rem', marginTop: '0.25rem', display: 'block' }}>
                      {errors.animal_id}
                    </span>
                  )}
                </label>

                <label>
                  Vacuna (opcional)
                  <select
                    value={data.vacuna_id}
                    onChange={e => setData('vacuna_id', e.target.value)}
                    style={{
                      width: '100%',
                      padding: '0.5rem',
                      border: '1px solid #d1d5db',
                      borderRadius: '4px',
                      fontSize: '0.875rem'
                    }}
                  >
                    <option value="">Sin vacuna</option>
                    {vacunas.map(v => (
                      <option key={v.id} value={v.id}>
                        {v.nombre}
                      </option>
                    ))}
                  </select>
                </label>

                <label>
                  Fecha
                  <input
                    type="date"
                    value={data.fecha}
                    onChange={e => setData('fecha', e.target.value)}
                    required
                    style={{
                      width: '100%',
                      padding: '0.5rem',
                      border: '1px solid #d1d5db',
                      borderRadius: '4px',
                      fontSize: '0.875rem'
                    }}
                  />
                </label>

                <label>
                  Hora
                  <input
                    type="time"
                    value={data.hora}
                    onChange={e => setData('hora', e.target.value)}
                    style={{
                      width: '100%',
                      padding: '0.5rem',
                      border: '1px solid #d1d5db',
                      borderRadius: '4px',
                      fontSize: '0.875rem'
                    }}
                  />
                </label>

                <label className="full">
                  Notas
                  <textarea
                    value={data.notas}
                    onChange={e => setData('notas', e.target.value)}
                    style={{
                      width: '100%',
                      padding: '0.5rem',
                      border: '1px solid #d1d5db',
                      borderRadius: '4px',
                      fontSize: '0.875rem',
                      minHeight: '80px',
                      fontFamily: 'inherit'
                    }}
                  />
                </label>

                <div className="errors" style={{ color: '#ef4444', fontSize: '0.875rem' }}>
                  {Object.values(errors || {}).join(' ')}
                </div>

                <button
                  type="submit"
                  className="btn primary"
                  disabled={processing}
                  style={{
                    padding: '0.5rem 1rem',
                    backgroundColor: processing ? '#9ca3af' : '#3b82f6',
                    color: 'white',
                    border: 'none',
                    borderRadius: '4px',
                    cursor: processing ? 'not-allowed' : 'pointer',
                    fontSize: '0.875rem',
                    width: '100%'
                  }}
                >
                  {processing ? 'Guardando...' : 'Guardar cita'}
                </button>
              </form>
            </section>

            {/* ================= COLUMNA DERECHA ================= */}
            <section className="card">
              {/* Tabs */}
              <div className="tabs">
                <button
                  type="button"
                  className={tab === 'vacunas' ? 'active' : ''}
                  onClick={() => setTab('vacunas')}
                >
                  Vacunaciones
                </button>
                <button
                  type="button"
                  className={tab === 'tratamientos' ? 'active' : ''}
                  onClick={() => setTab('tratamientos')}
                >
                  Tratamientos
                </button>
                <button
                  type="button"
                  className={tab === 'recs' ? 'active' : ''}
                  onClick={() => setTab('recs')}
                >
                  Recomendaciones
                </button>
              </div>

              {/* ------- TAB VACUNACIONES ------- */}
              {tab === 'vacunas' && (
                <>
                  <div className="card-header">
                    <h2>Vacunaciones Programadas</h2>
                    <small>Estado de vacunas por animal</small>
                  </div>

                  <div className="list">
                    {pending.length === 0 && done.length === 0 && (
                      <div className="small-text">
                        No hay vacunaciones registradas en este mes.
                      </div>
                    )}

                    {pending.map(c => (
                      <div key={c.id} className="list-item">
                        <div>
                          <div className="li-title">{c.vacuna || 'Vacuna'}</div>
                          <div className="li-sub">
                            {c.animal} · Fecha: {c.fecha}
                            {c.hora ? ` · ${c.hora}` : ''}
                          </div>
                        </div>
                        <div className="li-right">
                          <span className="badge yellow">Próximo</span>
                          <button
                            type="button"
                            className="btn"
                            onClick={() => markDone(c.id, c.fecha)}
                          >
                            Marcar como Completado
                          </button>
                        </div>
                      </div>
                    ))}

                    {done.map(c => (
                      <div key={c.id} className="list-item">
                        <div>
                          <div className="li-title">{c.vacuna || 'Vacuna'}</div>
                          <div className="li-sub">
                            {c.animal} · Fecha: {c.fecha}
                            {c.hora ? ` · ${c.hora}` : ''}
                          </div>
                        </div>
                        <div className="li-right">
                          <span className="badge green">Completado</span>
                        </div>
                      </div>
                    ))}
                  </div>
                </>
              )}

              {/* ------- TAB TRATAMIENTOS ------- */}
              {tab === 'tratamientos' && (
                <>
                  <div className="card-header">
                    <h2>Tratamientos Activos</h2>
                    <small>Medicamentos y terapias en curso</small>
                  </div>

                  <div className="list">
                    {treatments.length === 0 && (
                      <div className="small-text">
                        No hay tratamientos registrados en este mes.
                      </div>
                    )}

                    {treatments.map(t => (
                      <div key={t.id} className="list-item">
                        <div>
                          <div className="li-title">{t.nombre}</div>
                          <div className="li-sub">
                            {t.animal} · {t.rango}
                          </div>
                        </div>
                        <div className="li-right">
                          <span className={`badge ${t.estado === 'activo' ? 'yellow' : 'green'}`}>
                            {t.estado === 'activo' ? 'Activo' : 'Completado'}
                          </span>
                          <button type="button" className="btn ghost">
                            Ver Detalles
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                </>
              )}

              {/* ------- TAB RECOMENDACIONES ------- */}
              {tab === 'recs' && (
                <>
                  <div className="card-header">
                    <h2>Recomendaciones por Especie</h2>
                    <small>Programas sugeridos según edad y especie</small>
                  </div>

                  <div className="list">
                    {recommendations.length === 0 && (
                      <div className="small-text">
                        Sin recomendaciones definidas.
                      </div>
                    )}

                    {recommendations.map(r => (
                      <div key={r.id} className="list-item">
                        <div>
                          <div className="li-title">{r.especie}</div>
                          <div className="li-sub">Edad: {r.edad}</div>
                          <div className="li-sub">
                            Vacunas recomendadas:{' '}
                            {r.vacunas.map(v => (
                              <span
                                key={v}
                                className="badge gray"
                                style={{ marginRight: 4 }}
                              >
                                {v}
                              </span>
                            ))}
                          </div>
                        </div>
                        <div className="li-right">
                          <span className="badge gray">{r.frecuencia}</span>
                        </div>
                      </div>
                    ))}
                  </div>
                </>
              )}
            </section>
          </div>
        </div>
      </div>

      {/* Modal de selección de animal */}
      <ModalAnimalSelect
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        onSelect={handleAnimalSelect}
        animals={animals}
        especies={especies}
        razasPorEspecie={razasPorEspecie}
        estadosProductivos={estadosProductivos}
      />
    </>
  );
}

// Layout con sidebar light
Health.layout = page => <AppLayout>{page}</AppLayout>;

export default Health;