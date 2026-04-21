import { useEffect, useRef } from 'react';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const TIPOS = [
    { key: 'leche',  label: 'Leche',  color: '#3B82F6' },
    { key: 'huevo',  label: 'Huevo',  color: '#F59E0B' },
    { key: 'lana',   label: 'Lana',   color: '#10B981' },
    { key: 'carne',  label: 'Carne',  color: '#EF4444' },
    { key: 'grasa',  label: 'Grasa',  color: '#F97316' },
    { key: 'cuero',  label: 'Cuero',  color: '#8B5CF6' },
    { key: 'plumas', label: 'Plumas', color: '#EC4899' },
    { key: 'canal',  label: 'Canal',  color: '#6B7280' },
];

export default function Tendencias({ datos }) {
    const barrasRef = useRef(null);
    const pastelRef = useRef(null);
    const barrasChart = useRef(null);
    const pastelChart = useRef(null);

    useEffect(() => {
        barrasChart.current?.destroy();
        pastelChart.current?.destroy();
    
        if (!Array.isArray(datos) || datos.length === 0) return;
    
        // Filtrar solo tipos que tienen al menos un valor > 0 (evita datasets vacíos)
        const tiposActivos = TIPOS.filter(({ key }) =>
            datos.some((d) => d[key] > 0)
        );

        // --- Gráfico de líneas (mejor para tendencias que barras) ---
        barrasChart.current = new Chart(barrasRef.current, {
            type: 'line',
            data: {
                labels: datos.map((t) => t.fecha),
                datasets: tiposActivos.map(({ key, label, color }) => ({
                    label,
                    data: datos.map((t) => t[key] ?? 0),
                    borderColor: color,
                    backgroundColor: color + '33', // 20% opacity fill
                    tension: 0.4,
                    fill: false,
                    pointRadius: 3,
                })),
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { mode: 'index' },
                },
                scales: {
                    x: { title: { display: true, text: 'Fecha' } },
                    y: { beginAtZero: true, title: { display: true, text: 'Cantidad' } },
                },
            },
        });

        // --- Gráfico de pastel con totales por tipo ---
        const totales = tiposActivos.map(({ key }) =>
            datos.reduce((acc, t) => acc + (t[key] ?? 0), 0)
        );

        pastelChart.current = new Chart(pastelRef.current, {
            type: 'pie',
            data: {
                labels: tiposActivos.map((t) => t.label),
                datasets: [{
                    data: totales,
                    backgroundColor: tiposActivos.map((t) => t.color),
                }],
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (item) => {
                                const total = totales.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? ((item.raw / total) * 100).toFixed(1) : 0;
                                return `${item.label}: ${item.raw} (${pct}%)`;
                            },
                        },
                    },
                },
            },
        });

        return () => {
            barrasChart.current?.destroy();
            pastelChart.current?.destroy();
        };
    }, [datos]);

    if (!Array.isArray(datos) || datos.length === 0) {
        return (
            <div className="bg-white rounded-2xl shadow p-6 text-center text-gray-400 py-10">
                Sin datos de tendencias disponibles.
            </div>
        );
    }

    return (
        <div className="bg-white rounded-2xl shadow p-6">
            <h2 className="text-xl font-semibold mb-6">Tendencias de Producción</h2>
            <div className="mb-10">
                <h3 className="text-lg font-semibold mb-4">Evolución por fecha</h3>
                <canvas ref={barrasRef} className="w-full h-96" />
            </div>
            <div className="max-w-md mx-auto">
                <h3 className="text-lg font-semibold mb-4">Proporción total</h3>
                <canvas ref={pastelRef} className="w-full h-96" />
            </div>
        </div>
    );
}