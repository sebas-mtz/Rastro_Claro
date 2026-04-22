import React from "react";
import { X, ClipboardList } from "lucide-react";

export default function HistorialPesajesModal({
    open,
    animal,
    onClose,
    handleDelete,
    round2,
    badgeGanancia,
}) {
    if (!open || !animal) return null;

    const pesajesOrdenados = [...(animal.pesajes || [])].sort(
        (a, b) => new Date(b.fecha) - new Date(a.fecha)
    );

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div className="w-full max-w-4xl rounded-2xl bg-white shadow-2xl border border-gray-100 overflow-hidden">
                {/* Header */}
                <div className="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <ClipboardList size={18} />
                        </div>

                        <div>
                            <h3 className="text-lg font-semibold text-gray-800">
                                Historial de pesajes
                            </h3>
                            <p className="text-sm text-gray-500">
                                Animal: <strong>{animal.arete}</strong>
                                {animal.alias ? ` — ${animal.alias}` : ""}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-lg p-2 text-gray-500 hover:bg-gray-100 transition"
                    >
                        <X size={20} />
                    </button>
                </div>

                {/* Body */}
                <div className="max-h-[70vh] overflow-y-auto p-6">
                    {pesajesOrdenados.length > 0 ? (
                        <div className="space-y-3">
                            {pesajesOrdenados.map((pesaje, idx, arr) => {
                                const siguiente = arr[idx + 1];
                                const delta = siguiente
                                    ? round2(pesaje.peso - siguiente.peso)
                                    : null;

                                return (
                                    <div
                                        key={pesaje.id}
                                        className="rounded-xl border border-gray-200 bg-gray-50 p-4"
                                    >
                                        <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                                            <div>
                                                <p className="text-xs text-gray-500">Fecha</p>
                                                <p className="text-sm font-medium text-gray-800">
                                                    {pesaje.fecha}
                                                </p>
                                            </div>

                                            <div>
                                                <p className="text-xs text-gray-500">Peso</p>
                                                <p className="text-sm font-semibold text-gray-800">
                                                    {pesaje.peso} kg
                                                </p>
                                            </div>

                                            <div>
                                                <p className="text-xs text-gray-500">
                                                    Δ vs anterior
                                                </p>
                                                <div className="mt-1">
                                                    {delta != null ? (
                                                        <span
                                                            className={`rounded-full border px-2 py-0.5 text-[11px] ${badgeGanancia(
                                                                delta
                                                            )}`}
                                                        >
                                                            {delta >= 0 ? "+" : ""}
                                                            {delta} kg
                                                        </span>
                                                    ) : (
                                                        <span className="text-sm text-gray-300">
                                                            —
                                                        </span>
                                                    )}
                                                </div>
                                            </div>

                                            <div>
                                                <p className="text-xs text-gray-500">Notas</p>
                                                <p className="text-sm text-gray-600">
                                                    {pesaje.notas ?? "—"}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="mt-4 flex justify-end">
                                            <button
                                                type="button"
                                                onClick={() => handleDelete(pesaje)}
                                                className="rounded-lg border border-red-200 px-4 py-2 text-xs text-red-600 hover:bg-red-50 transition"
                                            >
                                                Eliminar
                                            </button>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    ) : (
                        <div className="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-8 text-center text-sm text-gray-400">
                            Sin pesajes registrados para este animal.
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}