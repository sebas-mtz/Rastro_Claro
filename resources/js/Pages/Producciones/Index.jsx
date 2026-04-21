import React, { useState } from "react";
import { Head } from "@inertiajs/react";

// 👇 IMPORTA el layout con sidebar
import AppLayout from "@/Layouts/AppLayout";
import { Link } from "@inertiajs/react";
import Tendencias from "./Tendencias";
import Comparativo from "./Comparativo";
import Reportes from "./Reportes";
import { TrendingUp, Target, FileBarChart } from "lucide-react";

import { DollarSign, Scissors, Drumstick } from "lucide-react";

function ProduccionesIndex({
    auth,
    datos = {},
    mejores = [],
    resumen = {},
    tendencias = [],   // ← AGREGAR
    inventarioSubproductos = {},
}) {
    const [tab, setTab] = useState("tendencias");

    const cardsProduccion = [
        { tipo: "leche", nombre: "Leche Hoy", icon: "🥛", unidad: "L" },
        { tipo: "huevo", nombre: "Huevos Hoy", icon: "🥚", unidad: "unidades" },
        { tipo: "lana", nombre: "Lana Hoy", icon: "🧶", unidad: "kg" },
    ];

    const cardsSubproductos = [
        { tipo: "carne", nombre: "Carne Disponible", icon: "🍖", unidad: "kg", color: "bg-red-500" },
        { tipo: "cuero", nombre: "Cuero Disponible", icon: "🐄", unidad: "kg", color: "bg-amber-500" },
        { tipo: "grasa", nombre: "Grasa Disponible", icon: "🟡", unidad: "kg", color: "bg-yellow-500" },
        { tipo: "plumas", nombre: "Plumas Disponibles", icon: "🪶", unidad: "kg", color: "bg-gray-500" },
        { tipo: "hueso", nombre: "Hueso Disponible", icon: "🦴", unidad: "kg", color: "bg-stone-500" },
        { tipo: "visceras", nombre: "Vísceras Disponibles", icon: "🧠", unidad: "kg", color: "bg-purple-500" },
    ];

    return (
        <>
            <Head title="Módulo de Producción" />
            <div className="py-8 px-6 max-w-7xl mx-auto space-y-6">
             {/* ENCABEZADO */}       
                <div>      
                    <h1 className="text-2xl font-bold text-gray-800">Módulo de Producción</h1>
           
                    <div className="flex items-start justify-between">
                    <p className="text-gray-600">
                        Monitorea y registra la productividad de tus animales y subproductos.
                    </p>
                    
                    <div className="flex gap-3 mr-6">
                        
                    <Link
              href="/ventas"
              className="border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
            >
              <DollarSign className="w-5 h-5" />
              Ventas
            </Link>

            <Link
              href="/sacrificios"
              className="border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
            >
              <Scissors className="w-5 h-5" />
              Sacrificios
            </Link>

            <Link
              href="/faenas"
              className="border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
            >
              <Drumstick className="w-5 h-5" />
              Faena
            </Link>
                </div>     
                </div>
                </div>

                {/* CARDS HORIZONTALES */}
                <div className="relative">
                    <div
                        id="subproductos-container"
                        className="flex gap-4 overflow-x-auto pb-4 scrollbar-hide"
                        style={{ scrollBehavior: "smooth" }}
                    >
                        {/* Producción diaria */}
                        {cardsProduccion.map((card) => {
                            const valor = datos?.[card.tipo]?.total ?? datos?.[card.tipo] ?? 0;

                            return (
                                <div
                                    key={card.tipo}
                                    className="flex-shrink-0 w-64 bg-white rounded-2xl shadow p-5 border-l-4 border-blue-500"
                                >
                                    <div className="flex justify-between items-center mb-4">
                                        <span className="text-gray-700 font-medium text-sm">
                                            {card.nombre}
                                        </span>
                                        <span className="text-2xl">{card.icon}</span>
                                    </div>

                                    <div className="text-2xl font-bold text-gray-800 mb-1">
                                        {Number(valor).toFixed(1)}
                                        <span className="text-sm text-gray-400 ml-2">
                                            {card.unidad}
                                        </span>
                                    </div>

                                    <p className="text-sm text-gray-500">Producción del día / mes</p>
                                </div>
                            );
                        })}

                        {/* Subproductos de faena */}
                        {cardsSubproductos.map((card) => {
                            const cantidad = Number(inventarioSubproductos[card.tipo]) || 0;

                            return (
                                <div
                                    key={card.tipo}
                                    className="flex-shrink-0 w-64 bg-white rounded-2xl shadow p-5 border-l-4 border-green-500"
                                >
                                    <div className="flex justify-between items-center mb-4">
                                        <span className="text-gray-700 font-medium text-sm">
                                            {card.nombre}
                                        </span>
                                        <span className="text-2xl">{card.icon}</span>
                                    </div>

                                    <div className="text-2xl font-bold text-gray-800 mb-1">
                                        {cantidad.toFixed(1)}
                                        <span className="text-sm text-gray-400 ml-2">
                                            {card.unidad}
                                        </span>
                                    </div>

                                    <p className="text-sm text-gray-500 mb-3">Inventario disponible</p>

                                    <div className="mt-2 bg-gray-200 h-2 w-full rounded-full">
                                        <div
                                            className={`h-2 rounded-full transition-all duration-500 ${card.color}`}
                                            style={{
                                                width: `${Math.min(100, (cantidad / 100) * 100)}%`,
                                            }}
                                        />
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>

                {/* PESTAÑAS */}
                <div className="flex gap-6 border-b pb-3 text-gray-600">
                    <button
                        className={`flex items-center gap-2 pb-2 ${
                            tab === "tendencias"
                                ? "border-b-2 border-blue-600 text-blue-600 font-semibold"
                                : "hover:text-blue-600"
                        }`}
                        onClick={() => setTab("tendencias")}
                    >
                        <TrendingUp size={18} />
                        Tendencias
                    </button>

                    <button
                        className={`flex items-center gap-2 pb-2 ${
                            tab === "comparativo"
                                ? "border-b-2 border-blue-600 text-blue-600 font-semibold"
                                : "hover:text-blue-600"
                        }`}
                        onClick={() => setTab("comparativo")}
                    >
                        <Target size={18} />
                        Comparativo
                    </button>

                    <button
                        className={`flex items-center gap-2 pb-2 ${
                            tab === "reportes"
                                ? "border-b-2 border-blue-600 text-blue-600 font-semibold"
                                : "hover:text-blue-600"
                        }`}
                        onClick={() => setTab("reportes")}
                    >
                        <FileBarChart size={18} />
                        Reportes
                    </button>
                </div>

                {/* CONTENIDO DE LAS TABS */}
                {tab === "tendencias" && <Tendencias datos={tendencias} />}  
                {tab === "comparativo" && <Comparativo mejores={mejores} />}
                {tab === "reportes" && <Reportes resumen={resumen} />}
            </div>
        </>
    );
}

// 👇 AQUÍ SE CONECTA CON EL LAYOUT CON SIDEBAR
ProduccionesIndex.layout = (page) => (
    <AppLayout user={page.props.auth.user}>{page}</AppLayout>
);

export default ProduccionesIndex;
