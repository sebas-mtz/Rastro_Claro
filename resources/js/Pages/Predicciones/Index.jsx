import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

function PrediccionesIndex() {
    const { predicciones = {}, hoy, analisisIa } = usePage().props;

    const cards = [
        { tipo: 'leche', label: 'Producción de leche', icon: '🥛', unidad: 'L' },
        { tipo: 'huevo', label: 'Producción de huevo', icon: '🥚', unidad: 'unid.' },
        { tipo: 'carne', label: 'Producción de carne', icon: '🥩', unidad: 'kg' },
    ];

    const getColorTendencia = (t) => {
        if (t === 'subiendo') return 'text-emerald-600 bg-emerald-50';
        if (t === 'bajando') return 'text-red-600 bg-red-50';
        return 'text-gray-600 bg-gray-50';
    };

    const getTextoTendencia = (t) => {
        if (t === 'subiendo') return 'Tendencia al alza';
        if (t === 'bajando') return 'Tendencia a la baja';
        return 'Tendencia estable';
    };

    return (
        <>
            <Head title="Predicciones" />

            <div className="py-8 px-4 max-w-6xl mx-auto space-y-6">
                {/* ENCABEZADO */}
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-800 flex items-center gap-2">
                            Módulo de Predicciones <span>👑</span>
                        </h1>
                        <p className="text-sm text-gray-600">
                            Estimaciones basadas en tu producción de los últimos 30 días.
                        </p>
                        <p className="text-xs text-gray-400 mt-1">
                            Datos hasta: {hoy}
                        </p>
                    </div>

                    <div className="text-xs text-emerald-700 bg-emerald-50 border border-emerald-100 px-3 py-1 rounded-full">
                        Plan Premium · Análisis avanzado
                    </div>
                </div>

                {/* CARDS DE PREDICCIÓN */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    {cards.map((card) => {
                        const data = predicciones[card.tipo] || {};
                        const tendenciaClass = getColorTendencia(data.tendencia);

                        return (
                            <div
                                key={card.tipo}
                                className="bg-white rounded-2xl shadow p-5 border border-gray-100"
                            >
                                <div className="flex justify-between items-start mb-3">
                                    <div>
                                        <div className="text-xs uppercase tracking-wide text-gray-400">
                                            {card.label}
                                        </div>
                                        <div className="text-lg font-semibold text-gray-800">
                                            Próximos 30 días
                                        </div>
                                    </div>
                                    <div className="text-3xl">{card.icon}</div>
                                </div>

                                <div className="text-3xl font-bold text-gray-900 mb-1">
                                    {data.proyeccion_30dias ?? 0}{' '}
                                    <span className="text-sm text-gray-400">
                                        {card.unidad}
                                    </span>
                                </div>

                                <p className="text-xs text-gray-500 mb-2">
                                    Promedio diario actual:{' '}
                                    <span className="font-semibold text-gray-700">
                                        {data.promedio_diario ?? 0} {card.unidad}/día
                                    </span>
                                </p>

                                <div className="flex items-center justify-between mb-3">
                                    <div
                                        className={`inline-flex items-center px-2 py-0.5 rounded-full text-[11px] ${tendenciaClass}`}
                                    >
                                        {getTextoTendencia(data.tendencia)}
                                    </div>
                                    {data.cambio_porcentaje !== null && (
                                        <div
                                            className={
                                                'text-xs font-semibold ' +
                                                (data.cambio_porcentaje > 0
                                                    ? 'text-emerald-600'
                                                    : 'text-red-600')
                                            }
                                        >
                                            {data.cambio_porcentaje > 0 ? '+' : ''}
                                            {data.cambio_porcentaje}% vs. mes anterior
                                        </div>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>

                {/* ANÁLISIS AUTOMÁTICO DE IA */}
                <div className="bg-white rounded-2xl shadow border border-gray-100 p-5">
                    <h2 className="text-lg font-semibold text-gray-800 mb-1 flex items-center gap-2">
                        Análisis de la IA
                        <span className="text-sm px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">
                            Asesor inteligente
                        </span>
                    </h2>
                    <p className="text-xs text-gray-500 mb-3">
                        Resumen automático generado con tus datos de producción de los últimos 30 días.
                    </p>

                    {analisisIa ? (
                        <div className="whitespace-pre-line text-sm text-gray-800 leading-relaxed">
                            {analisisIa}
                        </div>
                    ) : (
                        <p className="text-sm text-gray-400">
                            Aún no hay análisis generado por la IA.
                        </p>
                    )}
                </div>
            </div>
        </>
    );
}

PrediccionesIndex.layout = (page) => <AppLayout>{page}</AppLayout>;

export default PrediccionesIndex;
