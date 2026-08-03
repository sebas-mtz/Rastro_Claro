import React, { useEffect, useRef, useState } from 'react';
import { X, QrCode, Download, Printer } from 'lucide-react';
import { QRCodeCanvas } from 'qrcode.react';
import axios from 'axios';

export default function QrCodeModal({ show, onClose, animal }) {
    const [cargando, setCargando] = useState(false);
    const [error, setError] = useState('');
    const [url, setUrl] = useState('');
    const canvasWrapperRef = useRef(null);

    useEffect(() => {
        if (show && animal) {
            obtenerQr();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [show, animal]);

    const obtenerQr = async () => {
        setCargando(true);
        setError('');
        try {
            const { data } = await axios.get(route('animales.qr', animal.id));
            setUrl(data.url);
        } catch (e) {
            setError('No se pudo generar el código QR. Intenta de nuevo.');
        } finally {
            setCargando(false);
        }
    };

    const descargar = () => {
        const canvas = canvasWrapperRef.current?.querySelector('canvas');
        if (!canvas) return;

        const enlace = document.createElement('a');
        enlace.download = `qr-${animal.arete || animal.id}.png`;
        enlace.href = canvas.toDataURL('image/png');
        enlace.click();
    };

    const imprimir = () => {
        const canvas = canvasWrapperRef.current?.querySelector('canvas');
        if (!canvas) return;

        const ventana = window.open('', '_blank');
        if (!ventana) return;

        ventana.document.write(`
            <html>
                <head><title>QR ${animal.arete || ''}</title></head>
                <body style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;font-family:sans-serif;">
                    <img src="${canvas.toDataURL('image/png')}" style="width:280px;height:280px;" />
                    <p>${animal.alias || ''} ${animal.arete ? '(' + animal.arete + ')' : ''}</p>
                </body>
            </html>
        `);
        ventana.document.close();
        ventana.focus();
        ventana.print();
    };

    if (!show || !animal) return null;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div className="bg-white rounded-lg max-w-sm w-full">
                <div className="flex justify-between items-center border-b p-6">
                    <div className="flex items-center gap-3">
                        <QrCode className="w-6 h-6 text-indigo-600" />
                        <h2 className="text-xl font-bold text-gray-800">Código QR del animal</h2>
                    </div>
                    <button onClick={onClose} className="text-gray-400 hover:text-gray-600">
                        <X className="w-6 h-6" />
                    </button>
                </div>

                <div className="p-6 flex flex-col items-center gap-4">
                    <p className="text-sm text-gray-600 text-center">
                        {animal.alias || 'Sin alias'} {animal.arete ? `— Arete: ${animal.arete}` : ''}
                    </p>

                    {cargando && <p className="text-sm text-gray-500">Generando código QR...</p>}
                    {error && <p className="text-sm text-red-600">{error}</p>}

                    {url && !cargando && (
                        <>
                            <div ref={canvasWrapperRef} className="p-4 bg-white border rounded-lg">
                                <QRCodeCanvas value={url} size={220} includeMargin />
                            </div>

                            <div className="flex gap-3">
                                <button
                                    onClick={descargar}
                                    className="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium"
                                >
                                    <Download className="w-4 h-4" /> Descargar
                                </button>
                                <button
                                    onClick={imprimir}
                                    className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium"
                                >
                                    <Printer className="w-4 h-4" /> Imprimir
                                </button>
                            </div>
                        </>
                    )}
                </div>
            </div>
        </div>
    );
}
