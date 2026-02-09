export default function Reportes({ resumen = {} }) {

    const {
        totalMes = 0,
        variacionMes = 0,
        eficiencia = 0,
        meta = 100, // Valor por defecto
        // ❌ ingresoMes y ingresoAnual ya no vienen aquí
        lotes = []
    } = resumen;

    // ✅ Obtener ingresos desde props separadas o calcular aquí
    // Si los ingresos vienen en resumen.ingresos, desestructurar:
    const { ingresoMes = 0, ingresoAnual = 0 } = resumen.ingresos || {};

    return (
        <div className="space-y-6">

            {/* CARDS SUPERIORES */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">

                <div className="bg-white rounded-2xl shadow p-6">
                    <p className="text-gray-500 font-medium">Producción Total Mes</p>
                    <h2 className="text-3xl font-bold mt-2">{totalMes.toLocaleString()}</h2>
                    <p className="text-sm text-gray-400">Unidades equivalentes</p>
                    <p className={`text-sm mt-1 ${variacionMes >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                        {variacionMes >= 0 ? '+' : ''}{variacionMes}% vs mes anterior
                    </p>
                </div>

                <div className="bg-white rounded-2xl shadow p-6">
                    <p className="text-gray-500 font-medium">Eficiencia Promedio</p>
                    <h2 className={`text-3xl font-bold mt-2 ${
                        eficiencia >= 80 ? 'text-green-600' : 
                        eficiencia >= 60 ? 'text-yellow-600' : 'text-red-600'
                    }`}>
                        {eficiencia}%
                    </h2>
                    <p className="text-sm text-gray-500">Meta vs Realizado</p>
                    <p className="text-blue-600 text-sm mt-1">Meta: {meta}%</p>
                </div>

                <div className="bg-white rounded-2xl shadow p-6">
                    <p className="text-gray-500 font-medium">Ingreso Estimado</p>
                    <h2 className="text-3xl font-bold mt-2">
                        ${typeof ingresoMes === 'number' ? ingresoMes.toLocaleString() : '0'}
                    </h2>
                    <p className="text-sm text-gray-500">Producción del mes</p>
                    <p className="text-purple-600 text-sm mt-1">
                        Proyección anual: ${typeof ingresoAnual === 'number' ? ingresoAnual.toLocaleString() : '0'}
                    </p>
                </div>

            </div>

            {/* RESUMEN POR LOTE */}
            <div className="bg-white rounded-2xl shadow p-6">
                <h3 className="text-lg font-semibold">Resumen de Producción por Lote</h3>
                <p className="text-sm text-gray-500 mb-4">
                    Detalle de rendimiento por área de la granja
                </p>

                <div className="divide-y">
                    {lotes.length > 0 ? (
                        lotes.map((lote, i) => (
                            <div key={i} className="flex justify-between items-center py-4">
                                <div>
                                    <p className="font-medium">{lote.nombre || `Lote ${i + 1}`}</p>
                                    <p className="text-sm text-gray-500">
                                        {lote.especie || 'General'} - {lote.animales || lote.animales_count || 0} animales
                                    </p>
                                </div>
                                <div className="text-right">
                                    <p className="font-semibold">
                                        {typeof lote.valor === 'number' ? lote.valor.toLocaleString() : lote.valor || 0}
                                    </p>
                                    <p className="text-sm text-gray-600">
                                        Eficiencia: {lote.eficiencia || 0}%
                                    </p>
                                </div>
                            </div>
                        ))
                    ) : (
                        <div className="text-center py-8 text-gray-500">
                            No hay datos de lotes disponibles
                        </div>
                    )}
                </div>
            </div>

        </div>
    );
}