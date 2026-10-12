import React, { useMemo } from 'react';

/**
 * Borrega armada con figuras geométricas.
 *
 * No pretende ser realista: es un muñeco reconocible que sirve para señalar
 * partes del cuerpo. Se dibuja por código, así que no depende de ningún
 * archivo de modelo y no suma peso de descarga.
 *
 * El día que se consiga un modelo realista en .glb, se reemplaza este
 * componente y todo lo demás —marcas, zonas, controles— sigue igual.
 */

/** Vellón: racimo de esferas que da el aspecto de lana en vez de un huevo liso. */
function Vellon({ color, opacidad = 1 }) {
    // Posiciones fijas y deterministas: si se generaran al azar en cada
    // render, la borrega "hervería" en cada fotograma.
    const bolas = useMemo(() => {
        const puntos = [];
        const filas = [
            { z: 0.72, r: 0.52, y: 1.08 },
            { z: 0.36, r: 0.62, y: 1.05 },
            { z: 0.0, r: 0.65, y: 1.03 },
            { z: -0.36, r: 0.62, y: 1.03 },
            { z: -0.72, r: 0.5, y: 1.06 },
        ];

        filas.forEach(({ z, r, y }, i) => {
            const cuantas = 7;
            for (let k = 0; k < cuantas; k++) {
                const ang = (k / cuantas) * Math.PI * 2 + i * 0.4;
                puntos.push({
                    key: `${i}-${k}`,
                    pos: [Math.cos(ang) * r * 1.15, y + Math.sin(ang) * r * 0.78, z],
                    escala: 0.3 + ((i + k) % 3) * 0.035,
                });
            }
        });

        return puntos;
    }, []);

    return (
        <group>
            {bolas.map((b) => (
                <mesh key={b.key} position={b.pos} castShadow>
                    <sphereGeometry args={[b.escala, 16, 16]} />
                    <meshStandardMaterial color={color} roughness={0.95} transparent={opacidad < 1} opacity={opacidad} />
                </mesh>
            ))}
        </group>
    );
}

export default function Borrega({
    opacidad = 1,
    colorLana = '#f5f0e6',
    colorCara = '#3f3a36',
    gestante = false,
}) {
    return (
        <group>
            {/* Cuerpo: elipsoide bajo el vellón, para que no se vean huecos */}
            <mesh position={[0, 1.04, 0]} scale={[0.66, 0.56, 0.98]} castShadow>
                <sphereGeometry args={[1, 32, 24]} />
                <meshStandardMaterial color={colorLana} roughness={0.9} transparent={opacidad < 1} opacity={opacidad} />
            </mesh>

            <Vellon color={colorLana} opacidad={opacidad} />

            {/* Vientre más marcado cuando está gestante: se nota de un vistazo */}
            {gestante && (
                <mesh position={[0, 0.72, -0.25]} castShadow>
                    <sphereGeometry args={[0.62, 24, 20]} />
                    <meshStandardMaterial color={colorLana} roughness={0.95} transparent={opacidad < 1} opacity={opacidad} />
                </mesh>
            )}

            {/* Cuello */}
            <mesh position={[0, 1.3, 0.86]} rotation={[0.5, 0, 0]} castShadow>
                <cylinderGeometry args={[0.24, 0.3, 0.5, 16]} />
                <meshStandardMaterial color={colorCara} roughness={0.8} transparent={opacidad < 1} opacity={opacidad} />
            </mesh>

            {/* Cabeza */}
            <mesh position={[0, 1.45, 1.16]} castShadow>
                <sphereGeometry args={[0.34, 24, 20]} />
                <meshStandardMaterial color={colorCara} roughness={0.75} transparent={opacidad < 1} opacity={opacidad} />
            </mesh>

            {/* Hocico */}
            <mesh position={[0, 1.3, 1.44]} castShadow>
                <sphereGeometry args={[0.19, 20, 16]} />
                <meshStandardMaterial color={colorCara} roughness={0.7} transparent={opacidad < 1} opacity={opacidad} />
            </mesh>

            {/* Ojos */}
            {[-0.15, 0.15].map((x) => (
                <mesh key={x} position={[x, 1.52, 1.4]}>
                    <sphereGeometry args={[0.052, 12, 12]} />
                    <meshStandardMaterial color="#111" roughness={0.3} transparent={opacidad < 1} opacity={opacidad} />
                </mesh>
            ))}

            {/* Orejas: caídas a los lados, como las de un ovino de pelo */}
            {[-1, 1].map((lado) => (
                <mesh
                    key={lado}
                    position={[lado * 0.36, 1.5, 1.08]}
                    rotation={[0, 0, lado * 0.9]}
                    castShadow
                >
                    <sphereGeometry args={[0.15, 14, 10]} />
                    <meshStandardMaterial color={colorCara} roughness={0.8} transparent={opacidad < 1} opacity={opacidad} />
                </mesh>
            ))}

            {/* Patas */}
            {[
                [-0.34, 0.58], [0.34, 0.58],
                [-0.34, -0.58], [0.34, -0.58],
            ].map(([x, z]) => (
                <group key={`${x}-${z}`}>
                    <mesh position={[x, 0.34, z]} castShadow>
                        <cylinderGeometry args={[0.09, 0.075, 0.68, 12]} />
                        <meshStandardMaterial color={colorCara} roughness={0.8} transparent={opacidad < 1} opacity={opacidad} />
                    </mesh>
                    {/* Pezuña */}
                    <mesh position={[x, 0.03, z]} castShadow>
                        <cylinderGeometry args={[0.085, 0.095, 0.09, 12]} />
                        <meshStandardMaterial color="#1c1917" roughness={0.6} transparent={opacidad < 1} opacity={opacidad} />
                    </mesh>
                </group>
            ))}

            {/* Cola */}
            <mesh position={[0, 1.3, -1.02]} rotation={[0.7, 0, 0]} castShadow>
                <cylinderGeometry args={[0.09, 0.05, 0.34, 10]} />
                <meshStandardMaterial color={colorLana} roughness={0.95} transparent={opacidad < 1} opacity={opacidad} />
            </mesh>

            {/* Suelo, solo para que la sombra tenga dónde caer */}
            <mesh rotation={[-Math.PI / 2, 0, 0]} position={[0, -0.02, 0]} receiveShadow>
                <circleGeometry args={[2.4, 48]} />
                <meshStandardMaterial color="#e7e5e4" roughness={1} />
            </mesh>
        </group>
    );
}
