import { useEffect, useRef } from 'react';

/**
 * Normaliza un código leído por cualquier lector (microchip, RFID, QR o
 * tecleado manualmente): quita espacios, saltos de línea y lo pasa a
 * mayúsculas. Debe coincidir con Animal::normalizarIdentificador() en el
 * backend para que las comparaciones sean consistentes.
 */
export function normalizarCodigo(valor) {
    return String(valor ?? '').replace(/\s+/g, '').trim().toUpperCase();
}

/**
 * Lector USB tipo teclado (keyboard wedge).
 *
 * La mayoría de lectores de microchip/RFID/código de barras USB se
 * comportan como un teclado: al escanear, "escriben" el código en el
 * campo enfocado y terminan con un Enter. Este hook mantiene el input
 * enfocado automáticamente y llama a onScan(codigo) cuando detecta el
 * Enter del lector.
 */
export function useKeyboardWedgeReader({ onScan, activo = true } = {}) {
    const inputRef = useRef(null);

    useEffect(() => {
        if (activo && inputRef.current) {
            inputRef.current.focus();
        }
    }, [activo]);

    const handleKeyDown = (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            const codigo = normalizarCodigo(event.currentTarget.value);
            if (codigo && onScan) {
                onScan(codigo);
            }
        }
    };

    return { inputRef, handleKeyDown };
}

/**
 * Lector serial o Bluetooth (placeholder documentado).
 *
 * NO implementado: no se conoce el modelo exacto del lector físico, y
 * cada fabricante usa su propio protocolo/formato de trama. Antes de
 * implementar esta clase hace falta:
 *
 *   - Marca y modelo del lector.
 *   - Protocolo de comunicación (Web Serial API para USB-serial,
 *     Web Bluetooth API para BLE).
 *   - Formato exacto del código que envía (con/sin checksum, prefijos,
 *     longitud fija, etc.).
 *   - Tipo de conexión (cable serial/USB-CDC, Bluetooth Low Energy,
 *     Bluetooth clásico SPP).
 *
 * Mientras tanto, la lectura por teclado USB (useKeyboardWedgeReader) y
 * la captura manual cubren el caso de uso real.
 */
export class SerialBluetoothReader {
    async connect() {
        throw new Error(
            'Lector serial/Bluetooth no implementado todavía: falta especificar marca, modelo, ' +
            'protocolo (Web Serial o Web Bluetooth) y formato del código del dispositivo real.'
        );
    }
}
