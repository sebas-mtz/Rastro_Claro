import { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import { Pencil, Trash2, ChevronLeft, ChevronRight, Lock, Search, SlidersHorizontal } from 'lucide-react';
import ModalPajilla from '@Pages/Genetica/Modalpajilla';

const estadoBadge = {
    disponible: 'bg-emerald-100 text-emerald-700 border border-emerald-200',
    utilizada: 'bg-blue-100 text-blue-700 border border-blue-200',
    dañada: 'bg-red-100 text-red-600 border border-red-200',
    inactiva: 'bg-gray-100 text-gray-500 border border-gray-200',
};

const ESTADOS_FILTRO = [
    { value: 'utilizada', label: 'Utilizadas' },
    { value: 'inactiva', label: 'Inutilizadas' },
    { value: 'dañada', label: 'Dañadas' },
];

// Solo aplica a Animal (semental interno). DonadorExterno solo tiene codigo/nombre.
function nombreAnimal(animal) {
    if (!animal) return null;
    if (animal.arete) {
        return `${animal.arete}${animal.alias ? ` (${animal.alias})` : ''}`;
    }
    return animal.identificador ?? `Animal #${animal.id}`;
}

function Badge({ label, map }) {
    return (
        <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${map[label] ?? 'bg-gray-100 text-gray-500'}`}>
            {label}
        </span>
    );
}

function ConfirmDeleteModal({ open, onClose, onConfirm, label }) {
    if (!open) return null;
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div className="absolute inset-0 bg-black/40 backdrop-blur-sm" onClick={onClose} />
            <div className="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
                <h3 className="text-base font-semibold text-gray-800 mb-2">Eliminar pajilla</h3>
                <p className="text-sm text-gray-600 mb-6">
                    ¿Estás seguro de eliminar la pajilla <strong>{label}</strong>? Esta acción no se puede deshacer.
                </p>
                <div className="flex justify-end gap-2">
                    <button
                        type="button"
                        onClick={onClose}
                        className="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        onClick={onConfirm}
                        className="px-4 py-2 rounded-lg text-sm font-medium bg-red-500 text-white hover:bg-red-600 transition"
                    >
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    );
}

function Pagination({ meta }) {
    if (!meta || meta.last_page <= 1) return null;
    return (
        <div className="flex items-center justify-between pt-4 border-t border-gray-100 mt-4">
            <p className="text-xs text-gray-400">
                Mostrando {meta.from}–{meta.to} de {meta.total}
            </p>
            <div className="flex items-center gap-1">
                <button
                    disabled={meta.current_page === 1}
                    onClick={() => router.get(route('genetica.index'), { page: meta.current_page - 1 }, { preserveState: true, preserveScroll: true })}
                    className="p-1.5 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition"
                >
                    <ChevronLeft className="w-4 h-4" />
                </button>
                {Array.from({ length: meta.last_page }, (_, i) => i + 1)
                    .filter((p) => Math.abs(p - meta.current_page) <= 2)
                    .map((p) => (
                        <button
                            key={p}
                            onClick={() => router.get(route('genetica.index'), { page: p }, { preserveState: true, preserveScroll: true })}
                            className={`w-8 h-8 rounded-lg text-xs font-medium transition ${
                                p === meta.current_page
                                    ? 'bg-blue-600 text-white'
                                    : 'border border-gray-200 text-gray-600 hover:bg-gray-50'
                            }`}
                        >
                            {p}
                        </button>
                    ))}
                <button
                    disabled={meta.current_page === meta.last_page}
                    onClick={() => router.get(route('genetica.index'), { page: meta.current_page + 1 }, { preserveState: true, preserveScroll: true })}
                    className="p-1.5 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition"
                >
                    <ChevronRight className="w-4 h-4" />
                </button>
            </div>
        </div>
    );
}

export default function TabPajillas({
    pajillas,
    termos = [],
    animales = [],
    donadoresExternos = [],
    filtrosPajillas = {},
}) {
    const [editPajilla, setEditPajilla] = useState(null);
    const [editOpen, setEditOpen] = useState(false);
    const [deletePajilla, setDeletePajilla] = useState(null);

    const [search, setSearch] = useState(filtrosPajillas.search ?? '');
    const [sort, setSort] = useState(filtrosPajillas.sort ?? 'default');
    const [estados, setEstados] = useState(filtrosPajillas.estados ?? []);
    const [filtrosAbiertos, setFiltrosAbiertos] = useState(false);

    const primerRender = useRef(true);
    const debounceRef = useRef(null);

    const items = pajillas?.data ?? [];
    const meta = pajillas?.meta ?? pajillas;

    // Cualquier cambio en search/sort/estados dispara una nueva consulta al
    // servidor (no se filtra en el cliente porque la tabla está paginada:
    // filtrar solo lo que ya llegó a esta página ocultaría resultados que
    // existen en otras páginas). El buscador se debounce para no golpear
    // el servidor en cada tecla.
    useEffect(() => {
        if (primerRender.current) {
            primerRender.current = false;
            return;
        }

        clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => {
            router.get(
                route('genetica.index'),
                {
                    search: search || undefined,
                    sort: sort !== 'default' ? sort : undefined,
                    estados: estados.length ? estados : undefined,
                    page: 1,
                },
                { preserveState: true, preserveScroll: true, replace: true }
            );
        }, 350);

        return () => clearTimeout(debounceRef.current);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search, sort, estados]);

    function toggleEstado(value) {
        setEstados((current) =>
            current.includes(value)
                ? current.filter((e) => e !== value)
                : [...current, value]
        );
    }

    function handleEdit(p) {
        setEditPajilla(p);
        setEditOpen(true);
    }

    function handleDelete() {
        router.delete(route('pajillas.destroy', deletePajilla.id), {
            onSuccess: () => setDeletePajilla(null),
            preserveScroll: true,
        });
    }

    const hayFiltrosActivos = search || sort !== 'default' || estados.length > 0;

    return (
        <>
            {/* Barra de búsqueda, orden y filtros */}
            <div className="flex flex-col gap-3 mb-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="relative flex-1 sm:max-w-xs">
                    <Search className="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                    <input
                        type="text"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Buscar código, arete, alias, nombre…"
                        className="w-full rounded-lg border border-gray-200 pl-9 pr-3 py-2 text-sm text-gray-800 outline-none transition focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400"
                    />
                </div>

                <div className="flex items-center gap-2">
                    <select
                        value={sort}
                        onChange={(e) => setSort(e.target.value)}
                        className="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 outline-none transition focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400"
                    >
                        <option value="default">Orden predeterminado</option>
                        <option value="codigo">Código (menor a mayor)</option>
                        <option value="fecha_colecta">Fecha de colecta</option>
                    </select>

                    <div className="relative">
                        <button
                            type="button"
                            onClick={() => setFiltrosAbiertos((v) => !v)}
                            className={`flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm font-medium transition ${
                                estados.length > 0
                                    ? 'border-blue-300 bg-blue-50 text-blue-700'
                                    : 'border-gray-200 text-gray-600 hover:bg-gray-50'
                            }`}
                        >
                            <SlidersHorizontal className="w-3.5 h-3.5" />
                            Filtrar
                            {estados.length > 0 && (
                                <span className="ml-0.5 rounded-full bg-blue-600 text-white text-[10px] px-1.5 py-0.5">
                                    {estados.length}
                                </span>
                            )}
                        </button>

                        {filtrosAbiertos && (
                            <div className="absolute right-0 z-10 mt-2 w-48 rounded-lg border border-gray-200 bg-white p-2 shadow-lg">
                                {ESTADOS_FILTRO.map((opt) => (
                                    <label
                                        key={opt.value}
                                        className="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer"
                                    >
                                        <input
                                            type="checkbox"
                                            checked={estados.includes(opt.value)}
                                            onChange={() => toggleEstado(opt.value)}
                                            className="rounded border-gray-300"
                                        />
                                        {opt.label}
                                    </label>
                                ))}
                            </div>
                        )}
                    </div>

                    {hayFiltrosActivos && (
                        <button
                            type="button"
                            onClick={() => {
                                setSearch('');
                                setSort('default');
                                setEstados([]);
                            }}
                            className="text-xs font-medium text-gray-400 hover:text-gray-600 transition"
                        >
                            Limpiar
                        </button>
                    )}
                </div>
            </div>

            {items.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-16 text-center">
                    <div className="w-14 h-14 rounded-2xl bg-cyan-50 flex items-center justify-center mb-4">
                        <span className="text-2xl">💧</span>
                    </div>
                    <p className="text-gray-500 font-medium">
                        {hayFiltrosActivos ? 'Sin resultados para estos filtros' : 'Sin pajillas registradas'}
                    </p>
                    <p className="text-sm text-gray-400 mt-1">
                        {hayFiltrosActivos
                            ? 'Ajusta la búsqueda o los filtros para ver más resultados.'
                            : 'Agrega tu primera pajilla con el botón de arriba.'}
                    </p>
                </div>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b border-gray-100">
                                <th className="text-left pb-3 pr-4 text-xs font-semibold text-gray-400 uppercase tracking-wide">Código</th>
                                <th className="text-left pb-3 pr-4 text-xs font-semibold text-gray-400 uppercase tracking-wide">Termo</th>
                                <th className="text-center pb-3 pr-4 text-xs font-semibold text-gray-400 uppercase tracking-wide">Canastilla</th>
                                <th className="text-left pb-3 pr-4 text-xs font-semibold text-gray-400 uppercase tracking-wide">Donador</th>
                                <th className="text-left pb-3 pr-4 text-xs font-semibold text-gray-400 uppercase tracking-wide">Lote</th>
                                <th className="text-left pb-3 pr-4 text-xs font-semibold text-gray-400 uppercase tracking-wide">Fecha de colecta</th>
                                <th className="text-right pb-3 pr-4 text-xs font-semibold text-gray-400 uppercase tracking-wide">Capacidad</th>
                                <th className="text-center pb-3 pr-4 text-xs font-semibold text-gray-400 uppercase tracking-wide">Estado</th>
                                <th className="pb-3" />
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-50">
                            {items.map((p) => {
                                const bloqueada = p.estado === 'dañada' || p.estado === 'inactiva';

                                return (
                                    <tr key={p.id} className="hover:bg-gray-50/60 transition group">
                                        <td className="py-3 pr-4 font-mono text-xs text-gray-600">{p.codigo}</td>

                                        <td className="py-3 pr-4 font-mono text-xs text-gray-500">
                                            {p.termo?.codigo ?? '—'}
                                        </td>

                                        <td className="py-3 pr-4 text-center tabular-nums text-gray-500">
                                            {p.canastilla_numero ?? '—'}
                                        </td>

                                        <td className="py-3 pr-4 text-gray-700">
                                            {p.animal ? (
                                                <div>
                                                    <p className="font-medium">{nombreAnimal(p.animal)}</p>
                                                    <p className="text-xs text-gray-400">Interno</p>
                                                </div>
                                            ) : p.donador_externo ? (
                                                <div>
                                                    <p className="font-medium">{p.donador_externo.nombre}</p>
                                                    <p className="text-xs text-gray-400">
                                                        Externo
                                                        {p.donador_externo.codigo
                                                            ? ` · Código: ${p.donador_externo.codigo}`
                                                            : ''}
                                                    </p>
                                                </div>
                                            ) : (
                                                '—'
                                            )}
                                        </td>

                                        <td className="py-3 pr-4 text-gray-500">{p.lote ?? '—'}</td>

                                        <td className="py-3 pr-4 tabular-nums text-gray-500">
                                            {p.fecha_colecta?.slice(0, 10) ?? '—'}
                                        </td>

                                        <td className="py-3 pr-4 text-right tabular-nums text-gray-500">
                                            {p.capacidad_pajilla != null ? `${p.capacidad_pajilla} ml` : '—'}
                                        </td>

                                        <td className="py-3 pr-4 text-center">
                                            <Badge label={p.estado} map={estadoBadge} />
                                        </td>

                                        <td className="py-3">
                                            <div className="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition">
                                                {bloqueada ? (
                                                    <span
                                                        className="p-1.5 text-gray-300"
                                                        title="Pajilla dañada o inactiva: ya no puede modificarse"
                                                    >
                                                        <Lock className="w-3.5 h-3.5" />
                                                    </span>
                                                ) : (
                                                    <button
                                                        type="button"
                                                        onClick={() => handleEdit(p)}
                                                        className="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition"
                                                        title="Editar"
                                                    >
                                                        <Pencil className="w-3.5 h-3.5" />
                                                    </button>
                                                )}
                                                <button
                                                    type="button"
                                                    onClick={() => setDeletePajilla(p)}
                                                    className="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition"
                                                    title="Eliminar"
                                                >
                                                    <Trash2 className="w-3.5 h-3.5" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            )}

            <Pagination meta={meta} />

            <ModalPajilla
                isOpen={editOpen}
                onClose={() => {
                    setEditOpen(false);
                    setEditPajilla(null);
                }}
                pajilla={editPajilla}
                termos={termos}
                animales={animales}
                donadoresExternos={donadoresExternos}
            />

            <ConfirmDeleteModal
                open={!!deletePajilla}
                onClose={() => setDeletePajilla(null)}
                onConfirm={handleDelete}
                label={deletePajilla?.codigo}
            />
        </>
    );
}