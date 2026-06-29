import { Fragment,useState } from 'react';
import { router } from '@inertiajs/react';
import { Pencil, Trash2, ChevronLeft, ChevronRight, ChevronDown,
    ChevronUp } from 'lucide-react';
import ModalTermo from './Modaltermo';

const estadoBadge = {
    activo:        'bg-emerald-100 text-emerald-700 border border-emerald-200',
    inactivo:      'bg-gray-100 text-gray-500 border border-gray-200',
    mantenimiento: 'bg-amber-100 text-amber-700 border border-amber-200',
};

const estadoPajillaBadge = {
    disponible: 'bg-emerald-100 text-emerald-700 border border-emerald-200',
    utilizada: 'bg-blue-100 text-blue-700 border border-blue-200',
    dañada: 'bg-red-100 text-red-600 border border-red-200',
    vencida: 'bg-gray-100 text-gray-500 border border-gray-200',
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
                <h3 className="text-base font-semibold text-gray-800 mb-2">Eliminar termo</h3>
                <p className="text-sm text-gray-600 mb-6">
                    ¿Estás seguro de eliminar <strong>{label}</strong>? Esta acción no se puede deshacer y eliminará todas las pajillas asociadas.
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
                    onClick={() => router.get(route('termos.index'), { page: meta.current_page - 1 }, { preserveState: true })}
                    className="p-1.5 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition"
                >
                    <ChevronLeft className="w-4 h-4" />
                </button>
                {Array.from({ length: meta.last_page }, (_, i) => i + 1)
                    .filter((p) => Math.abs(p - meta.current_page) <= 2)
                    .map((p) => (
                        <button
                            key={p}
                            onClick={() => router.get(route('termos.index'), { page: p }, { preserveState: true })}
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
                    onClick={() => router.get(route('termos.index'), { page: meta.current_page + 1 }, { preserveState: true })}
                    className="p-1.5 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition"
                >
                    <ChevronRight className="w-4 h-4" />
                </button>
            </div>
        </div>
    );
}

export default function TabTermos({ termos }) {
    const [editTermo, setEditTermo]   = useState(null);
    const [editOpen, setEditOpen]     = useState(false);
    const [deleteTermo, setDeleteTermo] = useState(null);
    const [termoExpandido, setTermoExpandido] = useState(null);

    const items = termos?.data ?? [];
    const meta  = termos?.meta  ?? termos;

    function handleEdit(t) {
        setEditTermo(t);
        setEditOpen(true);
    }

    function handleDelete() {
        router.delete(route('termos.destroy', deleteTermo.id), {
            onSuccess: () => setDeleteTermo(null),
            preserveScroll: true,
        });
    }

    if (items.length === 0) {
        return (
            <div className="flex flex-col items-center justify-center py-16 text-center">
                <div className="w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center mb-4">
                    <span className="text-2xl">🧪</span>
                </div>
                <p className="text-gray-500 font-medium">Sin termos registrados</p>
                <p className="text-sm text-gray-400 mt-1">Agrega tu primer termo con el botón de arriba.</p>
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
                            <th className="text-left pb-3 text-xs font-semibold text-gray-400 uppercase tracking-wide">Nombre</th>
                            <th className="text-left pb-3 text-xs font-semibold text-gray-400 uppercase tracking-wide">Ubicación</th>
                            <th className="text-center pb-3 text-xs font-semibold text-gray-400 uppercase tracking-wide">Pajillas</th>
                            <th className="text-center pb-3 text-xs font-semibold text-gray-400 uppercase tracking-wide">Disponibles</th>
                            <th className="text-center pb-3 text-xs font-semibold text-gray-400 uppercase tracking-wide">Estado</th>
                            <th className="pb-3" />
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-50">
    {items.map((t) => {
        const estaExpandido = termoExpandido === t.id;
        const pajillasTermo = t.pajillas ?? [];

        return (
            <Fragment key={t.id}>
                {/* Fila principal del termo */}
                <tr className="hover:bg-gray-50/60 transition group">
                    <td className="py-3 pr-4 font-mono text-xs text-gray-600">
                        {t.codigo}
                    </td>

                    <td className="py-3 pr-4 text-gray-800 font-medium">
                        {t.nombre ?? '—'}
                    </td>

                    <td className="py-3 pr-4 text-gray-500">
                        {t.ubicacion ?? '—'}
                    </td>

                    <td className="py-3 pr-4 text-center tabular-nums text-gray-700">
                        {t.pajillas_count ?? pajillasTermo.length}
                    </td>

                    <td className="py-3 pr-4 text-center tabular-nums text-emerald-600 font-semibold">
                        {t.pajillas_disponibles_count ?? 0}
                    </td>

                    <td className="py-3 pr-4 text-center">
                        <Badge label={t.estado} map={estadoBadge} />
                    </td>

                    <td className="py-3">
                        <div className="flex items-center justify-end gap-1">
                            <button
                                type="button"
                                onClick={() =>
                                    setTermoExpandido(
                                        estaExpandido ? null : t.id
                                    )
                                }
                                className="p-1.5 rounded-lg text-gray-400 hover:text-cyan-600 hover:bg-cyan-50 transition"
                                title={
                                    estaExpandido
                                        ? 'Ocultar pajillas'
                                        : 'Mostrar pajillas'
                                }
                            >
                                {estaExpandido ? (
                                    <ChevronUp className="w-4 h-4" />
                                ) : (
                                    <ChevronDown className="w-4 h-4" />
                                )}
                            </button>

                            <button
                                type="button"
                                onClick={() => handleEdit(t)}
                                className="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition"
                                title="Editar"
                            >
                                <Pencil className="w-3.5 h-3.5" />
                            </button>

                            <button
                                type="button"
                                onClick={() => setDeleteTermo(t)}
                                className="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition"
                                title="Eliminar"
                            >
                                <Trash2 className="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </td>
                </tr>

                {/* Fila desplegable con las pajillas */}
                {estaExpandido && (
                    <tr>
                        <td colSpan={7} className="bg-gray-50 px-5 py-4">
                            <div className="overflow-hidden rounded-xl border border-gray-200 bg-white">
                                <div className="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                                    <div>
                                        <h4 className="text-sm font-semibold text-gray-700">
                                            Pajillas del termo {t.codigo}
                                        </h4>

                                        <p className="mt-0.5 text-xs text-gray-400">
                                            {pajillasTermo.length} pajilla(s)
                                            registrada(s)
                                        </p>
                                    </div>
                                </div>

                                {pajillasTermo.length === 0 ? (
                                    <div className="px-4 py-8 text-center text-sm text-gray-400">
                                        Este termo no tiene pajillas registradas.
                                    </div>
                                ) : (
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-sm">
                                            <thead className="bg-gray-50">
                                                <tr>
                                                    <th className="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                                                        Código
                                                    </th>

                                                    <th className="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                                                        Donador
                                                    </th>

                                                    <th className="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                                                        Lote
                                                    </th>

                                                    <th className="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                                                        Vencimiento
                                                    </th>

                                                    <th className="px-4 py-2 text-center text-xs font-semibold uppercase tracking-wide text-gray-400">
                                                        Estado
                                                    </th>
                                                </tr>
                                            </thead>

                                            <tbody className="divide-y divide-gray-100">
                                                {pajillasTermo.map((pajilla) => (
                                                    <tr
                                                        key={pajilla.id}
                                                        className="hover:bg-gray-50"
                                                    >
                                                        <td className="px-4 py-3 font-mono text-xs text-gray-600">
                                                            {pajilla.codigo}
                                                        </td>

                                                        <td className="px-4 py-3 text-gray-700">
                                                            {pajilla.animal ? (
                                                                <div>
                                                                    <p className="font-medium">
                                                                        {pajilla
                                                                            .animal
                                                                            .nombre ??
                                                                            pajilla
                                                                                .animal
                                                                                .arete ??
                                                                            `Animal #${pajilla.animal.id}`}
                                                                    </p>

                                                                    <p className="text-xs text-gray-400">
                                                                        Interno
                                                                        {pajilla
                                                                            .animal
                                                                            .arete
                                                                            ? ` · Arete: ${pajilla.animal.arete}`
                                                                            : ''}
                                                                    </p>
                                                                </div>
                                                            ) : pajilla.donador_externo ? (
                                                                <div>
                                                                    <p className="font-medium">
                                                                        {
                                                                            pajilla
                                                                                .donador_externo
                                                                                .nombre
                                                                        }
                                                                    </p>

                                                                    <p className="text-xs text-gray-400">
                                                                        Externo
                                                                        {pajilla
                                                                            .donador_externo
                                                                            .codigo
                                                                            ? ` · Código: ${pajilla.donador_externo.codigo}`
                                                                            : ''}
                                                                    </p>
                                                                </div>
                                                            ) : (
                                                                '—'
                                                            )}
                                                        </td>

                                                        <td className="px-4 py-3 text-gray-500">
                                                            {pajilla.lote ?? '—'}
                                                        </td>

                                                        <td className="px-4 py-3 tabular-nums text-gray-500">
                                                            {pajilla.fecha_vencimiento
                                                                ?.slice(0, 10) ??
                                                                '—'}
                                                        </td>

                                                        <td className="px-4 py-3 text-center">
                                                            <Badge
                                                                label={
                                                                    pajilla.estado
                                                                }
                                                                map={
                                                                    estadoPajillaBadge
                                                                }
                                                            />
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </div>
                        </td>
                    </tr>
                )}
            </Fragment>
        );
    })}
</tbody>
                </table>
            </div>

            <Pagination meta={meta} />

            <ModalTermo
                isOpen={editOpen}
                onClose={() => { setEditOpen(false); setEditTermo(null); }}
                termo={editTermo}
            />

            <ConfirmDeleteModal
                open={!!deleteTermo}
                onClose={() => setDeleteTermo(null)}
                onConfirm={handleDelete}
                label={deleteTermo?.codigo}
            />
        </>
    );
}