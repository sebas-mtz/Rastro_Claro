import { useEffect, useRef, useState, useCallback } from 'react';

/**
 * Normaliza un código leído por cualquier lector (microchip, RFID, QR o
 * tecleado manualmente): quita espacios, saltos de línea y lo pasa a
 * mayúsculas. Debe coincidir con Animal::normalizarIdentificador() en el
 * backend para que las comparaciones sean consistentes.
 */
export function normalizarCodigo(valor) {
    return String(valor ?? '').replace(/\s+/g, '').trim().toUpperCase();
}

// ─── Código ISO 11784 ────────────────────────────────────────────────────
//
// Es lo que devuelve un lector al pasar por un arete electrónico. Son 15
// dígitos: 3 de país o fabricante y 12 del animal.
//
// Sobre HDX y FDX-B: son las dos formas de transmisión de la norma ISO 11785
// y las resuelve el LECTOR. Desde aquí llega el mismo código con cualquiera de
// las dos, así que no hay nada que distinguir en el navegador.

export const PAIS_MEXICO = '484';
export const LONGITUD_ISO = 15;

/**
 * Interpreta un código ISO. Devuelve null si lo leído no lo es.
 *
 * Se queda solo con los dígitos porque cada marca lo presenta a su manera:
 * con espacios, con guiones o con el país separado por un punto.
 * Réplica de App\Support\CodigoIso11784 para poder dar respuesta inmediata
 * sin ir al servidor; el backend vuelve a validar antes de guardar.
 */
export function leerCodigoIso(crudo) {
    const digitos = String(crudo ?? '').replace(/\D+/g, '');

    if (digitos.length !== LONGITUD_ISO) return null;

    const pais = digitos.slice(0, 3);
    const esFabricante = Number(pais) >= 900;

    return {
        codigo: digitos,
        pais,
        nacional: digitos.slice(3),
        formateado: `${pais} ${digitos.slice(3)}`,
        esMexico: pais === PAIS_MEXICO,
        esFabricante,
        origen: pais === PAIS_MEXICO
            ? 'México (SINIIGA)'
            : esFabricante
                ? 'Código de fabricante, no de país'
                : `País ${pais}`,
    };
}

// ─── Lector USB tipo teclado ─────────────────────────────────────────────

/**
 * La mayoría de lectores de arete USB se comportan como un teclado: al leer,
 * "escriben" el código en el campo enfocado y terminan con un Enter. Este hook
 * mantiene el input enfocado y llama a onScan(codigo) al detectar ese Enter.
 *
 * Es el modo que funciona sin permisos ni configuración, así que sigue siendo
 * el camino recomendado.
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

// ─── Diagnóstico ─────────────────────────────────────────────────────────

/** Longitud del token de QR que genera el sistema. */
const LONGITUD_TOKEN_QR = 32;

/**
 * Deduce qué clase de identificador es lo que se leyó.
 *
 * No adivina de más: cuando no encaja en nada conocido lo dice, en vez de
 * suponer que es un arete visual solo porque son dígitos.
 */
export function clasificarLectura(texto) {
    const limpio = normalizarCodigo(texto);
    const digitos = limpio.replace(/\D+/g, '');

    if (!limpio) {
        return { tipo: 'vacio', etiqueta: 'Nada', descripcion: 'No llegó ningún carácter.' };
    }

    if (leerCodigoIso(limpio)) {
        return {
            tipo: 'iso',
            etiqueta: 'Arete electrónico ISO 11784',
            descripcion: 'Los 15 dígitos de la norma. Es lo que devuelve un lector de arete.',
        };
    }

    if (limpio.length === LONGITUD_TOKEN_QR && /^[A-Z0-9]+$/.test(limpio)) {
        return {
            tipo: 'qr',
            etiqueta: 'Token de código QR',
            descripcion: 'Corresponde a un QR generado por el sistema.',
        };
    }

    if (digitos.length === limpio.length) {
        return {
            tipo: 'numerico',
            etiqueta: 'Número sin formato ISO',
            descripcion: `Son ${limpio.length} dígitos. Puede ser un arete visual, pero no es un código electrónico.`,
        };
    }

    return {
        tipo: 'texto',
        etiqueta: 'Texto libre',
        descripcion: 'Mezcla letras y números. Puede ser un arete interno, un alias o un microchip anterior a la norma.',
    };
}

/**
 * Vuelve visibles los caracteres que no se ven.
 *
 * Es la parte más útil del diagnóstico: muchos lectores mandan un tabulador
 * antes del código o dos saltos de línea al final, y en un campo normal eso
 * es invisible. Aquí se muestra tal cual llegó.
 */
export function mostrarInvisibles(texto) {
    return String(texto ?? '')
        .replace(/\r/g, '␍')
        .replace(/\n/g, '␊')
        .replace(/\t/g, '␉')
        .replace(/ /g, '␠');
}

/**
 * Captura cruda de lo que manda el lector, tecla por tecla y con tiempos.
 *
 * El campo de texto normal solo deja ver el resultado final. Esto registra
 * cada pulsación para poder responder tres preguntas que deciden si un lector
 * sirve:
 *
 *   · ¿Manda algo antes o después del código? (prefijos y sufijos invisibles)
 *   · ¿Cierra con Enter, con tabulador, o con nada?
 *   · ¿Es un lector o alguien tecleando? Un lector escribe el código completo
 *     en pocos milisegundos; una persona tarda segundos.
 */
export function useCapturaCruda({ onLectura, activo = true } = {}) {
    const inputRef = useRef(null);
    const teclasRef = useRef([]);
    const inicioRef = useRef(null);

    useEffect(() => {
        if (activo && inputRef.current) inputRef.current.focus();
    }, [activo]);

    const reiniciar = () => {
        teclasRef.current = [];
        inicioRef.current = null;
    };

    const handleKeyDown = (event) => {
        const ahora = performance.now();

        if (inicioRef.current === null) inicioRef.current = ahora;

        // Las teclas modificadoras no forman parte de la lectura.
        if (['Shift', 'Control', 'Alt', 'Meta', 'CapsLock'].includes(event.key)) return;

        const esCierre = event.key === 'Enter' || event.key === 'Tab';

        if (!esCierre) {
            teclasRef.current.push({ tecla: event.key, ms: Math.round(ahora - inicioRef.current) });
            return;
        }

        // Tab también se intercepta: hay lectores que cierran con él y, sin
        // esto, el foco saltaría de campo y la lectura se perdería.
        event.preventDefault();

        const crudo = event.currentTarget.value;
        const duracion = Math.round(ahora - inicioRef.current);
        const teclas = [...teclasRef.current];

        reiniciar();
        event.currentTarget.value = '';

        if (!crudo && teclas.length === 0) return;

        onLectura?.({
            crudo,
            terminador: event.key,
            duracionMs: duracion,
            teclas,
            // Por debajo de este umbral no hay dedos humanos de por medio.
            pareceLector: duracion < 1000 && teclas.length > 3,
        });
    };

    return { inputRef, handleKeyDown, reiniciar };
}

// ─── Lector por puerto serial ────────────────────────────────────────────

/** ¿El navegador permite abrir puertos serie? Chrome y Edge sí; Firefox y Safari no. */
export function haySoporteSerial() {
    return typeof navigator !== 'undefined' && 'serial' in navigator;
}

/**
 * Lector conectado por puerto serie (USB-CDC o Bluetooth emparejado como
 * puerto COM), mediante la API Web Serial.
 *
 * Cubre el comportamiento común a la mayoría de lectores de arete: al leer,
 * envían el código como texto y cierran con salto de línea. Eso es suficiente
 * para los que trabajan en modo texto.
 *
 * Lo que NO cubre: lectores que hablan un protocolo binario propietario, o que
 * exigen enviarles un comando para disparar la lectura. Eso depende del modelo
 * y no puede escribirse a ciegas — hace falta su manual.
 *
 * Requiere HTTPS (o localhost) y que el usuario elija el puerto: el navegador
 * no da acceso a un dispositivo sin que la persona lo autorice.
 */
export class SerialTagReader {
    constructor({ baudRate = 9600 } = {}) {
        this.baudRate = baudRate;
        this.port = null;
        this.reader = null;
        this.leyendo = false;
        this.buffer = '';
    }

    /**
     * Pide al usuario que elija el puerto y lo abre.
     * Debe llamarse desde un gesto del usuario (un clic): el navegador lo exige.
     */
    async conectar() {
        if (!haySoporteSerial()) {
            throw new Error(
                'Este navegador no permite conectar lectores por puerto serie. ' +
                'Usa Chrome o Edge, o conecta el lector en modo teclado.'
            );
        }

        this.port = await navigator.serial.requestPort();
        await this.port.open({ baudRate: this.baudRate });
    }

    /**
     * Empieza a escuchar. Llama a onCodigo(texto) por cada línea completa.
     *
     * Se acumula en un buffer porque una lectura puede llegar partida en
     * varios paquetes: solo se entrega cuando aparece el fin de línea.
     */
    async escuchar(onCodigo, onError) {
        if (!this.port) throw new Error('Conecta el lector antes de escuchar.');

        this.leyendo = true;
        const decoder = new TextDecoder();

        try {
            while (this.leyendo && this.port.readable) {
                this.reader = this.port.readable.getReader();

                try {
                    while (this.leyendo) {
                        const { value, done } = await this.reader.read();
                        if (done) break;

                        this.buffer += decoder.decode(value, { stream: true });

                        let corte;
                        while ((corte = this.buffer.search(/[\r\n]/)) !== -1) {
                            const linea = this.buffer.slice(0, corte).trim();
                            this.buffer = this.buffer.slice(corte + 1);

                            if (linea) onCodigo(normalizarCodigo(linea));
                        }
                    }
                } finally {
                    this.reader.releaseLock();
                    this.reader = null;
                }
            }
        } catch (error) {
            // Desconectar el cable en plena lectura entra por aquí. Se avisa
            // en vez de dejar la pantalla esperando un código que no llegará.
            if (this.leyendo && onError) onError(error);
        }
    }

    async desconectar() {
        this.leyendo = false;
        this.buffer = '';

        try {
            await this.reader?.cancel();
        } catch {
            // El lector ya podía estar liberado; no hay nada que rescatar.
        }

        try {
            await this.port?.close();
        } catch {
            // Igual que arriba: cerrar un puerto ya cerrado no es un problema.
        }

        this.port = null;
    }
}

/**
 * Envoltura del lector serial para usarlo desde un componente.
 *
 * Devuelve el estado de la conexión y las dos acciones. Se desconecta solo al
 * desmontar, para no dejar el puerto tomado.
 */
export function useSerialTagReader({ onScan } = {}) {
    const lectorRef = useRef(null);
    const [conectado, setConectado] = useState(false);
    const [error, setError] = useState('');

    const desconectar = useCallback(async () => {
        await lectorRef.current?.desconectar();
        lectorRef.current = null;
        setConectado(false);
    }, []);

    const conectar = useCallback(async () => {
        setError('');

        try {
            const lector = new SerialTagReader();
            await lector.conectar();

            lectorRef.current = lector;
            setConectado(true);

            // Sin await: se queda escuchando en segundo plano.
            lector.escuchar(
                (codigo) => onScan?.(codigo),
                (e) => {
                    setError('Se perdió la conexión con el lector: ' + e.message);
                    setConectado(false);
                }
            );
        } catch (e) {
            // Cerrar el diálogo de selección de puerto no es un error digno
            // de mostrarse: el usuario simplemente cambió de opinión.
            if (e?.name !== 'NotFoundError') {
                setError(e.message);
            }
            setConectado(false);
        }
    }, [onScan]);

    useEffect(() => () => { lectorRef.current?.desconectar(); }, []);

    return { conectado, error, conectar, desconectar, soportado: haySoporteSerial() };
}
