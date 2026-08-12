import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import {
    ScanLine, CheckCircle2, XCircle, AlertTriangle, Copy, Trash2,
    Keyboard, Usb, Timer, HelpCircle,
} from 'lucide-react';
import {
    useCapturaCruda,
    useSerialTagReader,
    normalizarCodigo,
    leerCodigoIso,
    clasificarLectura,
    mostrarInvisibles,
} from '@/Services/identifierReader';

/**
 * Diagnóstico del lector de aretes.
 *
 * Existe porque el sistema se instala en ranchos con equipos que nadie del
 * lado del desarrollo va a ver nunca. Cuando alguien dice "no me lee", esta
 * pantalla responde si el problema es el arete, el lector, su configuración o
 * el sistema — sin que haga falta entrar a su computadora.
 *
 * No consulta la base de datos a propósito: analiza únicamente lo que llegó
 * del lector, así que funciona incluso antes de registrar el primer ejemplar.
 */

function Dato({ etiqueta, children, mono = false }) {
    return (
        <div>
            <dt className="text-xs font-medium text-slate-500">{etiqueta}</dt>
            <dd className={'mt-0.5 text-sm text-slate-800 break-all ' + (mono ? 'font-mono' : '')}>
                {children}
            </dd>
        </div>
    );
}

function Lectura({ lectura, onCopiar }) {
    const normalizado = normalizarCodigo(lectura.crudo);
    const iso = leerCodigoIso(normalizado);
    const clase = clasificarLectura(normalizado);
    const digitos = normalizado.replace(/\D+/g, '').length;

    const valido = clase.tipo === 'iso';

    return (
        <div className={
            'rounded-2xl border p-5 space-y-4 ' +
            (valido ? 'border-emerald-200 bg-emerald-50/40' : 'border-slate-200 bg-white')
        }>
            <div className="flex flex-wrap items-center justify-between gap-2">
                <div className="flex items-center gap-2">
                    {valido
                        ? <CheckCircle2 className="w-5 h-5 text-emerald-600" />
                        : <AlertTriangle className="w-5 h-5 text-amber-500" />}
                    <span className="font-semibold text-slate-800">{clase.etiqueta}</span>
                </div>

                <div className="flex items-center gap-3 text-xs text-slate-500">
                    <span className="inline-flex items-center gap-1">
                        <Timer className="w-3.5 h-3.5" /> {lectura.duracionMs} ms
                    </span>
                    <button
                        onClick={() => onCopiar(lectura)}
                        className="inline-flex items-center gap-1 hover:text-slate-800"
                        title="Copiar el detalle para enviarlo a soporte"
                    >
                        <Copy className="w-3.5 h-3.5" /> Copiar
                    </button>
                </div>
            </div>

            <p className="text-sm text-slate-600">{clase.descripcion}</p>

            <dl className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {/* Lo que llegó tal cual, con los caracteres invisibles a la
                    vista: aquí es donde se descubren los prefijos ocultos. */}
                <Dato etiqueta="Texto crudo recibido" mono>
                    {mostrarInvisibles(lectura.crudo) || <span className="text-slate-400">— vacío —</span>}
                </Dato>

                <Dato etiqueta="Código normalizado" mono>
                    {normalizado || <span className="text-slate-400">— vacío —</span>}
                </Dato>

                <Dato etiqueta="Longitud">
                    {normalizado.length} caracteres
                    {digitos !== normalizado.length && ` · ${digitos} dígitos`}
                </Dato>

                <Dato etiqueta="Cierre de la lectura">
                    {lectura.terminador === 'Enter' ? 'Enter' : 'Tabulador'}
                    {lectura.pareceLector
                        ? <span className="text-emerald-700"> · lectura de dispositivo</span>
                        : <span className="text-amber-700"> · parece escrito a mano</span>}
                </Dato>

                {iso && (
                    <>
                        <Dato etiqueta="País o fabricante">
                            {iso.pais} — {iso.origen}
                        </Dato>
                        <Dato etiqueta="Código del animal" mono>{iso.nacional}</Dato>
                    </>
                )}
            </dl>

            <div className={
                'text-sm rounded-lg p-3 flex items-start gap-2 ' +
                (valido
                    ? 'bg-emerald-100 text-emerald-900'
                    : 'bg-amber-100 text-amber-900')
            }>
                {valido
                    ? <CheckCircle2 className="w-4 h-4 mt-0.5 shrink-0" />
                    : <XCircle className="w-4 h-4 mt-0.5 shrink-0" />}
                <span>
                    {valido
                        ? 'El sistema acepta esta lectura como arete electrónico. El lector funciona.'
                        : 'El sistema no la aceptaría como arete electrónico. Puede guardarse igual como '
                          + 'arete visual o identificador manual, pero revisa la tabla de abajo si esperabas un código ISO.'}
                </span>
            </div>
        </div>
    );
}

export default function DiagnosticoLector({ auth, configuracion = null }) {
    const [lecturas, setLecturas] = useState([]);
    const [copiado, setCopiado] = useState(false);

    const hayAjustes = Boolean(
        configuracion?.prefijo_descartar ||
        configuracion?.sufijo_descartar ||
        configuracion?.solo_digitos
    );

    const registrar = (lectura) => {
        setLecturas((previas) => [{ ...lectura, id: Date.now() }, ...previas].slice(0, 10));
    };

    const { inputRef, handleKeyDown } = useCapturaCruda({ onLectura: registrar });

    // El lector por cable alimenta el mismo diagnóstico: llega el texto y se
    // analiza igual, sin tiempos de tecleo porque no los hay.
    const serial = useSerialTagReader({
        onScan: (codigo) => registrar({
            crudo: codigo,
            terminador: 'Enter',
            duracionMs: 0,
            teclas: [],
            pareceLector: true,
        }),
    });

    const copiar = (lectura) => {
        const normalizado = normalizarCodigo(lectura.crudo);
        const clase = clasificarLectura(normalizado);

        const detalle = [
            'Diagnóstico de lector — Rastro Claro',
            `Texto crudo:  ${mostrarInvisibles(lectura.crudo)}`,
            `Normalizado:  ${normalizado}`,
            `Longitud:     ${normalizado.length}`,
            `Tipo:         ${clase.etiqueta}`,
            `Cierre:       ${lectura.terminador}`,
            `Duración:     ${lectura.duracionMs} ms`,
        ].join('\n');

        navigator.clipboard?.writeText(detalle);
        setCopiado(true);
        setTimeout(() => setCopiado(false), 2000);
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800">Diagnóstico del lector</h2>}
        >
            <Head title="Diagnóstico del lector" />

            <div className="py-8 px-4 sm:px-6 max-w-4xl mx-auto space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <ScanLine className="w-6 h-6 text-slate-400" /> Diagnóstico del lector
                    </h1>
                    <p className="text-gray-600 mt-1">
                        Pasa un arete por el lector y aquí verás exactamente qué recibió el sistema.
                        No se guarda nada ni se busca ningún ejemplar: solo se analiza la lectura.
                    </p>
                </div>

                {/* Si el rancho configuró recortes, conviene saberlo antes de
                    interpretar cualquier lectura rara. */}
                {hayAjustes && (
                    <p className="text-sm text-blue-800 bg-blue-50 border border-blue-200 rounded-lg p-3">
                        Este rancho tiene ajustes de lector activos
                        {configuracion.prefijo_descartar && <> · descarta «{configuracion.prefijo_descartar}» al inicio</>}
                        {configuracion.sufijo_descartar && <> · descarta «{configuracion.sufijo_descartar}» al final</>}
                        {configuracion.solo_digitos && <> · descarta lo que no sea dígito</>}
                        . Lo que ves abajo es la lectura tal como llega, <strong>antes</strong> de aplicarlos.{' '}
                        <a href={route('herramientas.lector')} className="underline">Ver ajustes</a>
                    </p>
                )}

                {/* Captura */}
                <div className="bg-white rounded-2xl border border-slate-200 p-5 space-y-4">
                    <div className="flex items-center gap-2 text-sm font-medium text-slate-700">
                        <Keyboard className="w-4 h-4" /> Modo teclado
                    </div>

                    <p className="text-sm text-slate-600">
                        Haz clic en el campo y pasa el lector por un arete. Si tu lector está en modo
                        teclado —lo normal— el código aparecerá solo.
                    </p>

                    <input
                        ref={inputRef}
                        type="text"
                        autoFocus
                        onKeyDown={handleKeyDown}
                        placeholder="Pasa el lector por un arete…"
                        className="w-full border-2 border-dashed border-slate-300 rounded-xl px-4 py-6 text-center text-lg font-mono focus:border-emerald-400 focus:outline-none"
                    />

                    {serial.soportado && (
                        <div className="pt-3 border-t border-slate-100 space-y-2">
                            <div className="flex items-center gap-2 text-sm font-medium text-slate-700">
                                <Usb className="w-4 h-4" /> Lector por cable
                            </div>
                            <p className="text-sm text-slate-600">
                                Solo si el lector no funciona en modo teclado.
                            </p>
                            <button
                                type="button"
                                onClick={serial.conectado ? serial.desconectar : serial.conectar}
                                className={
                                    'inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium ' +
                                    (serial.conectado
                                        ? 'bg-emerald-50 border-emerald-300 text-emerald-800'
                                        : 'bg-white border-slate-300 text-slate-700 hover:bg-slate-50')
                                }
                            >
                                <Usb className="w-4 h-4" />
                                {serial.conectado ? 'Conectado — desconectar' : 'Conectar lector por cable'}
                            </button>
                            {serial.error && (
                                <p className="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg p-2">
                                    {serial.error}
                                </p>
                            )}
                        </div>
                    )}
                </div>

                {copiado && (
                    <p className="text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-2">
                        Detalle copiado. Ya puedes pegarlo en un correo o mensaje de soporte.
                    </p>
                )}

                {/* Lecturas */}
                {lecturas.length > 0 && (
                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <h2 className="font-semibold text-slate-800">
                                Últimas lecturas ({lecturas.length})
                            </h2>
                            <button
                                onClick={() => setLecturas([])}
                                className="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1"
                            >
                                <Trash2 className="w-4 h-4" /> Limpiar
                            </button>
                        </div>

                        {lecturas.map((l) => (
                            <Lectura key={l.id} lectura={l} onCopiar={copiar} />
                        ))}
                    </div>
                )}

                {/* Ayuda */}
                <div className="bg-white rounded-2xl border border-slate-200 p-5 space-y-4">
                    <h2 className="font-semibold text-slate-800 flex items-center gap-2">
                        <HelpCircle className="w-4 h-4 text-slate-400" /> Si no lee
                    </h2>

                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead>
                                <tr className="text-left text-xs text-slate-500 uppercase">
                                    <th className="py-2 pr-4">Qué pasa</th>
                                    <th className="py-2">Qué revisar</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                <tr>
                                    <td className="py-3 pr-4 font-medium text-slate-700">No aparece nada</td>
                                    <td className="py-3 text-slate-600">
                                        El lector no está en modo teclado. Consulta su manual: casi todos
                                        traen una tarjeta de configuración o un comando para activarlo.
                                        Prueba también en un bloc de notas: si ahí tampoco escribe, el
                                        problema no es el sistema.
                                    </td>
                                </tr>
                                <tr>
                                    <td className="py-3 pr-4 font-medium text-slate-700">
                                        Aparece pero dice «parece escrito a mano»
                                    </td>
                                    <td className="py-3 text-slate-600">
                                        Llegó demasiado lento para venir de un lector. Suele indicar que
                                        se tecleó, no que se escaneó.
                                    </td>
                                </tr>
                                <tr>
                                    <td className="py-3 pr-4 font-medium text-slate-700">
                                        Salen caracteres de más
                                    </td>
                                    <td className="py-3 text-slate-600">
                                        Los símbolos ␍ ␊ ␉ ␠ marcan saltos de línea, tabuladores y
                                        espacios. El sistema los descarta solo. Si hay letras o símbolos
                                        antes del número, tu lector añade un prefijo: anótalo y avísanos.
                                    </td>
                                </tr>
                                <tr>
                                    <td className="py-3 pr-4 font-medium text-slate-700">
                                        Salen menos de 15 dígitos
                                    </td>
                                    <td className="py-3 text-slate-600">
                                        La lectura quedó incompleta, o el arete es solo visual y no trae
                                        electrónica. Acerca más el lector y repite.
                                    </td>
                                </tr>
                                <tr>
                                    <td className="py-3 pr-4 font-medium text-slate-700">
                                        Unos aretes leen y otros no
                                    </td>
                                    <td className="py-3 text-slate-600">
                                        Probablemente el rebaño mezcla aretes HDX y FDX-B, y el lector
                                        solo soporta una de las dos. Es limitación del equipo, no del
                                        sistema: hace falta un lector que declare ambas.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p className="text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-lg p-3">
                        Al comprar un lector, exige que diga <strong>ISO 11784/11785, HDX y FDX-B</strong>,
                        y que tenga <strong>modo teclado</strong> (HID). Con eso funciona sin configurar nada.
                    </p>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
