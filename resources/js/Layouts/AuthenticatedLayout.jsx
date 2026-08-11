import React from "react";
import { usePage } from "@inertiajs/react";
import Sidebar from "@/Components/Sidebar";

export default function AuthenticatedLayout({ header, children }) {
    const { auth } = usePage().props;

    return (
        <div className="app-frame">

            {/* 🔹 SIDEBAR */}
            <Sidebar user={auth.user} />

            {/* 🔹 CONTENIDO PRINCIPAL */}
            <div className="app-content flex flex-col">

                {/* HEADER (OPCIONAL) */}
                {header && (
                    <header className="bg-white shadow dark:bg-slate-900">
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
