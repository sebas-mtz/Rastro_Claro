import { useState, useEffect } from 'react';

function ModalAnimalSelect({ isOpen, onClose, onSelect, animals = [], especies = [], razasPorEspecie = {}, estadosProductivos = {} }) {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedAnimal, setSelectedAnimal] = useState(null);
  
  // Estados para los filtros
  const [filtroEspecie, setFiltroEspecie] = useState('');
  const [filtroRaza, setFiltroRaza] = useState('');
  const [filtroEstadoProductivo, setFiltroEstadoProductivo] = useState('');
  
  // Razas disponibles según especie seleccionada
  const [razasDisponibles, setRazasDisponibles] = useState([]);
  
  // Estados productivos disponibles según especie seleccionada
  const [estadosDisponibles, setEstadosDisponibles] = useState([]);

  // Actualizar razas y estados cuando cambia la especie
  useEffect(() => {
    if (filtroEspecie) {
      setRazasDisponibles(razasPorEspecie[filtroEspecie] || []);
      setEstadosDisponibles(estadosProductivos[filtroEspecie] || []);
      setFiltroRaza(''); // Resetear raza al cambiar especie
      setFiltroEstadoProductivo(''); // Resetear estado al cambiar especie
    } else {
      setRazasDisponibles([]);
      setEstadosDisponibles([]);
      setFiltroRaza('');
      setFiltroEstadoProductivo('');
    }
  }, [filtroEspecie, razasPorEspecie, estadosProductivos]);

  if (!isOpen) return null;

  // Aplicar todos los filtros a los animales
  const filteredAnimals = animals.filter(animal => {
    // Filtro de búsqueda por texto
    const matchesSearch = searchTerm === '' || 
      (animal.arete?.toLowerCase() || '').includes(searchTerm.toLowerCase()) ||
      (animal.alias?.toLowerCase() || '').includes(searchTerm.toLowerCase()) ||
      (animal.raza?.toLowerCase() || '').includes(searchTerm.toLowerCase()) ||
      (animal.especie?.toLowerCase() || '').includes(searchTerm.toLowerCase());

    // Filtro por especie
    const matchesEspecie = filtroEspecie === '' || animal.especie === filtroEspecie;
    
    // Filtro por raza
    const matchesRaza = filtroRaza === '' || animal.raza === filtroRaza;
    
    // Filtro por estado productivo
    const matchesEstado = filtroEstadoProductivo === '' || animal.estado_productivo === filtroEstadoProductivo;

    return matchesSearch && matchesEspecie && matchesRaza && matchesEstado;
  });

  const handleSelect = (animal) => {
    setSelectedAnimal(animal);
  };

  const handleConfirm = () => {
    if (selectedAnimal) {
      onSelect(selectedAnimal);
      handleClose();
    }
  };

  const handleClose = () => {
    setSearchTerm('');
    setSelectedAnimal(null);
    setFiltroEspecie('');
    setFiltroRaza('');
    setFiltroEstadoProductivo('');
    onClose();
  };

  const handleEspecieChange = (e) => {
    setFiltroEspecie(e.target.value);
  };

  // Estilos en línea
  const styles = {
    overlay: {
      position: 'fixed',
      top: 0,
      left: 0,
      right: 0,
      bottom: 0,
      backgroundColor: 'rgba(0, 0, 0, 0.5)',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      zIndex: 1000,
    },
    modal: {
      background: 'white',
      borderRadius: '8px',
      width: '90%',
      maxWidth: '900px',
      maxHeight: '80vh',
      display: 'flex',
      flexDirection: 'column',
      boxShadow: '0 4px 6px rgba(0, 0, 0, 0.1)',
    },
    header: {
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'center',
      padding: '1rem 1.5rem',
      borderBottom: '1px solid #e5e7eb',
    },
    headerTitle: {
      margin: 0,
      fontSize: '1.25rem',
      color: '#111827',
    },
    closeButton: {
      background: 'none',
      border: 'none',
      fontSize: '1.5rem',
      cursor: 'pointer',
      color: '#6b7280',
      padding: '0.5rem',
      lineHeight: 1,
    },
    searchContainer: {
      padding: '1rem 1.5rem',
      borderBottom: '1px solid #e5e7eb',
    },
    searchInput: {
      width: '100%',
      padding: '0.5rem',
      border: '1px solid #d1d5db',
      borderRadius: '4px',
      fontSize: '0.875rem',
    },
    filtersContainer: {
      display: 'grid',
      gridTemplateColumns: 'repeat(3, 1fr)',
      gap: '1rem',
      padding: '1rem 1.5rem',
      borderBottom: '1px solid #e5e7eb',
      backgroundColor: '#f9fafb',
    },
    filterGroup: {
      display: 'flex',
      flexDirection: 'column',
      gap: '0.25rem',
    },
    filterLabel: {
      fontSize: '0.75rem',
      fontWeight: 600,
      color: '#4b5563',
      textTransform: 'uppercase',
    },
    filterSelect: {
      padding: '0.5rem',
      border: '1px solid #d1d5db',
      borderRadius: '4px',
      fontSize: '0.875rem',
      backgroundColor: 'white',
    },
    content: {
      display: 'flex',
      flex: 1,
      overflow: 'hidden',
    },
    filterPanel: {
      width: '250px',
      borderRight: '1px solid #e5e7eb',
      padding: '1rem',
      backgroundColor: '#f9fafb',
      overflowY: 'auto',
    },
    filterTitle: {
      fontSize: '0.875rem',
      fontWeight: 600,
      color: '#374151',
      marginBottom: '0.75rem',
    },
    filterOption: {
      marginBottom: '0.5rem',
    },
    filterOptionLabel: {
      display: 'flex',
      alignItems: 'center',
      gap: '0.5rem',
      fontSize: '0.875rem',
      color: '#4b5563',
      cursor: 'pointer',
    },
    filterOptionInput: {
      cursor: 'pointer',
    },
    animalList: {
      flex: 1,
      overflowY: 'auto',
      padding: '1rem 1.5rem',
    },
    animalItem: {
      display: 'flex',
      alignItems: 'center',
      padding: '0.75rem',
      border: '1px solid #e5e7eb',
      borderRadius: '6px',
      marginBottom: '0.5rem',
      cursor: 'pointer',
      transition: 'all 0.2s',
    },
    animalItemSelected: {
      backgroundColor: '#eff6ff',
      borderColor: '#3b82f6',
    },
    animalAvatar: {
      width: '40px',
      height: '40px',
      borderRadius: '50%',
      overflow: 'hidden',
      marginRight: '1rem',
      flexShrink: 0,
      backgroundColor: '#3b82f6',
      color: 'white',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      fontWeight: 'bold',
      fontSize: '1.25rem',
    },
    animalInfo: {
      flex: 1,
    },
    animalName: {
      fontSize: '0.875rem',
      color: '#111827',
      marginBottom: '0.25rem',
    },
    animalDetails: {
      fontSize: '0.75rem',
      color: '#6b7280',
      display: 'flex',
      alignItems: 'center',
      gap: '0.25rem',
    },
    separator: {
      margin: '0 0.25rem',
    },
    selectedCheck: {
      color: '#3b82f6',
      fontWeight: 'bold',
      fontSize: '1.25rem',
      marginLeft: '1rem',
    },
    noResults: {
      textAlign: 'center',
      padding: '2rem',
      color: '#6b7280',
      fontSize: '0.875rem',
    },
    footer: {
      display: 'flex',
      justifyContent: 'flex-end',
      gap: '0.5rem',
      padding: '1rem 1.5rem',
      borderTop: '1px solid #e5e7eb',
    },
    button: {
      padding: '0.5rem 1rem',
      borderRadius: '4px',
      fontSize: '0.875rem',
      cursor: 'pointer',
      border: 'none',
    },
    buttonPrimary: {
      backgroundColor: '#3b82f6',
      color: 'white',
    },
    buttonPrimaryDisabled: {
      backgroundColor: '#9ca3af',
      cursor: 'not-allowed',
    },
    buttonSecondary: {
      backgroundColor: '#e5e7eb',
      color: '#374151',
      
    },
    badge: {
      display: 'inline-block',
      padding: '0.25rem 0.5rem',
      borderRadius: '4px',
      fontSize: '0.75rem',
      fontWeight: 500,
      backgroundColor: '#e5e7eb',
      color: '#374151',
    },
  };

  return (
    <div style={styles.overlay} onClick={handleClose}>
      <div style={styles.modal} onClick={e => e.stopPropagation()}>
        {/* Header */}
        <div style={styles.header}>
          <h2 style={styles.headerTitle}>Seleccionar Animal</h2>
          <button style={styles.closeButton} onClick={handleClose}>×</button>
        </div>

        {/* Buscador */}
        <div style={styles.searchContainer}>
          <input
            type="text"
            style={styles.searchInput}
            placeholder="Buscar por arete, nombre, raza o especie..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            autoFocus
          />
        </div>

        {/* Filtros rápidos */}
        <div style={styles.filtersContainer}>
          <div style={styles.filterGroup}>
            <label style={styles.filterLabel}>Especie</label>
            <select 
              style={styles.filterSelect}
              value={filtroEspecie}
              onChange={handleEspecieChange}
            >
              <option value="">Todas las especies</option>
              {especies.map(especie => (
                <option key={especie} value={especie}>{especie}</option>
              ))}
            </select>
          </div>

          <div style={styles.filterGroup}>
            <label style={styles.filterLabel}>Raza</label>
            <select 
              style={styles.filterSelect}
              value={filtroRaza}
              onChange={(e) => setFiltroRaza(e.target.value)}
              disabled={!filtroEspecie}
            >
              <option value="">Todas las razas</option>
              {razasDisponibles.map(raza => (
                <option key={raza} value={raza}>{raza}</option>
              ))}
            </select>
          </div>

          <div style={styles.filterGroup}>
            <label style={styles.filterLabel}>Estado Productivo</label>
            <select 
              style={styles.filterSelect}
              value={filtroEstadoProductivo}
              onChange={(e) => setFiltroEstadoProductivo(e.target.value)}
              disabled={!filtroEspecie}
            >
              <option value="">Todos los estados</option>
              {estadosDisponibles.map(estado => (
                <option key={estado} value={estado}>{estado}</option>
              ))}
            </select>
          </div>
        </div>

        {/* Contenido principal con dos columnas */}
        <div style={styles.content}>
          {/* Panel de filtros detallados (opcional) */}
          <div style={styles.filterPanel}>
            <h4 style={styles.filterTitle}>Filtros rápidos</h4>
            
            {/* Aquí puedes agregar más opciones de filtro si lo deseas */}
            <div style={styles.filterOption}>
              <label style={styles.filterOptionLabel}>
                <input type="checkbox" style={styles.filterOptionInput} />
                Solo machos
              </label>
            </div>
            <div style={styles.filterOption}>
              <label style={styles.filterOptionLabel}>
                <input type="checkbox" style={styles.filterOptionInput} />
                Solo hembras
              </label>
            </div>
            <div style={styles.filterOption}>
              <label style={styles.filterOptionLabel}>
                <input type="checkbox" style={styles.filterOptionInput} />
                Con peso registrado
              </label>
            </div>
            
            <div style={{ marginTop: '1rem' }}>
              <span style={styles.badge}>
                {filteredAnimals.length} animales
              </span>
            </div>
          </div>

          {/* Lista de animales */}
          <div style={styles.animalList}>
            {filteredAnimals.length === 0 ? (
              <div style={styles.noResults}>
                No se encontraron animales con los filtros seleccionados
              </div>
            ) : (
              filteredAnimals.map(animal => (
                <div
                  key={animal.id}
                  style={{
                    ...styles.animalItem,
                    ...(selectedAnimal?.id === animal.id ? styles.animalItemSelected : {})
                  }}
                  onClick={() => handleSelect(animal)}
                  onMouseEnter={(e) => {
                    e.currentTarget.style.backgroundColor = '#f9fafb';
                  }}
                  onMouseLeave={(e) => {
                    e.currentTarget.style.backgroundColor = 
                      selectedAnimal?.id === animal.id ? '#eff6ff' : 'transparent';
                  }}
                >
                  <div style={styles.animalAvatar}>
                    {animal.especie?.charAt(0) || '?'}
                  </div>
                  <div style={styles.animalInfo}>
                    <div style={styles.animalName}>
                      <strong>{animal.arete || `#${animal.id}`}</strong>
                      {animal.alias && <span> - {animal.alias}</span>}
                    </div>
                    <div style={styles.animalDetails}>
                      <span>{animal.especie}</span>
                      {animal.raza && (
                        <>
                          <span style={styles.separator}>•</span>
                          <span>{animal.raza}</span>
                        </>
                      )}
                      {animal.estado_productivo && (
                        <>
                          <span style={styles.separator}>•</span>
                          <span>{animal.estado_productivo}</span>
                        </>
                      )}
                      {animal.sexo && (
                        <>
                          <span style={styles.separator}>•</span>
                          <span>{animal.sexo === 'M' ? '♂' : '♀'}</span>
                        </>
                      )}
                    </div>
                  </div>
                  {selectedAnimal?.id === animal.id && (
                    <div style={styles.selectedCheck}>✓</div>
                  )}
                </div>
              ))
            )}
          </div>
        </div>

        {/* Footer */}
        <div style={styles.footer}>
          <button 
            style={{...styles.button, ...styles.buttonSecondary}}
            onClick={handleClose}
          >
            Cancelar
          </button>
          <button 
            style={{
              ...styles.button,
              ...styles.buttonPrimary,
              ...(!selectedAnimal ? styles.buttonPrimaryDisabled : {})
            }}
            onClick={handleConfirm}
            disabled={!selectedAnimal}
          >
            Seleccionar
          </button>
        </div>
      </div>
    </div>
  );
}

export default ModalAnimalSelect;