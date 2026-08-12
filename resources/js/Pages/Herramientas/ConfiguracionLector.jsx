import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import axios from 'axios';
import {
    Settings, ScanLine, CheckCircle2, AlertTriangle, FlaskConical, Info, ArrowRight,
} from 'lucide-react';

/**
 * Ajustes del lector de aretes del rancho.
 *
 * La mayoría de los clientes nunca entrará aquí: un lector corriente en modo
 * teclado funciona sin tocar nada. Esta pantalla existe para el que tenga uno
 * que añade caracteres al código o usa una velocidad distinta, y evita que eso
 * obligue a modificar el sistema.
 */
export default function ConfiguracionLector({ auth, configuracion, conexiones = {}, baudRates = [] }) {
    const { data, setData, put, processing, errors } = useForm({
        prefijo_descartar: configuracion?.prefijo_descartar ?? '',
        sufijo_descartar: configuracion?.sufijo_descartar ?? '',
        solo_digitos: !!configuracion?.solo_digitos,
        longitud_esperada: configuracion?.longitud_esperada ?? '',
        tipo_conexion: configuracion?.tipo_conexion ?? 'teclado',
        baud_rate: configuracion?.baud_rate ?? 9600,
        modelo_lector: configuracion?.modelo_lector ?? '',
        notas: configuracion?.notas ?? '',
    });

    const [prueba, setPrueba] = useState({ lectura: '', resultado: null, cargando: false });

    const guardar = (e) => {
        e.preventDefault();
        put(route('herramientas.lector.update'), { preserveScroll: true });
    };

    // La prueba usa los valores del formulario, no los guardados: así se ve el
    // efecto de un cambio antes de aplicarlo a todo el rancho.
    const probar = async () => {
        if (!prueba.lectura) return;

        setPrueba((p) => ({ ...p, cargando: true }));

        try {
            const { data: resultado } = await axios.post(route('herramientas.lector.probar'), {
                lectura: prueba.lectura,
                prefijo_descartar: data.prefijo_descartar,
                sufijo_descartar: data.sufijo_descartar,
                solo_digitos: data.solo_digitos,
                longitud_esperada: data.longitud_esperada || 0,
            });
            setPrueba((p) => ({ ...p, resultado, cargando: false }));
        } catch {
            setPrueba((p) => ({ ...p, cargando: false }));
        }
    };

    const serial = data.tipo_conexion !== 'teclado';

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800">Ajustes del lector</h2>}
        >
            <Head title="Ajustes del lector" />

            <div className="py-8 px-4 sm:px-6 max-w-3xl mx-auto space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <Settings className="w-6 h-6 text-slate-400" /> Ajustes del lector
                    </h1>
                    <p className="text-gray-600 mt-1">
                        Adapta el sistema a tu lector sin cambiar nada más.
                    </p>
                </div>

                <p className="text-sm text-slate-600 bg-slate-50 border border-slate-200 rounded-lg p-3 flex items-start gap-2">
                    <Info className="w-4 h-4 mt-0.5 shrink-0 text-slate-400" />
                    <span>
                        Si tu lector funciona bien, no cambies nada. Estos ajustes son para los
                        equipos que añaden caracteres al código o usan una configuración poco común.
                        Antes de tocarlos, pasa un arete por el{' '}
                        <Link href={route('herramientas.diagnostico-lector')} className="text-emerald-700 underline">
                            diagnóstico
                        </Link>{' '}
                        para ver qué está llegando.
                    </span>
                </p>

                <form onSubmit={guardar} className="space-y-6">
                    {/* Limpieza */}
                    <div className="bg-white rounded-2xl border border-slate-200 p-5 space-y-4">
                        <h2 className="font-semibold text-slate-800">Limpieza de la lectura</h2>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">
                                    Descartar al inicio
                                </label>
                                <input
                                    type="text"
                                    value={data.prefijo_descartar}
                                    onChange={(e) => setData('prefijo_descartar', e.target.value)}
                                    placeholder="Ej. LA"
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-mono"
                                />
                                <p className="text-xs text-slate-400 mt-1">
                                    Solo se recorta si la lectura empieza así.
                                </p>
                                {errors.prefijo_descartar && (
                                    <p className="text-xs text-red-600 mt-1">{errors.prefijo_descartar}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">
                                    Descartar al final
                                </label>
                                <input
                                    type="text"
                                    value={data.sufijo_descartar}
                                    onChange={(e) => setData('sufijo_descartar', e.target.value)}
                                    placeholder="Ej. #"
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-mono"
                                />
                                <p className="text-xs text-slate-400 mt-1">
                                    Los espacios y saltos de línea ya se descartan solos.
                                </p>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">
                                    Longitud esperada
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    max="64"
                                    value={data.longitud_esperada}
                                    onChange={(e) => setData('longitud_esperada', e.target.value)}
                                    placeholder="15"
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                                />
                                <p className="text-xs text-slate-400 mt-1">
                                    Vacío = 15, la de la norma ISO 11784.
                                </p>
                            </div>

                            <div className="flex items-start pt-6">
                                <label className="flex items-start gap-2 text-sm text-slate-700">
                                    <input
                                        type="checkbox"
                                        checked={data.solo_digitos}
                                        onChange={(e) => setData('solo_digitos', e.target.checked)}
                                        className="mt-1"
                                    />
                                    <span>
                                        Descartar todo lo que no sea dígito
                                        <span className="block text-xs text-amber-700 mt-0.5">
                                            Cuidado: perderías los aretes internos y alias con letras.
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {/* Prueba */}
                    <div className="bg-white rounded-2xl border border-slate-200 p-5 space-y-4">
                        <h2 className="font-semibold text-slate-800 flex items-center gap-2">
                            <FlaskConical className="w-4 h-4 text-slate-400" /> Probar antes de guardar
                        </h2>
                        <p className="text-sm text-slate-600">
                            Escribe o escanea una lectura y comprueba cómo quedaría con los valores
                            de arriba. No se guarda nada.
                        </p>

                        <div className="flex flex-wrap gap-2">
                            <input
                                type="text"
                                value={prueba.lectura}
                                onChange={(e) => setPrueba((p) => ({ ...p, lectura: e.target.value }))}
                                onKeyDown={(e) => { if (e.key === 'Enter') { e.preventDefault(); probar(); } }}
                                placeholder="Ej. LA484000123456789#"
                                className="flex-1 min-w-[16rem] border border-slate-300 rounded-lg px-3 py-2 text-sm font-mono"
                            />
                            <button
                                type="button"
                                onClick={probar}
                                disabled={prueba.cargando || !prueba.lectura}
                                className="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium disabled:opacity-50"
                            >
                                {prueba.cargando ? 'Probando…' : 'Probar'}
                            </button>
                        </div>

                        {prueba.resultado && (
                            <div className={
                                'rounded-lg border p-4 space-y-2 text-sm ' +
                                (prueba.resultado.coincide
                                    ? 'bg-emerald-50 border-emerald-200'
                                    : 'bg-amber-50 border-amber-200')
                            }>
                                <div className="flex items-center gap-2 font-mono">
                                    <span className="text-slate-500">{prueba.resultado.crudo}</span>
                                    <ArrowRight className="w-4 h-4 text-slate-400" />
                                    <span className="font-semibold text-slate-800">
                                        {prueba.resultado.normalizado || '— vacío —'}
                                    </span>
                                </div>

                                {prueba.resultado.pasos?.length > 0 && (
                                    <ul className="text-xs text-slate-600 list-disc list-inside">
                                        {prueba.resultado.pasos.map((paso, i) => <li key={i}>{paso}</li>)}
                                    </ul>
                                )}

                                <p className="flex items-center gap-2">
                                    {prueba.resultado.coincide
                                        ? <CheckCircle2 className="w-4 h-4 text-emerald-600" />
                                        : <AlertTriangle className="w-4 h-4 text-amber-600" />}
                                    <span>
                                        {prueba.resultado.longitud} de {prueba.resultado.longitud_esperada}{' '}
                                        caracteres esperados
                                        {prueba.resultado.iso && ` · ${prueba.resultado.iso.origen}`}
                                    </span>
                                </p>
                            </div>
                        )}
                    </div>

                    {/* Conexión */}
                    <div className="bg-white rounded-2xl border border-slate-200 p-5 space-y-4">
                        <h2 className="font-semibold text-slate-800 flex items-center gap-2">
                            <ScanLine className="w-4 h-4 text-slate-400" /> Conexión
                        </h2>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">
                                    Cómo se conecta
                                </label>
                                <select
                                    value={data.tipo_conexion}
                                    onChange={(e) => setData('tipo_conexion', e.target.value)}
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                                >
                                    {Object.entries(conexiones).map(([valor, etiqueta]) => (
                                        <option key={valor} value={valor}>{etiqueta}</option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">
                                    Velocidad del puerto
                                </label>
                                <select
                                    value={data.baud_rate}
                                    onChange={(e) => setData('baud_rate', Number(e.target.value))}
                                    disabled={!serial}
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm disabled:bg-slate-100 disabled:text-slate-500"
                                >
                                    {baudRates.map((b) => <option key={b} value={b}>{b} baudios</option>)}
                                </select>
                                <p className="text-xs text-slate-400 mt-1">
                                    {serial
                                        ? 'Debe coincidir con la del lector. 9600 es lo habitual.'
                                        : 'Solo aplica a las conexiones por puerto serie.'}
                                </p>
                            </div>
                        </div>

                        {data.tipo_conexion === 'bluetooth' && (
                            <p className="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg p-3">
                                El lector debe estar emparejado en el sistema operativo y aparecer como
                                puerto serie. Los que solo hablan Bluetooth de bajo consumo (BLE) con un
                                protocolo propio necesitan trabajo específico; repórtalo con el modelo.
                            </p>
                        )}
                    </div>

                    {/* Equipo */}
                    <div className="bg-white rounded-2xl border border-slate-200 p-5 space-y-4">
                        <h2 className="font-semibold text-slate-800">Datos del equipo</h2>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">
                                Marca y modelo
                            </label>
                            <input
                                type="text"
                                value={data.modelo_lector}
                                onChange={(e) => setData('modelo_lector', e.target.value)}
                                placeholder="El que aparezca en la etiqueta del lector"
                                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                            />
                            <p className="text-xs text-slate-400 mt-1">
                                Sirve para dar soporte sin tener que preguntarlo.
                            </p>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">
                                Notas
                            </label>
                            <textarea
                                rows="3"
                                value={data.notas}
                                onChange={(e) => setData('notas', e.target.value)}
                                placeholder="Cualquier cosa que convenga recordar sobre este lector."
                                className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
                            />
                        </div>
                    </div>

                    <div className="flex justify-end gap-3">
                        <Link
                            href={route('herramientas.diagnostico-lector')}
                            className="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm"
                        >
                            Ir al diagnóstico
                        </Link>
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-6 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium disabled:opacity-60"
                        >
                            {processing ? 'Guardando…' : 'Guardar ajustes'}
                        </button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
