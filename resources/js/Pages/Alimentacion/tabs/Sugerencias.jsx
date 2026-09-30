export default function Sugerencias() {
    const sugerencias = [
        {
            titulo: "Reducir Costo",
            especie: "Ovino",
            detalle: "Cambiar a concentrado local puede reducir el costo por kg de ración.",
            mejora: "Ahorro mensual: $750",
        },
        {
            titulo: "Aumentar Proteína",
            especie: "Ovino",
            detalle: "Agregar 2% más de proteína mejora la conversión.",
            mejora: "+15% eficiencia",
        },
        {
            titulo: "Optimizar Horarios",
            especie: "Ovino",
            detalle: "Dividir la ración en varias tomas reduce el desperdicio en el comedero.",
            mejora: "-8% desperdicio",
        },
    ];

    return (
        <div>
            <h2 className="text-xl font-semibold mb-4">Sugerencias</h2>

            <div className="space-y-4">
                {sugerencias.map((s, i) => (
                    <div key={i} className="bg-white p-4 border shadow rounded">
                        <p className="font-semibold">{s.titulo}</p>
                        <p className="text-xs text-gray-500">Para: {s.especie}</p>

                        <p className="mt-2">{s.detalle}</p>
                        <p className="text-green-600 text-xs mt-1">{s.mejora}</p>

                        <button className="mt-2 px-3 py-1 text-xs border rounded hover:bg-gray-50">
                            Aplicar
                        </button>
                    </div>
                ))}
            </div>
        </div>
    );
}
