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

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" onClick={handleClose}>
      <div className="bg-white rounded-lg w-11/12 max-w-4xl max-h-[80vh] flex flex-col shadow-lg" onClick={e => e.stopPropagation()}>
        {/* Header */}
        <div className="flex justify-between items-center p-4 border-b border-gray-200">
          <h2 className="text-xl font-semibold text-gray-900">Seleccionar Animal</h2>
          <button className="text-2xl text-gray-500 p-2" onClick={handleClose}>×</button>
        </div>

        {/* Buscador */}
        <div className="p-4 border-b border-gray-200">
          <input
            type="text"
            className="w-full p-2 border border-gray-300 rounded-md text-sm"
            placeholder="Buscar por arete, nombre, raza o especie..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            autoFocus
          />
        </div>

        {/* Filtros rápidos */}
        <div className="grid grid-cols-3 gap-4 p-4 border-b border-gray-200 bg-gray-50">
          <div className="flex flex-col gap-1">
            <label className="text-xs font-semibold text-gray-700 uppercase">Especie</label>
            <select 
              className="p-2 border border-gray-300 rounded-md text-sm"
              value={filtroEspecie}
              onChange={handleEspecieChange}
            >
              <option value="">Todas las especies</option>
              {especies.map(especie => (
                <option key={especie} value={especie}>{especie}</option>
              ))}
            </select>
          </div>

          <div className="flex flex-col gap-1">
            <label className="text-xs font-semibold text-gray-700 uppercase">Raza</label>
            <select 
              className="p-2 border border-gray-300 rounded-md text-sm"
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

          <div className="flex flex-col gap-1">
            <label className="text-xs font-semibold text-gray-700 uppercase">Estado Productivo</label>
            <select 
              className="p-2 border border-gray-300 rounded-md text-sm"
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
        <div className="flex flex-1 overflow-hidden">
          {/* Panel de filtros detallados (opcional) */}
          <div className="w-64 border-r border-gray-200 p-4 bg-gray-50 overflow-y-auto">
            <h4 className="text-sm font-semibold text-gray-700 mb-3">Filtros rápidos</h4>
            {/* Aquí puedes agregar más opciones de filtro si lo deseas */} 
          </div>

          {/* Lista de animales */}
          <div className="flex-1 overflow-y-auto p-4">
            {filteredAnimals.length === 0 ? (
              <div className="text-center py-8 text-gray-500 text-sm">
                No se encontraron animales con los filtros seleccionados
              </div>
            ) : (
              filteredAnimals.map(animal => (
                <div
                  key={animal.id}
                  className={`flex items-center p-3 border border-gray-200 rounded-lg mb-2 cursor-pointer transition-all duration-200
                    ${selectedAnimal?.id === animal.id ? 'bg-blue-50 border-blue-400' : ''}`}
                  onClick={() => handleSelect(animal)}
                  onMouseEnter={(e) => {
                    e.currentTarget.style.backgroundColor = '#f9fafb';
                  }}
                  onMouseLeave={(e) => {
                    e.currentTarget.style.backgroundColor = 
                      selectedAnimal?.id === animal.id ? '#eff6ff' : 'transparent';
                  }}
                >
                  <div className="w-10 h-10 rounded-full bg-blue-500 text-white flex items-center justify-center mr-4 font-bold text-lg">
                    {animal.especie?.charAt(0) || '?'}
                  </div>
                  <div className="flex-1">
                    <div className="text-sm font-semibold text-gray-900">
                      <strong>{animal.arete || `#${animal.id}`}</strong>
                      {animal.alias && <span> - {animal.alias}</span>}
                    </div>
                    <div className="text-xs text-gray-500 flex items-center gap-1">
                      <span>{animal.especie}</span>
                      {animal.raza && (
                        <>
                          <span className="mx-1">•</span>
                          <span>{animal.raza}</span>
                        </>
                      )}
                      {animal.estado_productivo && (
                        <>
                          <span className="mx-1">•</span>
                          <span>{animal.estado_productivo}</span>
                        </>
                      )}
                    </div>
                  </div>
                  {selectedAnimal?.id === animal.id && (
                    <div className="text-blue-500 font-bold text-lg ml-4">✓</div>
                  )}
                </div>
              ))
            )}
          </div>
        </div>

        {/* Footer */}
        <div className="flex justify-end gap-3 p-4 border-t border-gray-200">
          <button 
            className="p-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md"
            onClick={handleClose}
          >
            Cancelar
          </button>
          <button 
            className={`p-2 text-sm font-medium text-white rounded-md ${!selectedAnimal ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-500'}`}
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