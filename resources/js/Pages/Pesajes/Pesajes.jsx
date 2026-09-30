import React, { useState } from "react";
import { usePage } from "@inertiajs/react";
import AppLayout from "@/Layouts/AppLayout";
import { Scale, TrendingUp } from "lucide-react";
import TabPesajes from "./TabPesajes";
import TabGanancia from "./TabGanancia";

function Pesajes() {
    const { animales = [], flash = {} } = usePage().props;
    const [tab, setTab] = useState("animales");

    return (
        <div className="py-8 px-6 max-w-7xl mx-auto">
            {/* ENCABEZADO */}
            <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <h2 className="text-2xl font-bold text-gray-800">Pesajes</h2>
                    <p className="mt-1 text-gray-600">
                        Registro y seguimiento del peso de tus animales a lo largo del tiempo.
                    </p>
                </div>

                <div className="flex flex-wrap gap-3 mt-5">
                    <button
                        type="button"
                        onClick={() => setTab("animales")}
                        className="h-10 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-medium px-4 rounded-lg flex items-center gap-2 transition"
                    >
                        <Scale size={18} className="text-blue-600" />
                        Animales
                    </button>

                    <button
                        type="button"
                        onClick={() => setTab("ganancia")}
                        className="h-10 bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 rounded-lg flex items-center gap-2 transition"
                    >
                        <TrendingUp size={18} />
                        Ganancia por período
                    </button>
                </div>
            </div>

            {/* TABS */}
            <div className="flex gap-6 border-b mt-2 pt-2 pb-4 text-gray-600 overflow-x-auto">
                {[
                    { key: "animales", label: "Animales", Icon: Scale },
                    { key: "ganancia", label: "Ganancia por período", Icon: TrendingUp },
                ].map(({ key, label, Icon }) => (
                    <button
                        key={key}
                        type="button"
                        onClick={() => setTab(key)}
                        className={`flex items-center gap-2 pb-2 whitespace-nowrap transition ${
                            tab === key
                                ? "border-b-2 border-blue-600 text-blue-600 font-semibold"
                                : "hover:text-blue-600"
                        }`}
                    >
                        <Icon size={17} />
                        {label}
                    </button>
                ))}
            </div>

            {/* ════════════ Tab: Animales ════════════ */}
            {tab === "animales" && (
                <TabPesajes animales={animales} setTab={setTab} />
            )}

            {/* ════════════ Tab: Ganancia por período ════════════ */}
            {tab === "ganancia" && <TabGanancia animales={animales} />}
        </div>
    );
}

Pesajes.layout = (page) => <AppLayout>{page}</AppLayout>;
export default Pesajes;