// resources/js/Pages/Genetica/ModalDonadorExterno.jsx

import { useEffect } from "react";
import { useForm } from "@inertiajs/react";
import { X, UserPlus } from "lucide-react";

export default function ModalDonadorExterno({
    isOpen,
    onClose,
}) {
    const {
        data,
        setData,
        post,
        processing,
        errors,
        reset,
        clearErrors,
    } = useForm({
        codigo: "",
        nombre: "",
        raza: "",
        proveedor: "",
        registro_genealogico: "",
        pais_origen: "",
        observaciones: "",
    });

    useEffect(() => {
        if (!isOpen) return;

        clearErrors();

        const cerrarConEscape = (event) => {
            if (event.key === "Escape") {
                cerrarModal();
            }
        };

        document.addEventListener("keydown", cerrarConEscape);

        return () => {
            document.removeEventListener("keydown", cerrarConEscape);
        };
    }, [isOpen]);

    function cerrarModal() {
        reset();
        clearErrors();
        onClose();
    }

    function handleSubmit(event) {
        event.preventDefault();

        post(route("donadores-externos.store"), {
            preserveScroll: true,
            onSuccess: () => {
                cerrarModal();
            },
        });
    }

    const inputClass = (error) =>
        `w-full rounded-lg border px-3 py-2 text-sm text-gray-800 outline-none transition
        focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20
        ${
            error
                ? "border-red-300 bg-red-50/40"
                : "border-gray-200 hover:border-gray-300"
        }`;

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div
                className="absolute inset-0 bg-black/50 backdrop-blur-sm"
                onClick={cerrarModal}
            />

            <div className="relative flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                {/* Encabezado */}
                <div className="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50">
                            <UserPlus className="h-5 w-5 text-blue-600" />
                        </div>

                        <div>
                            <h2 className="text-base font-semibold text-gray-800">
                                Registrar donador externo
                            </h2>

                            <p className="text-xs text-gray-500">
                                Agrega un semental que no pertenece al inventario interno.
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        onClick={cerrarModal}
                        className="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                {/* Formulario */}
                <form
                    onSubmit={handleSubmit}
                    className="flex-1 overflow-y-auto px-6 py-5"
                >
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        {/* Código */}
                        <div>
                            <label className="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Código *
                            </label>

                            <input
                                type="text"
                                value={data.codigo}
                                onChange={(event) =>
                                    setData("codigo", event.target.value)
                                }
                                placeholder="Ej. DON-001"
                                className={inputClass(errors.codigo)}
                                maxLength={100}
                            />

                            {errors.codigo && (
                                <p className="mt-1 text-xs text-red-500">
                                    {errors.codigo}
                                </p>
                            )}
                        </div>

                        {/* Nombre */}
                        <div>
                            <label className="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Nombre *
                            </label>

                            <input
                                type="text"
                                value={data.nombre}
                                onChange={(event) =>
                                    setData("nombre", event.target.value)
                                }
                                placeholder="Nombre del donador"
                                className={inputClass(errors.nombre)}
                                maxLength={150}
                            />

                            {errors.nombre && (
                                <p className="mt-1 text-xs text-red-500">
                                    {errors.nombre}
                                </p>
                            )}
                        </div>

                        {/* Raza */}
                        <div>
                            <label className="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Raza
                            </label>

                            <input
                                type="text"
                                value={data.raza}
                                onChange={(event) =>
                                    setData("raza", event.target.value)
                                }
                                placeholder="Ej. Angus"
                                className={inputClass(errors.raza)}
                                maxLength={100}
                            />

                            {errors.raza && (
                                <p className="mt-1 text-xs text-red-500">
                                    {errors.raza}
                                </p>
                            )}
                        </div>

                        {/* Proveedor */}
                        <div>
                            <label className="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Proveedor
                            </label>

                            <input
                                type="text"
                                value={data.proveedor}
                                onChange={(event) =>
                                    setData("proveedor", event.target.value)
                                }
                                placeholder="Nombre del proveedor"
                                className={inputClass(errors.proveedor)}
                                maxLength={150}
                            />

                            {errors.proveedor && (
                                <p className="mt-1 text-xs text-red-500">
                                    {errors.proveedor}
                                </p>
                            )}
                        </div>

                        {/* Registro genealógico */}
                        <div>
                            <label className="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Registro genealógico
                            </label>

                            <input
                                type="text"
                                value={data.registro_genealogico}
                                onChange={(event) =>
                                    setData(
                                        "registro_genealogico",
                                        event.target.value
                                    )
                                }
                                placeholder="Número o código de registro"
                                className={inputClass(
                                    errors.registro_genealogico
                                )}
                                maxLength={150}
                            />

                            {errors.registro_genealogico && (
                                <p className="mt-1 text-xs text-red-500">
                                    {errors.registro_genealogico}
                                </p>
                            )}
                        </div>

                        {/* País de origen */}
                        <div>
                            <label className="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                País de origen
                            </label>

                            <input
                                type="text"
                                value={data.pais_origen}
                                onChange={(event) =>
                                    setData(
                                        "pais_origen",
                                        event.target.value
                                    )
                                }
                                placeholder="Ej. México"
                                className={inputClass(errors.pais_origen)}
                                maxLength={100}
                            />

                            {errors.pais_origen && (
                                <p className="mt-1 text-xs text-red-500">
                                    {errors.pais_origen}
                                </p>
                            )}
                        </div>

                        {/* Observaciones */}
                        <div className="md:col-span-2">
                            <label className="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Observaciones
                            </label>

                            <textarea
                                rows={4}
                                value={data.observaciones}
                                onChange={(event) =>
                                    setData(
                                        "observaciones",
                                        event.target.value
                                    )
                                }
                                placeholder="Información adicional sobre el donador..."
                                className={`${inputClass(
                                    errors.observaciones
                                )} resize-none`}
                                maxLength={1000}
                            />

                            <div className="mt-1 flex items-center justify-between">
                                {errors.observaciones ? (
                                    <p className="text-xs text-red-500">
                                        {errors.observaciones}
                                    </p>
                                ) : (
                                    <span />
                                )}

                                <span className="text-xs text-gray-400">
                                    {data.observaciones.length}/1000
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Acciones */}
                    <div className="mt-6 flex justify-end gap-2 border-t border-gray-100 pt-4">
                        <button
                            type="button"
                            onClick={cerrarModal}
                            disabled={processing}
                            className="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50 disabled:opacity-50"
                        >
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {processing
                                ? "Guardando..."
                                : "Guardar donador"}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}