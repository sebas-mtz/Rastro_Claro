import { useState, useEffect } from 'react';

function ModalAnimalSelect({
    isOpen,
    onClose,
    onSelect,
    animals = [],
    especies = [],
    razasPorEspecie = {},
    estadosProductivos = {},
}) {
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedAnimal, setSelectedAnimal] = useState(null);
    const [filtroEspecie, setFiltroEspecie] = useState('');
    const [filtroRaza, setFiltroRaza] = useState('');
    const [filtroEstadoProductivo, setFiltroEstadoProductivo] = useState('');
    const [filtroSexo, setFiltroSexo] = useState('');

    const razasDisponibles = filtroEspecie
        ? (razasPorEspecie[filtroEspecie] || [])
        : [];

    const estadosDisponibles = filtroEspecie
        ? (estadosProductivos[filtroEspecie] || [])
        : [];

    useEffect(() => {
        setFiltroRaza('');
        setFiltroEstadoProductivo('');
    }, [filtroEspecie]);

    // Resetear al abrir/cerrar
    useEffect(() => {
        if (!isOpen) {
            setSearchTerm('');
            setSelectedAnimal(null);
            setFiltroEspecie('');
            setFiltroRaza('');
            setFiltroEstadoProductivo('');
            setFiltroSexo('');
        }
    }, [isOpen]);

    if (!isOpen) return null;

    const filteredAnimals = animals.filter(a => {
        const q = searchTerm.toLowerCase();
        const matchSearch = !q ||
            (a.arete?.toLowerCase() || '').includes(q) ||
            (a.alias?.toLowerCase() || '').includes(q) ||
            (a.raza?.toLowerCase() || '').includes(q) ||
            (a.especie?.toLowerCase() || '').includes(q);

        return matchSearch
            && (!filtroEspecie || a.especie === filtroEspecie)
            && (!filtroRaza || a.raza === filtroRaza)
            && (!filtroEstadoProductivo || a.estado_productivo === filtroEstadoProductivo)
            && (!filtroSexo || a.sexo === filtroSexo);
    });

    function handleConfirm() {
        if (!selectedAnimal) return;
        onSelect(selectedAnimal);
        onClose();
    }

    const avatarColor = (especie) => {
        const map = {
            'Bovino': 'bg-blue-100 text-blue-700',
            'Porcino': 'bg-pink-100 text-pink-700',
            'Ovino': 'bg-amber-100 text-amber-700',
            'Caprino': 'bg-green-100 text-green-700',
            'Equino': 'bg-purple-100 text-purple-700',
        };
        return map[especie] ?? 'bg-gray-100 text-gray-600';
    };

    const estadoBadge = (estado) => {
        const map = {
            'Lactante': 'bg-blue-50 text-blue-700',
            'Gestante': 'bg-purple-50 text-purple-700',
            'Seco': 'bg-gray-100 text-gray-600',
            'Engorda': 'bg-amber-50 text-amber-700',
        };
        return map[estado] ?? 'bg-gray-100 text-gray-600';
    };

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
            onClick={onClose}
        >
            <div
                className="bg-white rounded-xl shadow-xl w-full max-w-3xl flex flex-col overflow-hidden"
                style={{ maxHeight: '85vh' }}
                onClick={e => e.stopPropagation()}
            >
                <div className="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <div>
                        <h2 className="text-base font-semibold text-gray-900">Seleccionar Animal</h2>
                        <p className="text-xs text-gray-400 mt-0.5">
                            {filteredAnimals.length} de {animals.length} animales
                        </p>
                    </div>
                    <button
                        onClick={onClose}
                        className="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                    >
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div className="px-5 py-3 border-b border-gray-100">
                    <div className="relative">
                        <svg
                            className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"
                            />
                        </svg>
                        <input
                            autoFocus
                            type="text"
                            value={searchTerm}
                            onChange={e => setSearchTerm(e.target.value)}
                            placeholder="Buscar por arete, nombre, raza o especie…"
                            className="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                        />
                    </div>
                </div>

                <div className="px-5 py-3 bg-gray-50 border-b border-gray-100 grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <select
                        value={filtroEspecie}
                        onChange={e => setFiltroEspecie(e.target.value)}
                        className="text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Todas las especies</option>
                        {especies.map(e => (
                            <option key={e} value={e}>{e}</option>
                        ))}
                    </select>

                    <select
                        value={filtroRaza}
                        onChange={e => setFiltroRaza(e.target.value)}
                        disabled={!filtroEspecie}
                        className="text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        <option value="">Todas las razas</option>
                        {razasDisponibles.map(r => (
                            <option key={r} value={r}>{r}</option>
                        ))}
                    </select>

                    <select
                        value={filtroEstadoProductivo}
                        onChange={e => setFiltroEstadoProductivo(e.target.value)}
                        disabled={!filtroEspecie}
                        className="text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        <option value="">Estado productivo</option>
                        {estadosDisponibles.map(e => (
                            <option key={e} value={e}>{e}</option>
                        ))}
                    </select>

                    <select
                        value={filtroSexo}
                        onChange={e => setFiltroSexo(e.target.value)}
                        className="text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Ambos sexos</option>
                        <option value="M">♂ Macho</option>
                        <option value="H">♀ Hembra</option>
                    </select>
                </div>

                <div className="flex-1 overflow-y-auto px-5 py-3 space-y-1.5">
                    {filteredAnimals.length === 0 ? (
                        <div className="flex flex-col items-center justify-center py-16 text-gray-400">
                            <svg className="w-10 h-10 mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={1.5}
                                    d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            <p className="text-sm font-medium">Sin resultados</p>
                            <p className="text-xs mt-1">Prueba con otros filtros o términos de búsqueda</p>
                        </div>
                    ) : (
                        filteredAnimals.map(animal => {
                            const isSelected = selectedAnimal?.id === animal.id;
                            return (
                                <button
                                    key={animal.id}
                                    type="button"
                                    onClick={() => setSelectedAnimal(animal)}
                                    className={[
                                        'w-full flex items-center gap-3 px-3 py-2.5 rounded-lg border text-left transition-all',
                                        isSelected
                                            ? 'bg-blue-50 border-blue-300 ring-1 ring-blue-300'
                                            : 'bg-white border-gray-100 hover:border-gray-200 hover:bg-gray-50',
                                    ].join(' ')}
                                >
                                    <div className={`w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold flex-shrink-0 ${avatarColor(animal.especie)}`}>
                                        {animal.especie?.charAt(0) ?? '?'}
                                    </div>

                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center gap-2 flex-wrap">
                                            <span className="text-sm font-medium text-gray-900">
                                                {animal.arete ? `#${animal.arete}` : `ID ${animal.id}`}
                                            </span>
                                            {animal.alias && (
                                                <span className="text-sm text-gray-500">— {animal.alias}</span>
                                            )}
                                            {animal.sexo && (
                                                <span className="text-xs text-gray-400">
                                                    {animal.sexo === 'M' ? '♂' : '♀'}
                                                </span>
                                            )}
                                        </div>
                                        <div className="flex items-center gap-1.5 flex-wrap mt-0.5">
                                            {animal.especie && (
                                                <span className="text-xs text-gray-400">{animal.especie}</span>
                                            )}
                                            {animal.raza && (
                                                <>
                                                    <span className="text-gray-200">·</span>
                                                    <span className="text-xs text-gray-400">{animal.raza}</span>
                                                </>
                                            )}
                                            {animal.estado_productivo && (
                                                <span className={`text-xs px-1.5 py-0.5 rounded-md font-medium ${estadoBadge(animal.estado_productivo)}`}>
                                                    {animal.estado_productivo}
                                                </span>
                                            )}
                                        </div>
                                    </div>

                                    {isSelected && (
                                        <svg className="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M5 13l4 4L19 7" />
                                        </svg>
                                    )}
                                </button>
                            );
                        })
                    )}
                </div>

                <div className="flex items-center justify-between px-5 py-3 border-t border-gray-100 bg-gray-50">
                    <div className="text-xs text-gray-500">
                        {selectedAnimal ? (
                            <span>
                                Seleccionado:{' '}
                                <strong className="text-gray-800">
                                    {selectedAnimal.arete ? `#${selectedAnimal.arete}` : selectedAnimal.alias}
                                </strong>
                            </span>
                        ) : (
                            <span>Ningún animal seleccionado</span>
                        )}
                    </div>
                    <div className="flex gap-2">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-4 py-2 text-sm rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100 transition"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            onClick={handleConfirm}
                            disabled={!selectedAnimal}
                            className="px-4 py-2 text-sm rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
                        >
                            Confirmar selección
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default ModalAnimalSelect;