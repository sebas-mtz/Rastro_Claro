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
    } = usePage().props;
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
