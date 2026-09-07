// resources/js/Components/Statistics.jsx
import React from 'react';
import { usePage, Head } from '@inertiajs/react';
import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
    Legend,
} from 'recharts';
import { formatMXN } from '@/utils/currency';

export default function Statistics() {
    // 👇 PON ESTOS VALORES POR DEFECTO
    const {
        summary = {
            animalsActive: 0,
            animalsDiff: 0,
            upcomingBirths: 0,
            vaccinationAlerts: 0,
            foodInventoryPercent: 0,
            foodDaysAvailable: 0,
        },
        speciesDistribution = [],
        productionByMonth = [],
        alerts = [],
        costos = { ingresos: 0, costos: 0, utilidad: 0, estado: 'utilidad' },
        rebano = {},
        alertasOperativas = [],
    } = usePage().props;

    const colorNivel = {
        critica: 'bg-red-50 border-red-100 text-red-800',
        atencion: 'bg-amber-50 border-amber-100 text-amber-800',
        informativa: 'bg-slate-50 border-slate-200 text-slate-700',
    };
    return (
        <div className="px-8 py-6 space-y-6">
            {/* Encabezado */}
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-semibold text-slate-900">Hola</h1>
                    <p className="text-sm text-slate-500">
                        Resumen general de tu granja
                    </p>
                </div>

                <button className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                    Todas las especies
                    <span className="text-xs">▼</span>
                </button>
            </div>

            {/* Resumen tarjetas superiores */}
            <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                {/* Animales activos */}
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-xs font-medium text-slate-500">Animales Activos</p>
                    <div className="mt-2 text-2xl font-semibold text-slate-900">
                        {summary.animalsActive ?? 0}
                    </div>
                    <p className="mt-1 text-xs text-emerald-600">
                        +{summary.animalsDiff ?? 0} desde el mes pasado
                    </p>
                </div>

                {/* Partos próximos */}
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-xs font-medium text-slate-500">Partos Próximos</p>
                    <div className="mt-2 text-2xl font-semibold text-orange-600">
                        {summary.upcomingBirths ?? 0}
                    </div>
                    <p className="mt-1 text-xs text-slate-500">
                        En los próximos 7 días
                    </p>
                </div>

                {/* Alertas de vacunación */}
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-xs font-medium text-slate-500">
                        Alertas de Vacunación
                    </p>
                    <div className="mt-2 text-2xl font-semibold text-red-600">
                        {summary.vaccinationAlerts ?? 0}
                    </div>
                    <p className="mt-1 text-xs text-slate-500">
                        Requieren atención inmediata
                    </p>
                </div>

                {/* Inventario alimento */}
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-xs font-medium text-slate-500">
                        Inventario Alimento
                    </p>
                    <div className="mt-2 text-2xl font-semibold text-emerald-600">
                        {summary.foodInventoryPercent ?? 0}%
                    </div>
                    <p className="mt-1 text-xs text-slate-500">
                        Disponible para {summary.foodDaysAvailable ?? 0} días
                    </p>
                </div>
            </div>

            {/* Indicadores del rebaño ovino */}
            <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-xs font-medium text-slate-500">Borregas reproductoras</p>
                    <div className="mt-2 text-2xl font-semibold text-slate-900">
                        {rebano.borregas_reproductoras ?? 0}
                    </div>
                    <p className="mt-1 text-xs text-slate-500">{rebano.sementales ?? 0} semental(es)</p>
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-xs font-medium text-slate-500">Gestaciones activas</p>
                    <div className="mt-2 text-2xl font-semibold text-purple-700">
                        {rebano.gestaciones_activas ?? 0}
                    </div>
                    <p className="mt-1 text-xs text-slate-500">
                        {rebano.partos_proximos ?? 0} parto(s) en 21 días
                    </p>
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-xs font-medium text-slate-500">Crías del periodo</p>
                    <div className="mt-2 text-2xl font-semibold text-emerald-700">
                        {rebano.crias_periodo ?? 0}
                    </div>
                    <p className="mt-1 text-xs text-slate-500">
                        Prolificidad: {rebano.prolificidad ?? '—'}
                    </p>
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-4">
                    <p className="text-xs font-medium text-slate-500">Precio estimado del rebaño</p>
                    <div className="mt-2 text-2xl font-semibold text-emerald-700">
                        {formatMXN(rebano.precio_estimado)}
                    </div>
                    <p className="mt-1 text-xs text-slate-500">
                        {rebano.sin_identificador ?? 0} sin identificar
                    </p>
                </div>
            </div>

            {/* Trabajo pendiente en el rebaño */}
            {alertasOperativas.length > 0 && (
                <div className="rounded-2xl border border-slate-200 bg-white p-5 space-y-3">
                    <div>
                        <h2 className="text-sm font-semibold text-slate-900">Trabajo pendiente en el rebaño</h2>
                        <p className="text-xs text-slate-500">Actividades operativas que requieren tu atención</p>
                    </div>

                    <div className="space-y-2">
                        {alertasOperativas.map((a, i) => (
                            <div
                                key={i}
                                className={`flex items-start justify-between gap-3 rounded-xl border px-4 py-3 ${colorNivel[a.nivel]}`}
                            >
                                <div className="min-w-0">
                                    <p className="text-sm font-semibold">
                                        {a.titulo} ({a.cantidad})
                                    </p>
                                    <p className="text-xs opacity-90">{a.detalle}</p>
                                </div>
                                {a.ruta && (
                                    <a href={a.ruta} className="text-xs underline shrink-0 self-center">Ir →</a>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* Costos y utilidad del mes */}
            <div className="rounded-2xl border border-slate-200 bg-white p-5 space-y-4">
                <div>
                    <h2 className="text-sm font-semibold text-slate-900">
                        Costos y Utilidad (este mes)
                    </h2>
                    <p className="text-xs text-slate-500">
                        Comparación de ingresos por ventas contra costos registrados
                    </p>
                </div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div className="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                        <p className="text-xs font-medium text-slate-500">Ingresos por ventas</p>
                        <div className="mt-2 text-xl font-semibold text-emerald-700">
                            {formatMXN(costos.ingresos)}
                        </div>
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                        <p className="text-xs font-medium text-slate-500">Costos totales</p>
                        <div className="mt-2 text-xl font-semibold text-red-600">
                            {formatMXN(costos.costos)}
                        </div>
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                        <p className="text-xs font-medium text-slate-500">
                            {costos.estado === 'utilidad' ? 'Utilidad' : 'Pérdida'}
                        </p>
                        <div className={`mt-2 text-xl font-semibold ${costos.estado === 'utilidad' ? 'text-emerald-700' : 'text-red-600'}`}>
                            {formatMXN(costos.utilidad)}
                        </div>
                    </div>
                </div>
            </div>

            {/* Distribución por especie */}
            <div className="rounded-2xl border border-slate-200 bg-white p-5 space-y-4">
                <div>
                    <h2 className="text-sm font-semibold text-slate-900">
                        Distribución de Animales por Especie
                    </h2>
                    <p className="text-xs text-slate-500">
                        Total de animales activos en la granja
                    </p>
                </div>

                <div className="grid grid-cols-2 gap-3 md:grid-cols-6">
                    {speciesDistribution.map((item) => (
                        <div
                            key={item.name}
                            className="flex flex-col items-center justify-center rounded-2xl border border-slate-200 bg-slate-50/60 px-3 py-3 text-center"
                        >
                            <div className="text-2xl mb-1">{item.icon}</div>
                            <div className="text-lg font-semibold text-emerald-700">
                                {item.value}
                            </div>
                            <div className="text-xs text-slate-500">{item.name}</div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Producción mensual */}
            <div className="rounded-2xl border border-slate-200 bg-white p-5 space-y-4">
                <div>
                    <h2 className="text-sm font-semibold text-slate-900">
                        Producción Mensual
                    </h2>
                    <p className="text-xs text-slate-500">
                        Leche (litros), Huevos (docenas), Carne (kg)
                    </p>
                </div>

                <div className="h-72">
                    <ResponsiveContainer width="100%" height="100%">
                        <BarChart
                            data={productionByMonth}
                            margin={{ top: 10, right: 20, left: 0, bottom: 0 }}
                        >
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey="month" />
                            <YAxis />
                            <Tooltip />
                            <Legend />
                            <Bar dataKey="milk" name="Leche" />
                            <Bar dataKey="eggs" name="Huevos" />
                            <Bar dataKey="meat" name="Carne" />
                        </BarChart>
                    </ResponsiveContainer>
                </div>
            </div>

            {/* Alertas recientes */}
            <div className="rounded-2xl border border-slate-200 bg-white p-5 space-y-4 mb-10">
                <div>
                    <h2 className="text-sm font-semibold text-slate-900">
                        Alertas Recientes
                    </h2>
                    <p className="text-xs text-slate-500">
                        Acciones que requieren tu atención
                    </p>
                </div>

                <div className="space-y-3">
                    {alerts.map((alert, index) => {
                        const base =
                            alert.type === "danger"
                                ? "bg-red-50 border-red-100"
                                : alert.type === "warning"
                                ? "bg-amber-50 border-amber-100"
                                : "bg-emerald-50 border-emerald-100";

                        const badgeColor =
                            alert.type === "danger"
                                ? "bg-red-600 text-white"
                                : alert.type === "warning"
                                ? "bg-amber-500 text-white"
                                : "bg-emerald-500 text-white";

                        return (
                            <div
                                key={index}
                                className={`flex items-center justify-between rounded-2xl border px-4 py-3 ${base}`}
                            >
                                <div>
                                    <p className="text-sm font-semibold text-slate-800">
                                        {alert.title}
                                    </p>
                                    <p className="text-xs text-slate-500">
                                        {alert.subtitle}
                                    </p>
                                </div>
                                <span
                                    className={`rounded-full px-3 py-1 text-xs font-medium ${badgeColor}`}
                                >
                                    {alert.badge}
                                </span>
                            </div>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}
