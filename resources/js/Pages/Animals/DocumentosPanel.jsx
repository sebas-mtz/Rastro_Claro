import React, { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import { Paperclip, Upload, Download, Trash2, FileText, Image as ImageIcon } from 'lucide-react';

function fmtFecha(f) {
    return f ? new Date(f).toLocaleDateString('es-MX') : '—';
}

/**
 * Documentos y evidencias del ejemplar.
 *
 * Los archivos se guardan en disco privado: la descarga pasa siempre por el
 * servidor, que verifica que el documento pertenezca a la cuenta.
 */
export default function DocumentosPanel({ animal, documentos = [], tiposDocumento = {}, extensiones = [], tamanoMaximoKb = 5120 }) {
    const [abierto, setAbierto] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        documentable_tipo: 'animal',
        documentable_id: animal.id,
        tipo: 'certificado_pureza',
        nombre: '',
        fecha_documento: '',
        observaciones: '',
        archivo: null,
    });

    const enviar = (e) => {
        e.preventDefault();

        post(route('documentos.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => { reset(); setAbierto(false); },
        });
    };

    const eliminar = (id) => {
        if (confirm('¿Eliminar este documento? El archivo se borrará definitivamente.')) {
            router.delete(route('documentos.destroy', id), { preserveScroll: true });
        }
    };

    return (
        <div className="bg-white shadow-xl rounded-2xl p-6 border border-gray-200 space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <h2 className="text-lg font-semibold text-gray-700 flex items-center gap-2">
                    <Paperclip className="w-5 h-5 text-slate-500" />
                    Documentos y evidencias
                </h2>
                <button
                    onClick={() => setAbierto((v) => !v)}
                    className="flex items-center gap-2 px-4 py-2 bg-slate-600 text-white text-sm rounded-lg hover:bg-slate-700 transition"
                >
                    <Upload className="w-4 h-4" /> {abierto ? 'Cancelar' : 'Adjuntar documento'}
                </button>
            </div>

            {abierto && (
                <form onSubmit={enviar} className="border border-slate-200 rounded-xl p-4 space-y-3 bg-slate-50">
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label className="block text-xs font-medium text-slate-600 mb-1">Tipo de documento *</label>
                            <select
                                value={data.tipo}
                                onChange={(e) => setData('tipo', e.target.value)}
                                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                                disabled={processing}
                            >
                                {Object.entries(tiposDocumento).map(([valor, etiqueta]) => (
                                    <option key={valor} value={valor}>{etiqueta}</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-slate-600 mb-1">Fecha del documento</label>
                            <input
                                type="date"
                                value={data.fecha_documento}
                                onChange={(e) => setData('fecha_documento', e.target.value)}
                                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                                disabled={processing}
                            />
                        </div>
                    </div>

                    <div>
                        <label className="block text-xs font-medium text-slate-600 mb-1">
                            Nombre <span className="text-slate-400">(opcional, se usa el del archivo)</span>
                        </label>
                        <input
                            type="text"
                            value={data.nombre}
                            onChange={(e) => setData('nombre', e.target.value)}
                            className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                            disabled={processing}
                        />
                    </div>

                    <div>
                        <label className="block text-xs font-medium text-slate-600 mb-1">Archivo *</label>
                        <input
                            type="file"
                            accept={extensiones.map((e) => `.${e}`).join(',')}
                            onChange={(e) => setData('archivo', e.target.files[0] ?? null)}
                            className={`w-full border rounded-lg px-3 py-2 text-sm ${errors.archivo ? 'border-red-300' : 'border-slate-300'}`}
                            required
                            disabled={processing}
                        />
                        <p className="mt-1 text-xs text-slate-400">
                            Formatos: {extensiones.join(', ').toUpperCase()} · Máximo {Math.round(tamanoMaximoKb / 1024)} MB
                        </p>
                        {errors.archivo && <p className="mt-1 text-xs text-red-600">{errors.archivo}</p>}
                    </div>

                    <div>
                        <label className="block text-xs font-medium text-slate-600 mb-1">Observaciones</label>
                        <textarea
                            value={data.observaciones}
                            onChange={(e) => setData('observaciones', e.target.value)}
                            rows="2"
                            className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                            disabled={processing}
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={processing || !data.archivo}
                        className="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium py-2 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {processing ? 'Subiendo...' : 'Adjuntar'}
                    </button>
                </form>
            )}

            {documentos.length === 0 ? (
                <p className="text-sm text-slate-400">
                    Sin documentos adjuntos. Puedes agregar certificados de pureza, registros de
                    asociación, estudios veterinarios o comprobantes.
                </p>
            ) : (
                <ul className="divide-y divide-slate-100">
                    {documentos.map((doc) => (
                        <li key={doc.id} className="py-3 flex items-start justify-between gap-3">
                            <div className="flex items-start gap-3 min-w-0">
                                {doc.es_imagen
                                    ? <ImageIcon className="w-5 h-5 text-slate-400 shrink-0 mt-0.5" />
                                    : <FileText className="w-5 h-5 text-slate-400 shrink-0 mt-0.5" />}
                                <div className="min-w-0">
                                    <p className="text-sm font-medium text-slate-800 truncate">{doc.nombre}</p>
                                    <p className="text-xs text-slate-500">
                                        {tiposDocumento[doc.tipo] || doc.tipo}
                                        {doc.fecha_documento && ` · ${fmtFecha(doc.fecha_documento)}`}
                                        {doc.tamano_legible && ` · ${doc.tamano_legible}`}
                                    </p>
                                    {doc.observaciones && (
                                        <p className="text-xs text-slate-400 mt-0.5">{doc.observaciones}</p>
                                    )}
                                </div>
                            </div>

                            <div className="flex items-center gap-2 shrink-0">
                                <a
                                    href={route('documentos.download', doc.id)}
                                    className="text-blue-600 hover:text-blue-800 p-1"
                                    title="Descargar"
                                >
                                    <Download className="w-4 h-4" />
                                </a>
                                <button
                                    onClick={() => eliminar(doc.id)}
                                    className="text-red-600 hover:text-red-800 p-1"
                                    title="Eliminar"
                                >
                                    <Trash2 className="w-4 h-4" />
                                </button>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
