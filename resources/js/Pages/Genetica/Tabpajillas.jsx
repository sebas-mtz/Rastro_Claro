import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Pencil, Trash2, ChevronLeft, ChevronRight } from 'lucide-react';
import ModalPajilla from '@Pages/Genetica/Modalpajilla';

const estadoBadge = {
    disponible: 'bg-emerald-100 text-emerald-700 border border-emerald-200',
    utilizada:  'bg-blue-100 text-blue-700 border border-blue-200',
    'dañada':   'bg-red-100 text-red-600 border border-red-200',
    vencida:    'bg-gray-100 text-gray-500 border border-gray-200',
};

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
                    onClick={() => router.get(route('genetica.index'), { page: meta.current_page - 1 }, { preserveState: true })}
                    className="p-1.5 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition"
                >
                    <ChevronLeft className="w-4 h-4" />
                </button>
                {Array.from({ length: meta.last_page }, (_, i) => i + 1)
                    .filter((p) => Math.abs(p - meta.current_page) <= 2)
                    .map((p) => (
                        <button
                            key={p}
                            onClick={() => router.get(route('genetica.index'), { page: p }, { preserveState: true })}
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
                    onClick={() => router.get(route('genetica.index'), { page: meta.current_page + 1 }, { preserveState: true })}
                    className="p-1.5 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition"
                >
                    <ChevronRight className="w-4 h-4" />
                </button>
            </div>
        </div>
    );
}

export default function TabPajillas({ pajillas, termos = [], animales = [],  donadoresExternos = [] }) {
    const [editPajilla, setEditPajilla]   = useState(null);
    const [editOpen, setEditOpen]         = useState(false);
    const [deletePajilla, setDeletePajilla] = useState(null);

    const items = pajillas?.data ?? [];
    const meta  = pajillas?.meta  ?? pajillas;

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

    if (items.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center py-16 text-center">
                <div className="w-14 h-14 rounded-2xl bg-cyan-50 flex items-center justify-center mb-4">
                    <span className="text-2xl">💧</span>
                </div>
                <p className="text-gray-500 font-medium">Sin pajillas registradas</p>
                <p className="text-sm text-gray-400 mt-1">Agrega tu primera pajilla con el botón de arriba.</p>
            </div>
        );
    }

    return (
        <>
            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-gray-100">
                            <th className="text-left pb-3 text-xs font-semibold text-gray-400 uppercase tracking-wide">Código</th>
                            <th className="text-left pb-3 text-xs font-semibold text-gray-400 uppercase tracking-wide">Termo</th>
                            <th className="text-left pb-3 text-xs font-semibold text-gray-400 uppercase tracking-wide">Donador</th>
                            <th className="text-left pb-3 text-xs font-semibold text-gray-400 uppercase tracking-wide">Lote</th>
                            <th className="text-left pb-3 text-xs font-semibold text-gray-400 uppercase tracking-wide">Vencimiento</th>
                            <th className="text-center pb-3 text-xs font-semibold text-gray-400 uppercase tracking-wide">Estado</th>
                            <th className="pb-3" />
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-50">
                        {items.map((p) => (
                            <tr key={p.id} className="hover:bg-gray-50/60 transition group">
                                <td className="py-3 pr-4 font-mono text-xs text-gray-600">{p.codigo}</td>
                                <td className="py-3 pr-4 font-mono text-xs text-gray-500">
    {p.termo?.codigo ?? '—'}
</td>

<td className="py-3 pr-4 text-gray-700">
    {p.animal ? (
        <div>
            <p className="font-medium">
                                    {p.animal.nombre ?? p.animal.arete ?? `Animal #${p.animal.id}`}
                                </p>
                                <p className="text-xs text-gray-400">
                                    Interno
                                    {p.animal.arete ? ` · Arete: ${p.animal.arete}` : ''}
                                </p>
                            </div>
                        ) : p.donador_externo ? (
                            <div>
                                <p className="font-medium">
                                    {p.donador_externo.nombre}
                                </p>
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
                                    {p.fecha_vencimiento?.slice(0, 10) ?? '—'}
                                </td>
                                <td className="py-3 pr-4 text-center">
                                    <Badge label={p.estado} map={estadoBadge} />
                                </td>
                                <td className="py-3">
                                    <div className="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition">
                                        <button
                                            type="button"
                                            onClick={() => handleEdit(p)}
                                            className="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition"
                                            title="Editar"
                                        >
                                            <Pencil className="w-3.5 h-3.5" />
                                        </button>
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
                        ))}
                    </tbody>
                </table>
            </div>

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