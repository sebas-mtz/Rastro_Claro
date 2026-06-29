import { useState, useEffect } from 'react';

function ModalLoteSelect({
    isOpen,
    onClose,
    onSelect,
    lotes = [],
}) {
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedLote, setSelectedLote] = useState(null);

    useEffect(() => {
        if (!isOpen) {
            setSearchTerm('');
            setSelectedLote(null);
        }
    }, [isOpen]);

    if (!isOpen) return null;

    const filteredLotes = lotes.filter(lote => {
        const q = searchTerm.toLowerCase();

        return !q ||
            (lote.nombre?.toLowerCase() || '').includes(q) ||
            (lote.descripcion?.toLowerCase() || '').includes(q) ||
            (lote.especie?.toLowerCase() || '').includes(q) ||
            (lote.estado?.toLowerCase() || '').includes(q);
    });

    function handleConfirm() {
        if (!selectedLote) return;
        onSelect(selectedLote);
        onClose();
    }

    return (
        <div
            className="fixed inset-0 z-[2000] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
            onClick={onClose}
        >
            <div
                className="bg-white rounded-xl shadow-xl w-full max-w-3xl flex flex-col overflow-hidden"
                style={{ maxHeight: '85vh' }}
                onClick={e => e.stopPropagation()}
            >
                <div className="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <div>
                        <h2 className="text-base font-semibold text-gray-900">
                            Seleccionar Lote
                        </h2>
                        <p className="text-xs text-gray-400 mt-0.5">
                            {filteredLotes.length} de {lotes.length} lotes
                        </p>
                    </div>

                    <button
                        type="button"
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
                            placeholder="Buscar por nombre, especie, estado o descripción…"
                            className="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                        />
                    </div>
                </div>

                <div className="flex-1 overflow-y-auto px-5 py-3 space-y-1.5">
                    {filteredLotes.length === 0 ? (
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
                            <p className="text-xs mt-1">
                                Prueba con otro nombre o estado del lote
                            </p>
                        </div>
                    ) : (
                        filteredLotes.map(lote => {
                            const isSelected = selectedLote?.id === lote.id;

                            return (
                                <button
                                    key={lote.id}
                                    type="button"
                                    onClick={() => setSelectedLote(lote)}
                                    className={[
                                        'w-full flex items-center gap-3 px-3 py-2.5 rounded-lg border text-left transition-all',
                                        isSelected
                                            ? 'bg-blue-50 border-blue-300 ring-1 ring-blue-300'
                                            : 'bg-white border-gray-100 hover:border-gray-200 hover:bg-gray-50',
                                    ].join(' ')}
                                >
                                    <div className="w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold flex-shrink-0 bg-green-100 text-green-700">
                                        {lote.nombre?.charAt(0)?.toUpperCase() ?? 'L'}
                                    </div>

                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center gap-2 flex-wrap">
                                            <span className="text-sm font-medium text-gray-900">
                                                {lote.nombre ?? `Lote #${lote.id}`}
                                            </span>
                                        </div>

                                        <div className="flex items-center gap-1.5 flex-wrap mt-0.5">

                                        {lote.corral_potrero && (
                                            <span className="text-xs text-gray-400">
                                                {lote.corral_potrero}
                                            </span>
                                        )}

                                        {lote.responsable?.name && (
                                            <>
                                                <span className="text-gray-200">·</span>
                                                <span className="text-xs text-gray-400">
                                                    {lote.responsable.name}
                                                </span>
                                            </>
                                        )}

                                        {lote.descripcion && (
                                            <>
                                                <span className="text-gray-200">·</span>
                                                <span className="text-xs text-gray-400 truncate max-w-xs">
                                                    {lote.descripcion}
                                                </span>
                                            </>
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
                        {selectedLote ? (
                            <span>
                                Seleccionado:{' '}
                                <strong className="text-gray-800">
                                    {selectedLote.nombre ?? `Lote #${selectedLote.id}`}
                                </strong>
                            </span>
                        ) : (
                            <span>Ningún lote seleccionado</span>
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
                            disabled={!selectedLote}
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

export default ModalLoteSelect;