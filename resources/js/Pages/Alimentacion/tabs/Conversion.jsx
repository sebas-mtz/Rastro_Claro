import { usePage } from '@inertiajs/react';

export default function Conversion() {
    const { conversionSeries = [], conversionSummary = {} } = usePage().props;

    const maxValor =
        conversionSeries.length > 0
            ? Math.max(...conversionSeries.map((p) => p.valor)) || 1
            : 1;

    return (
        <div>
            <h2 className="text-xl font-semibold text-gray-800 mb-2">
                Conversión Alimenticia
            </h2>
            <p className="text-sm text-gray-500 mb-4">
                Kg de alimento por animal al mes (menor es mejor).
            </p>

            {/* GRÁFICA */}
            <div className="bg-white border border-gray-100 rounded-xl shadow-sm p-4 mb-6">
                {conversionSeries.length === 0 ? (
                    <p className="text-sm text-gray-400">
                        Aún no hay datos suficientes para calcular la conversión.
                        Registra raciones en la pestaña <strong>Raciones</strong>.
                    </p>
                ) : (
                    <div>
                        <p className="text-xs text-gray-400 mb-2">
                            Promedio mensual (todas las especies)
                        </p>

                        <div className="h-48">
                            <svg viewBox="0 0 100 40" className="w-full h-full">
                                {/* línea base */}
                                <line
                                    x1="0"
                                    y1="39"
                                    x2="100"
                                    y2="39"
                                    stroke="#e5e7eb"
                                    strokeWidth="0.5"
                                />
                                <line
                                    x1="0"
                                    y1="20"
                                    x2="100"
                                    y2="20"
                                    stroke="#f3f4f6"
                                    strokeWidth="0.5"
                                />

                                {/* línea */}
                                {conversionSeries.length > 1 && (
                                    <polyline
                                        fill="none"
                                        stroke="#22c55e"
                                        strokeWidth="1.5"
                                        points={conversionSeries
                                            .map((p, idx) => {
                                                const x =
                                                    conversionSeries.length === 1
                                                        ? 50
                                                        : (idx /
                                                              (conversionSeries.length -
                                                                  1)) * 100;
                                                const y =
                                                    38 -
                                                    (p.valor / maxValor) * 30;
                                                return `${x},${y}`;
                                            })
                                            .join(' ')}
                                    />
                                )}

                                {/* puntos */}
                                {conversionSeries.map((p, idx) => {
                                    const x =
                                        conversionSeries.length === 1
                                            ? 50
                                            : (idx /
                                                  (conversionSeries.length -
                                                      1)) *
                                              100;
                                    const y =
                                        38 -
                                        (p.valor / maxValor) * 30;
                                    return (
                                        <g key={p.year_month}>
                                            <circle
                                                cx={x}
                                                cy={y}
                                                r="1.5"
                                                fill="#22c55e"
                                            />
                                        </g>
                                    );
                                })}
                            </svg>
                        </div>

                        {/* etiquetas de meses */}
                        <div className="mt-3 flex justify-between text-[10px] text-gray-400">
                            {conversionSeries.map((p) => (
                                <span key={p.year_month}>{p.mes}</span>
                            ))}
                        </div>
                    </div>
                )}
            </div>

            {/* TARJETAS RESUMEN */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                {/* Mejor conversión */}
                <div className="bg-white border border-gray-100 rounded-xl shadow-sm p-4">
                    <p className="text-xs text-gray-500 mb-1">
                        Mejor Conversión
                    </p>
                    <p className="text-2xl font-semibold text-emerald-600">
                        {conversionSummary.best_value ?? '—'}
                    </p>
                    <p className="text-xs text-gray-400 mt-1">
                        {conversionSummary.best_label
                            ? `Mejor mes: ${conversionSummary.best_label}`
                            : 'Cuando haya suficientes datos'}
                    </p>
                </div>

                {/* Promedio general */}
                <div className="bg-white border border-gray-100 rounded-xl shadow-sm p-4">
                    <p className="text-xs text-gray-500 mb-1">
                        Promedio General
                    </p>
                    <p className="text-2xl font-semibold text-gray-800">
                        {conversionSummary.average ?? '—'}
                    </p>
                    <p className="text-xs text-gray-400 mt-1">
                        Todas las especies
                    </p>
                </div>

                {/* Tendencia */}
                <div className="bg-white border border-gray-100 rounded-xl shadow-sm p-4">
                    <p className="text-xs text-gray-500 mb-1">Tendencia</p>
                    {typeof conversionSummary.trend === 'number' ? (
                        <div>
                            <p
                                className={`text-2xl font-semibold ${
                                    conversionSummary.trend >= 0
                                        ? 'text-emerald-600'
                                        : 'text-red-600'
                                }`}
                            >
                                {conversionSummary.trend >= 0 ? '↓' : '↑'}{' '}
                                {Math.abs(conversionSummary.trend)}%
                            </p>
                            <p className="text-xs text-gray-400 mt-1">
                                {conversionSummary.trend >= 0
                                    ? 'Mejora vs mes anterior'
                                    : 'Peor que el mes anterior'}
                            </p>
                        </div>
                    ) : (
                        <p className="text-xs text-gray-400 mt-4">
                            Se necesita al menos 2 meses de datos.
                        </p>
                    )}
                </div>
            </div>
        </div>
    );
}
