import React, { Suspense, useState } from 'react';
import { Canvas } from '@react-three/fiber';
import { OrbitControls, Html, ContactShadows } from '@react-three/drei';
import Borrega from './Borrega';
import { ZONAS, COLOR_TIPO, COLOR_RESUELTA } from './zonas';

/**
 * Visor 3D del ejemplar con sus marcas corporales.
 *
 * Se arrastra para girar y se hace rueda para acercar. En pantalla táctil
 * funciona con un dedo para girar y dos para acercar.
 *
 * Decisión de interacción: para señalar una parte del cuerpo no se hace clic
 * sobre la malla, sino sobre puntos de zona visibles. Detectar la zona por
 * dónde cayó el rayo sobre la geometría sería frágil —y ambiguo en los
 * límites—; con puntos explícitos el usuario ve exactamente qué está
 * eligiendo y el resultado es siempre el mismo.
 */

/** Punto que representa una marca ya registrada. */
function PuntoMarca({ marca, onSeleccionar, resaltado }) {
    const [encima, setEncima] = useState(false);
    const zona = ZONAS[marca.zona];

    if (!zona) return null;   // zona desconocida: no se inventa una posición

    const resuelta = marca.estado === 'resuelta';
    const color = resuelta ? COLOR_RESUELTA : (COLOR_TIPO[marca.tipo] || COLOR_TIPO.observacion);
    const escala = encima || resaltado ? 0.14 : 0.105;

    return (
        <group position={zona.pos}>
            <mesh
                // renderOrder alto + depthWrite apagado: el punto se dibuja
                // después de la lana, así que un mechón que sobresalga un poco
                // más de la cuenta ya no puede taparlo.
                renderOrder={10}
                onPointerOver={(e) => { e.stopPropagation(); setEncima(true); }}
                onPointerOut={() => setEncima(false)}
                onClick={(e) => { e.stopPropagation(); onSeleccionar?.(marca); }}
            >
                <sphereGeometry args={[escala, 16, 16]} />
                <meshStandardMaterial
                    color={color}
                    emissive={color}
                    emissiveIntensity={resuelta ? 0.1 : 0.45}
                    transparent
                    opacity={resuelta ? 0.55 : 1}
                    depthWrite={false}
                />
            </mesh>

            {/* Aro blanco alrededor: separa el punto de la lana clara y hace
                que se distinga aunque quede sobre un mechón del mismo tono. */}
            <mesh renderOrder={9}>
                <sphereGeometry args={[escala * 1.35, 16, 16]} />
                <meshBasicMaterial color="#ffffff" transparent opacity={0.55} depthWrite={false} />
            </mesh>

            {(encima || resaltado) && (
                <Html center distanceFactor={7} style={{ pointerEvents: 'none' }}>
                    <div className="px-2 py-1 rounded-md bg-slate-900/90 text-white text-[11px] whitespace-nowrap shadow-lg">
                        <strong>{zona.etiqueta}</strong>
                        <span className="block opacity-80">{marca.tipo_legible}</span>
                    </div>
                </Html>
            )}
        </group>
    );
}

/** Punto elegible al registrar una marca nueva. */
function PuntoZona({ clave, zona, seleccionada, onElegir }) {
    const [encima, setEncima] = useState(false);
    const activa = seleccionada === clave;

    return (
        <group position={zona.pos}>
            <mesh
                // Igual que las marcas: se dibujan por encima de la lana para
                // que ninguna quede escondida bajo un mechón.
                renderOrder={10}
                onPointerOver={(e) => { e.stopPropagation(); setEncima(true); }}
                onPointerOut={() => setEncima(false)}
                onClick={(e) => { e.stopPropagation(); onElegir(clave); }}
            >
                <sphereGeometry args={[activa ? 0.13 : 0.095, 14, 14]} />
                <meshStandardMaterial
                    color={activa ? '#059669' : '#0ea5e9'}
                    emissive={activa ? '#059669' : '#0ea5e9'}
                    emissiveIntensity={activa || encima ? 0.7 : 0.4}
                    transparent
                    opacity={activa || encima ? 1 : 0.85}
                    depthWrite={false}
                />
            </mesh>

            <mesh renderOrder={9}>
                <sphereGeometry args={[(activa ? 0.13 : 0.095) * 1.4, 14, 14]} />
                <meshBasicMaterial color="#ffffff" transparent opacity={0.6} depthWrite={false} />
            </mesh>

            {(encima || activa) && (
                <Html center distanceFactor={7} style={{ pointerEvents: 'none' }}>
                    <div className="px-2 py-1 rounded-md bg-emerald-700 text-white text-[11px] whitespace-nowrap shadow-lg">
                        {zona.etiqueta}
                    </div>
                </Html>
            )}
        </group>
    );
}

export default function VisorBorrega({
    marcas = [],
    modoSeleccion = false,
    zonaSeleccionada = null,
    onElegirZona,
    onSeleccionarMarca,
    marcaResaltada = null,
    colorLana = '#f5f0e6',
    gestante = false,
    altura = 380,
}) {
    return (
        <div style={{ height: altura }} className="relative rounded-2xl overflow-hidden bg-gradient-to-b from-sky-50 to-slate-100 border border-slate-200">
            <Canvas
                shadows
                camera={{ position: [3.1, 2.3, 3.4], fov: 42 }}
                // Se limita para que en equipos modestos —los de un rancho—
                // no se dispare el consumo por una figura decorativa.
                dpr={[1, 1.75]}
            >
                <Suspense fallback={null}>
                    <ambientLight intensity={0.75} />
                    <directionalLight
                        position={[4, 6, 3]}
                        intensity={1.5}
                        castShadow
                        shadow-mapSize={[1024, 1024]}
                    />
                    <directionalLight position={[-3, 2, -2]} intensity={0.35} />

                    {/* Al marcar, la borrega se vuelve traslúcida: así se
                        alcanzan también los puntos del costado opuesto sin
                        tener que girarla entera. */}
                    <Borrega
                        colorLana={colorLana}
                        gestante={gestante}
                        opacidad={modoSeleccion ? 0.45 : 1}
                    />

                    <ContactShadows position={[0, 0, 0]} opacity={0.32} blur={2.2} scale={5} far={2} />

                    {/* Marcas registradas */}
                    {!modoSeleccion && marcas.map((m) => (
                        <PuntoMarca
                            key={m.id}
                            marca={m}
                            resaltado={marcaResaltada === m.id}
                            onSeleccionar={onSeleccionarMarca}
                        />
                    ))}

                    {/* Puntos elegibles al registrar */}
                    {modoSeleccion && Object.entries(ZONAS).map(([clave, zona]) => (
                        <PuntoZona
                            key={clave}
                            clave={clave}
                            zona={zona}
                            seleccionada={zonaSeleccionada}
                            onElegir={onElegirZona}
                        />
                    ))}

                    <OrbitControls
                        enablePan={false}
                        minDistance={2.6}
                        maxDistance={8}
                        // No se deja pasar por debajo del suelo: desde ahí no
                        // se entiende nada y se ve el reverso de las caras.
                        maxPolarAngle={Math.PI / 2.05}
                        target={[0, 1, 0]}
                        makeDefault
                    />
                </Suspense>
            </Canvas>

            <p className="absolute bottom-2 left-3 text-[11px] text-slate-500 pointer-events-none">
                Arrastra para girar · rueda para acercar
            </p>
        </div>
    );
}
