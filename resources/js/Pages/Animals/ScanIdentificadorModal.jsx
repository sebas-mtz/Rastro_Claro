import React, { useState, useEffect } from 'react';
import { X, ScanLine, Search, AlertTriangle, CheckCircle2, Usb, Info } from 'lucide-react';
import { router, useForm } from '@inertiajs/react';
import axios from 'axios';
import {
    useKeyboardWedgeReader,
    useSerialTagReader,
    normalizarCodigo,
    leerCodigoIso,
} from '@/Services/identifierReader';

/**
 * Muestra qué se leyó del código electrónico: si cumple la norma, a qué país
 * corresponde y cómo se separa. Sirve para detectar de inmediato un arete de
 * otro origen o una lectura incompleta.
 */
function LecturaIso({ codigo }) {
    const iso = leerCodigoIso(codigo);

    if (!codigo) return null;

    if (!iso) {
        return (
            <p className="text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-lg p-2">
                No tiene la forma de un código electrónico ISO 11784 (15 dígitos).
                Puede ser un arete visual, un alias o una lectura incompleta.
            </p>
        );
    }

    return (
        <div className={
            'text-xs rounded-lg p-2 border ' +
            (iso.esMexico
                ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
                : 'bg-amber-50 border-amber-200 text-amber-800')
        }>
            <span className="font-mono font-semibold">{iso.formateado}</span>
            <span className="ml-2">· {iso.origen}</span>
            {!iso.esMexico && (
                <p className="mt-1">
                    Este arete no proviene del padrón nacional. Se puede guardar igual.
                </p>
            )}
        </div>
    );
}

export default function ScanIdentificadorModal({ show, onClose, animales = [], modoDirecto = false, animalPreseleccionado = null }) {
    const [buscando, setBuscando] = useState(false);
    const [resultado, setResultado] = useState(null); // { encontrado: bool, animal? }
    const [errorBusqueda, setErrorBusqueda] = useState('');
    const [codigoBuscado, setCodigoBuscado] = useState('');
    const [mostrarRegistro, setMostrarRegistro] = useState(modoDirecto);

    useEffect(() => {
        if (show) {
            setMostrarRegistro(modoDirecto);
            if (modoDirecto && animalPreseleccionado) {
                registroForm.setData('animal_id', String(animalPreseleccionado));
            }
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [show, modoDirecto, animalPreseleccionado]);

    const { inputRef, handleKeyDown } = useKeyboardWedgeReader({
        activo: show && !mostrarRegistro,
        onScan: (codigo) => buscar(codigo),
    });

    // Lector conectado por puerto serie, para los equipos que no funcionan
    // como teclado. Solo aparece si el navegador lo permite.
    const serial = useSerialTagReader({ onScan: (codigo) => buscar(codigo) });

    const registroForm = useForm({
        animal_id: animalPreseleccionado ? String(animalPreseleccionado) : '',
        tipo_identificador: 'rfid',
        microchip_codigo: '',
        siniiga_numero: '',
        tecnologia_rfid: '',
        fecha_colocacion_microchip: new Date().toISOString().split('T')[0],
        estado_microchip: 'activo',
        observaciones_microchip: '',
    });

    const buscar = async (valorCrudo) => {
        const codigo = normalizarCodigo(valorCrudo);
        setCodigoBuscado(codigo);
        setResultado(null);
        setErrorBusqueda('');

        if (!codigo) {
            setErrorBusqueda('Ingresa o escanea un identificador.');
            return;
        }

        setBuscando(true);
        try {
            const { data } = await axios.get(route('animales.buscar-identificador'), {
                params: { codigo },
            });
            setResultado(data);

            if (data.encontrado) {
                // Ruta hardcodeada a propósito: el nombre 'animales.show' es ambiguo
                // (routes/api.php registra otra ruta con el mismo nombre).
                router.visit('/animales/' + data.animal.id);
            }
        } catch (e) {
            setErrorBusqueda('Ocurrió un error al buscar. Intenta de nuevo.');
        } finally {
            setBuscando(false);
        }
    };

    const abrirRegistro = () => {
        registroForm.setData('microchip_codigo', codigoBuscado);
        setMostrarRegistro(true);
    };

    const handleClose = () => {
        setResultado(null);
        setErrorBusqueda('');
        setCodigoBuscado('');
        setMostrarRegistro(false);
        registroForm.reset();
        onClose();
    };

    const handleSubmitRegistro = (e) => {
        e.preventDefault();
        if (!registroForm.data.animal_id) {
            alert('Selecciona el animal al que se le asignará este identificador.');
            return;
        }

        registroForm.post(route('animales.identificador.store', registroForm.data.animal_id), {
            onSuccess: () => handleClose(),
        });
    };

    if (!show) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div className="bg-white rounded-lg max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div className="flex justify-between items-center border-b p-6">
                    <div className="flex items-center gap-3">
                        <ScanLine className="w-6 h-6 text-blue-600" />
                        <h2 className="text-xl font-bold text-gray-800">Escanear identificador</h2>
                    </div>
                    <button onClick={handleClose} className="text-gray-400 hover:text-gray-600">
                        <X className="w-6 h-6" />
                    </button>
                </div>

                {!mostrarRegistro ? (
                    <div className="p-6 space-y-4">
                        <p className="text-sm text-gray-600">
                            Coloca el cursor en el campo y pasa el lector por el arete, o escribe
                            el número manualmente y presiona Enter. Busca por arete interno, número
                            SINIIGA, código electrónico, alias o QR.
                        </p>

                        {/* Lector por puerto serie: solo para equipos que no
                            se comportan como teclado. */}
                        {serial.soportado && (
                            <div className="flex flex-wrap items-center gap-2 text-sm">
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
                                    {serial.conectado ? 'Lector conectado — desconectar' : 'Conectar lector por cable'}
                                </button>
                                {serial.conectado && (
                                    <span className="text-xs text-emerald-700">
                                        Esperando lectura…
                                    </span>
                                )}
                            </div>
                        )}

                        {serial.error && (
                            <div className="bg-amber-50 border border-amber-200 text-amber-800 text-xs rounded-lg p-2">
                                {serial.error}
                            </div>
                        )}

                        <div className="flex items-center gap-2">
                            <input
                                ref={inputRef}
                                type="text"
                                autoFocus
                                onKeyDown={handleKeyDown}
                                placeholder="Escanea o escribe el identificador..."
                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                                disabled={buscando}
                            />
                            <button
                                type="button"
                                onClick={() => buscar(inputRef.current?.value || '')}
                                className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 disabled:opacity-50"
                                disabled={buscando}
                            >
                                <Search className="w-4 h-4" />
                                {buscando ? 'Buscando...' : 'Buscar'}
                            </button>
                        </div>

                        {errorBusqueda && (
                            <div className="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 flex items-center gap-2">
                                <AlertTriangle className="w-4 h-4 shrink-0" /> {errorBusqueda}
                            </div>
                        )}

                        {/* Lectura del código en cuanto se escanea, antes
                            incluso de saber si el animal existe. */}
                        {codigoBuscado && <LecturaIso codigo={codigoBuscado} />}

                        <p className="text-xs text-slate-500">
                            ¿El lector no responde o lee mal?{' '}
                            <a
                                href={route('herramientas.diagnostico-lector')}
                                className="text-emerald-700 underline"
                            >
                                Pruébalo aquí
                            </a>{' '}
                            para ver qué está recibiendo el sistema.
                        </p>

                        {resultado && !resultado.encontrado && (
                            <div className="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg p-3 space-y-2">
                                <p className="flex items-center gap-2">
                                    <AlertTriangle className="w-4 h-4 shrink-0" />
                                    No existe ningún animal con el identificador <strong>{codigoBuscado}</strong>.
                                </p>
                                <button
                                    onClick={abrirRegistro}
                                    className="text-sm font-medium text-amber-900 underline"
                                >
                                    Registrar este identificador en un animal existente
                                </button>
                            </div>
                        )}
                    </div>
                ) : (
                    <form onSubmit={handleSubmitRegistro} className="p-6 space-y-4">
                        <div className="bg-blue-50 border border-blue-200 text-blue-800 text-sm rounded-lg p-3 flex items-center gap-2">
                            <CheckCircle2 className="w-4 h-4 shrink-0" />
                            Registrando el código <strong>{registroForm.data.microchip_codigo}</strong>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Animal *</label>
                            <select
                                value={registroForm.data.animal_id}
                                onChange={(e) => registroForm.setData('animal_id', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                required
                                disabled={registroForm.processing}
                            >
                                <option value="">Selecciona un ejemplar...</option>
                                {animales.map((a) => (
                                    <option key={a.id} value={a.id}>{a.alias || a.arete} — {a.arete}</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Tipo de identificador *</label>
                            <select
                                value={registroForm.data.tipo_identificador}
                                onChange={(e) => registroForm.setData('tipo_identificador', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                disabled={registroForm.processing}
                            >
                                <option value="rfid">Arete electrónico (SINIIGA)</option>
                                <option value="microchip">Microchip o bolo ruminal</option>
                                <option value="qr">Código QR</option>
                                <option value="arete">Arete visual sin electrónica</option>
                                <option value="manual">Identificador manual</option>
                            </select>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Código electrónico
                            </label>
                            <input
                                type="text"
                                inputMode="numeric"
                                value={registroForm.data.microchip_codigo}
                                onChange={(e) => registroForm.setData('microchip_codigo', e.target.value)}
                                placeholder="484 000123456789"
                                className={`w-full border rounded-lg px-3 py-2 font-mono ${registroForm.errors.microchip_codigo ? 'border-red-300' : 'border-gray-300'}`}
                                disabled={registroForm.processing}
                            />
                            <div className="mt-1">
                                <LecturaIso codigo={registroForm.data.microchip_codigo} />
                            </div>
                            {registroForm.errors.microchip_codigo && (
                                <p className="mt-1 text-sm text-red-600">{registroForm.errors.microchip_codigo}</p>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Arete visual SINIIGA
                                </label>
                                <input
                                    type="text"
                                    inputMode="numeric"
                                    value={registroForm.data.siniiga_numero}
                                    onChange={(e) => registroForm.setData('siniiga_numero', e.target.value)}
                                    placeholder="Número impreso"
                                    className={`w-full border rounded-lg px-3 py-2 ${registroForm.errors.siniiga_numero ? 'border-red-300' : 'border-gray-300'}`}
                                    disabled={registroForm.processing}
                                />
                                {registroForm.errors.siniiga_numero && (
                                    <p className="mt-1 text-sm text-red-600">{registroForm.errors.siniiga_numero}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Tecnología del arete
                                </label>
                                <select
                                    value={registroForm.data.tecnologia_rfid}
                                    onChange={(e) => registroForm.setData('tecnologia_rfid', e.target.value)}
                                    className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    disabled={registroForm.processing}
                                >
                                    <option value="">Sin especificar</option>
                                    <option value="FDX-B">FDX-B — plena duplicidad</option>
                                    <option value="HDX">HDX — media duplicidad</option>
                                </select>
                            </div>
                        </div>

                        <p className="text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-lg p-3 flex gap-2">
                            <Info className="w-4 h-4 shrink-0 mt-0.5" />
                            <span>
                                HDX y FDX-B son dos formas de transmisión del arete. El lector se
                                encarga de distinguirlas; aquí se anota cuál trae el ejemplar para
                                saber qué equipo hace falta en campo.
                            </span>
                        </p>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Fecha de colocación</label>
                                <input
                                    type="date"
                                    value={registroForm.data.fecha_colocacion_microchip}
                                    onChange={(e) => registroForm.setData('fecha_colocacion_microchip', e.target.value)}
                                    className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    disabled={registroForm.processing}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                                <select
                                    value={registroForm.data.estado_microchip}
                                    onChange={(e) => registroForm.setData('estado_microchip', e.target.value)}
                                    className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                    disabled={registroForm.processing}
                                >
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                    <option value="perdido">Perdido</option>
                                    <option value="dañado">Dañado</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                            <textarea
                                value={registroForm.data.observaciones_microchip}
                                onChange={(e) => registroForm.setData('observaciones_microchip', e.target.value)}
                                rows="2"
                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                disabled={registroForm.processing}
                            />
                        </div>

                        <div className="flex justify-end gap-3 pt-2 border-t">
                            <button
                                type="button"
                                onClick={() => setMostrarRegistro(false)}
                                className="px-6 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50"
                                disabled={registroForm.processing}
                            >
                                Volver
                            </button>
                            <button
                                type="submit"
                                className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                                disabled={registroForm.processing}
                            >
                                {registroForm.processing ? 'Guardando...' : 'Registrar identificador'}
                            </button>
                        </div>
                    </form>
                )}
            </div>
        </div>
    );
}
