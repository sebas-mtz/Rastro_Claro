import React, { useEffect, useRef, useState } from 'react';
import { X, QrCode, CameraOff, Search, RotateCcw } from 'lucide-react';
import { router } from '@inertiajs/react';
import axios from 'axios';
import { normalizarCodigo } from '@/Services/identifierReader';

const READER_ELEMENT_ID = 'qr-scanner-reader';
const RELECTURA_MS = 3000;

export default function QrScannerModal({ show, onClose }) {
    const scannerRef = useRef(null);
    const ultimaLectura = useRef({ codigo: null, fecha: 0 });
    const [camaraActiva, setCamaraActiva] = useState(false);
    const [errorCamara, setErrorCamara] = useState('');
    const [buscando, setBuscando] = useState(false);
    const [codigoManual, setCodigoManual] = useState('');
    const [mensaje, setMensaje] = useState('');

    // El cierre temprano por `show` vive al final, junto al JSX: si se hiciera
    // aquí, las funciones declaradas abajo quedarían sin inicializar y el
    // cleanup de este efecto fallaría al intentar usarlas.
    useEffect(() => {
        if (show) {
            iniciarCamara();
        }
        return () => detenerCamara();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [show]);

    const procesarCodigo = async (valorCrudo) => {
        const codigo = normalizarCodigo(valorCrudo);
        if (!codigo) return;

        const ahora = Date.now();
        if (ultimaLectura.current.codigo === codigo && ahora - ultimaLectura.current.fecha < RELECTURA_MS) {
            return; // evita lecturas repetidas consecutivas del mismo código
        }
        ultimaLectura.current = { codigo, fecha: ahora };

        setBuscando(true);
        setMensaje('');
        try {
            const { data } = await axios.get(route('animales.buscar-identificador'), {
                params: { codigo },
            });

            if (data.encontrado) {
                detenerCamara();
                // Ruta hardcodeada a propósito: el nombre 'animales.show' es ambiguo
                // (routes/api.php registra otra ruta con el mismo nombre).
                router.visit('/animales/' + data.animal.id);
            } else {
                setMensaje(`No se encontró ningún animal con el código "${codigo}".`);
            }
        } catch (e) {
            setMensaje('Ocurrió un error al buscar el código.');
        } finally {
            setBuscando(false);
        }
    };

    const iniciarCamara = async () => {
        setErrorCamara('');
        try {
            const { Html5Qrcode } = await import('html5-qrcode');

            if (!scannerRef.current) {
                scannerRef.current = new Html5Qrcode(READER_ELEMENT_ID);
            }

            await scannerRef.current.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 250, height: 250 } },
                (decodedText) => procesarCodigo(decodedText),
                () => {}, // fallo de lectura de un frame: se ignora, es normal
            );

            setCamaraActiva(true);
        } catch (err) {
            setCamaraActiva(false);
            setErrorCamara(
                'No se pudo acceder a la cámara. Verifica que hayas dado permiso de cámara ' +
                'al navegador y que estés en una conexión segura (HTTPS o localhost).'
            );
        }
    };

    const detenerCamara = () => {
        if (scannerRef.current && camaraActiva) {
            scannerRef.current.stop().then(() => scannerRef.current.clear()).catch(() => {});
        }
        setCamaraActiva(false);
    };

    const handleClose = () => {
        detenerCamara();
        setMensaje('');
        setCodigoManual('');
        onClose();
    };

    const handleBuscarManual = (e) => {
        e.preventDefault();
        procesarCodigo(codigoManual);
    };

    if (!show) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div className="bg-white rounded-lg max-w-md w-full max-h-[90vh] overflow-y-auto">
                <div className="flex justify-between items-center border-b p-6">
                    <div className="flex items-center gap-3">
                        <QrCode className="w-6 h-6 text-indigo-600" />
                        <h2 className="text-xl font-bold text-gray-800">Escanear código QR</h2>
                    </div>
                    <button onClick={handleClose} className="text-gray-400 hover:text-gray-600">
                        <X className="w-6 h-6" />
                    </button>
                </div>

                <div className="p-6 space-y-4">
                    {errorCamara ? (
                        <div className="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg p-4 flex flex-col items-center gap-2 text-center">
                            <CameraOff className="w-8 h-8" />
                            <p>{errorCamara}</p>
                            <button
                                onClick={iniciarCamara}
                                className="mt-1 flex items-center gap-1 text-sm font-medium underline"
                            >
                                <RotateCcw className="w-4 h-4" /> Reintentar
                            </button>
                        </div>
                    ) : (
                        <div>
                            <div id={READER_ELEMENT_ID} className="rounded-lg overflow-hidden bg-black" />
                            <div className="flex justify-center mt-2">
                                <button
                                    onClick={camaraActiva ? detenerCamara : iniciarCamara}
                                    className="text-sm text-gray-600 hover:text-gray-900 underline"
                                >
                                    {camaraActiva ? 'Cerrar cámara' : 'Reabrir cámara'}
                                </button>
                            </div>
                        </div>
                    )}

                    {mensaje && (
                        <div className="bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-lg p-3">
                            {mensaje}
                        </div>
                    )}

                    <div className="pt-3 border-t">
                        <p className="text-sm font-medium text-gray-700 mb-2">
                            ¿La cámara no funciona? Escribe el código manualmente:
                        </p>
                        <form onSubmit={handleBuscarManual} className="flex gap-2">
                            <input
                                type="text"
                                value={codigoManual}
                                onChange={(e) => setCodigoManual(e.target.value)}
                                placeholder="Código del animal..."
                                className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                disabled={buscando}
                            />
                            <button
                                type="submit"
                                className="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 disabled:opacity-50"
                                disabled={buscando}
                            >
                                <Search className="w-4 h-4" />
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
}
