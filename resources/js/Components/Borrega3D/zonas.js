/**
 * Dónde cae cada parte del cuerpo en la figura 3D.
 *
 * Las claves son las mismas que guarda el backend (MarcaCorporal::ZONAS). Las
 * coordenadas viven solo aquí a propósito: si mañana se cambia la borrega por
 * un modelo realista, se ajustan estos números y las marcas ya registradas
 * siguen valiendo, porque «pata trasera derecha» no depende del dibujo.
 *
 * El sistema de ejes: X a los lados, Y hacia arriba, Z de la cola al hocico.
 *
 * IMPORTANTE — las posiciones van sobre la superficie EXTERIOR del vellón, no
 * sobre el elipsoide del cuerpo. La lana son esferas que sobresalen bastante:
 * llega a 1.08 de ancho y a 1.87 de alto en el centro del tronco. Un punto
 * colocado sobre el cuerpo (0.74 de ancho) queda enterrado y no se ve.
 *
 * Si se retoca la geometría del vellón en Borrega.jsx, hay que recalcular
 * estos valores o los puntos volverán a esconderse.
 */
export const ZONAS = {
    // ── Cabeza y cuello (sin lana: van sobre la propia geometría) ─────────
    cabeza:                   { pos: [0, 1.78, 1.14],      etiqueta: 'Cabeza' },
    hocico:                   { pos: [0, 1.26, 1.62],      etiqueta: 'Hocico' },
    oreja_izquierda:          { pos: [-0.54, 1.52, 1.06],  etiqueta: 'Oreja izquierda' },
    oreja_derecha:            { pos: [0.54, 1.52, 1.06],   etiqueta: 'Oreja derecha' },
    cuello:                   { pos: [0, 1.50, 0.90],      etiqueta: 'Cuello' },

    // ── Línea superior del tronco (sobre el vellón) ───────────────────────
    cruz:                     { pos: [0, 1.90, 0.42],      etiqueta: 'Cruz' },
    lomo:                     { pos: [0, 1.93, 0],         etiqueta: 'Lomo' },
    grupa:                    { pos: [0, 1.89, -0.62],     etiqueta: 'Grupa' },

    // ── Costados (sobre el vellón) ────────────────────────────────────────
    costillar_izquierdo:      { pos: [-1.12, 1.10, 0.24],  etiqueta: 'Costillar izquierdo' },
    costillar_derecho:        { pos: [1.12, 1.10, 0.24],   etiqueta: 'Costillar derecho' },
    flanco_izquierdo:         { pos: [-1.08, 0.95, -0.42], etiqueta: 'Flanco izquierdo' },
    flanco_derecho:           { pos: [1.08, 0.95, -0.42],  etiqueta: 'Flanco derecho' },

    // ── Frente, vientre y ubre ────────────────────────────────────────────
    pecho:                    { pos: [0, 1.05, 1.10],      etiqueta: 'Pecho' },
    vientre:                  { pos: [0, 0.22, 0.05],      etiqueta: 'Vientre' },
    ubre:                     { pos: [0, 0.28, -0.50],     etiqueta: 'Ubre' },

    // ── Extremidades (sin lana) ───────────────────────────────────────────
    pata_delantera_izquierda: { pos: [-0.47, 0.34, 0.58],  etiqueta: 'Pata delantera izquierda' },
    pata_delantera_derecha:   { pos: [0.47, 0.34, 0.58],   etiqueta: 'Pata delantera derecha' },
    pata_trasera_izquierda:   { pos: [-0.47, 0.34, -0.58], etiqueta: 'Pata trasera izquierda' },
    pata_trasera_derecha:     { pos: [0.47, 0.34, -0.58],  etiqueta: 'Pata trasera derecha' },
    cola:                     { pos: [0, 1.24, -1.22],     etiqueta: 'Cola' },
};

/** Color de cada tipo de marca. Mismo criterio que el resto del sistema. */
export const COLOR_TIPO = {
    lesion:      '#dc2626',
    vacuna:      '#2563eb',
    tratamiento: '#7c3aed',
    arete:       '#0891b2',
    condicion:   '#ca8a04',
    observacion: '#64748b',
};

/** Una marca resuelta se ve apagada, no desaparece. */
export const COLOR_RESUELTA = '#94a3b8';

export const claves = () => Object.keys(ZONAS);
