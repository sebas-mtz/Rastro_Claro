import React from "react";
import { usePage } from "@inertiajs/react";
import Sidebar from "@/Components/Sidebar";

export default function AuthenticatedLayout({ header, children }) {
    const { auth } = usePage().props;

    return (
        <div className="min-h-screen bg-gray-100 flex">

            {/* 🔹 SIDEBAR */}
            <Sidebar user={auth.user} />

            {/* 🔹 CONTENIDO PRINCIPAL */}
            <div className="flex-1 flex flex-col">

                {/* HEADER (OPCIONAL) */}
                {header && (
                    <header className="bg-white shadow">
                        <div className="max-w-7xl mx-auto py-4 px-6">
                            {header}
                        </div>
                    </header>
                )}

                {/* CONTENIDO DE LA PÁGINA */}
                <main className="p-6 flex-1 overflow-y-auto">
                    {children}
                </main>
            </div>
        </div>
    );
}
