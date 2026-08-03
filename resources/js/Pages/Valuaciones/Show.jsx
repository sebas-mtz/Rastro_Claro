import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import {
    ArrowLeft, RefreshCw, Sliders, FileText, BadgeCheck,
    Info, AlertTriangle, PawPrint,
} from 'lucide-react';
import DesgloseCostos from './DesgloseCostos';
import HistorialPrecio from './HistorialPrecio';
import LineaTiempo from './LineaTiempo';
import SimuladorModal from './SimuladorModal';
import ConfirmarVentaModal from './ConfirmarVentaModal';
import { formatMXN } from '@/utils/currency';

function calcularEdad(fechaNac) {
    if (!fechaNac) return 'N/D';
    const nacimiento = new Date(fechaNac);
    const hoy = new Date();
    const meses = (hoy.getFullYear() - nacimiento.getFullYear()) * 12 + (hoy.getMonth() - nacimiento.getMonth());
    if (meses < 24) return `${meses} mes${meses !== 1 ? 'es' : ''}`;
    return `${Math.floor(meses / 12)} año${Math.floor(meses / 12) !== 1 ? 's' : ''}`;
}

function Dato({ label, value }) {
    return (
        <div>
            <dt className="text-xs text-slate-400">{label}</dt>
            <dd className="text-sm text-slate-800 font-medium">{value || '—'}</dd>
        </div>
    );
}

export default function ValuacionShow({
    auth, animal, valuacion, calculo, historial, lineaTiempo,
    estadosReproductivos, tiposMovimiento, filtrosHistorial,
    puedeMargenExtendido, esVendido,
}) {
    const [showSimulador, setShowSimulador] = useState(false);
    const [showConfirmar, setShowConfirmar] = useState(false);
    const [recalculando, setRecalculando] = useState(false);

    const recalcular = () => {
        setRecalculando(true);
        router.post(route('valuaciones.recalcular', animal.id), {}, {
            preserveScroll: true,
            onFinish: () => setRecalculando(false),
        });
    };

    const utilidad = valuacion?.precio_real_venta != null
        ? Number(valuacion.precio_real_venta) - Number(valuacion.costo_total_produccion)
        : null;

    const porcentajeUtilidad = utilidad != null && Number(valuacion.costo_total_produccion) > 0
        ? (utilidad / Number(valuacion.costo_total_produccion)) * 100
        : null;

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800">Valuación y cotización</h2>}
        >
            <Head title={`Valuación — ${animal.arete}`} />

            <div className="py-8 px-4 sm:px-6 max-w-6xl mx-auto space-y-6">
                {/* Encabezado */}
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <PawPrint className="w-7 h-7 text-emerald-600" />
                        <div>
                            <h1 className="text-2xl font-semibold text-slate-900">
                                {animal.alias || animal.arete}
                            </h1>
                            <p className="text-sm text-slate-500">
                                {animal.especie} {animal.raza ? `· ${animal.raza}` : ''} · Arete {animal.arete}
                            </p>
                        </div>
                    </div>

                    <a
                        href={`/animales/${animal.id}`}
                        className="flex items-center text-sm text-emerald-700 hover:text-emerald-800 transition"
                    >
                        <ArrowLeft className="w-4 h-4 mr-1" /> Volver a la ficha
                    </a>
                </div>

                {/* Aviso obligatorio */}
                <div className="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 flex gap-2 text-sm text-amber-800">
                    <Info className="w-4 h-4 shrink-0 mt-0.5" />
                    <p>
                        Este resultado es una <strong>estimación interna</strong> del precio, basada en los costos
                        registrados, la genética y el estado reproductivo del animal.
                        No es un precio de mercado garantizado.
                    </p>
                </div>

                {esVendido && (
                    <div className="bg-slate-100 border border-slate-300 rounded-xl px-4 py-3 flex gap-2 text-sm text-slate-700">
                        <BadgeCheck className="w-4 h-4 shrink-0 mt-0.5" />
                        <p>Este animal ya fue vendido; su cotización quedó cerrada y no admite cambios nuevos.</p>
                    </div>
                )}

                {/* Avisos del cálculo */}
                {calculo?.avisos?.length > 0 && (
                    <div className="bg-white border border-slate-200 rounded-xl px-4 py-3 space-y-1">
                        {calculo.avisos.map((aviso, i) => (
                            <p key={i} className="text-xs text-slate-500 flex gap-2">
                                <AlertTriangle className="w-3.5 h-3.5 shrink-0 mt-0.5 text-amber-500" />
                                {aviso}
                            </p>
                        ))}
                    </div>
                )}

                {/* Resumen */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <p className="text-xs font-medium text-slate-500">Costo total de producción</p>
                        <p className="mt-2 text-xl font-semibold text-slate-900 tabular-nums">
                            {formatMXN(calculo?.costo_total_produccion)}
                        </p>
                    </div>
                    <div className="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                        <p className="text-xs font-medium text-emerald-700">Precio estimado</p>
                        <p className="mt-2 text-xl font-bold text-emerald-700 tabular-nums">
                            {formatMXN(calculo?.precio_estimado)}
                        </p>
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <p className="text-xs font-medium text-slate-500">Precio publicado</p>
                        <p className="mt-2 text-xl font-semibold text-slate-900 tabular-nums">
                            {valuacion?.precio_publicado != null ? formatMXN(valuacion.precio_publicado) : '—'}
                        </p>
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-white p-4">
                        <p className="text-xs font-medium text-slate-500">
                            {utilidad != null ? (utilidad >= 0 ? 'Utilidad' : 'Pérdida') : 'Precio real de venta'}
                        </p>
                        <p className={`mt-2 text-xl font-semibold tabular-nums ${
                            utilidad == null ? 'text-slate-400'
                                : utilidad >= 0 ? 'text-emerald-700' : 'text-red-600'
                        }`}>
                            {utilidad != null ? formatMXN(utilidad) : 'Sin vender'}
                        </p>
                        {porcentajeUtilidad != null && (
                            <p className="text-xs text-slate-500 mt-0.5">
                                {porcentajeUtilidad.toFixed(2)} % sobre el costo
                            </p>
                        )}
                    </div>
                </div>

                {/* Acciones */}
                <div className="flex flex-wrap gap-2">
                    <button
                        onClick={recalcular}
                        disabled={recalculando || esVendido}
                        className="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <RefreshCw className={`w-4 h-4 ${recalculando ? 'animate-spin' : ''}`} />
                        {recalculando ? 'Recalculando...' : 'Recalcular'}
                    </button>
                    <button
                        onClick={() => setShowSimulador(true)}
                        disabled={esVendido}
                        className="bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <Sliders className="w-4 h-4" /> Simular cotización
                    </button>
                    <button
                        onClick={() => setShowConfirmar(true)}
                        disabled={!valuacion}
                        className="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <BadgeCheck className="w-4 h-4" /> Confirmar precio de venta
                    </button>
                    <a
                        href={route('valuaciones.pdf', animal.id)}
                        className="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium px-4 py-2 rounded-lg flex items-center gap-2 transition"
                    >
                        <FileText className="w-4 h-4" /> Exportar PDF
                    </a>
                </div>

                {/* Datos generales */}
                <div className="bg-white rounded-2xl border border-slate-200 p-5">
                    <h2 className="text-sm font-semibold text-slate-900 mb-4">Datos del animal</h2>
                    <div className="flex flex-col sm:flex-row gap-6">
                        {animal.imagen ? (
                            <img
                                src={`/storage/${animal.imagen}`}
                                alt={`Fotografía de ${animal.alias || animal.arete}`}
                                className="w-32 h-32 rounded-xl object-cover border border-slate-200 shrink-0"
                            />
                        ) : (
                            <div className="w-32 h-32 rounded-xl border-2 border-dashed border-slate-200 flex items-center justify-center text-slate-300 shrink-0">
                                <PawPrint className="w-10 h-10" />
                            </div>
                        )}

                        <dl className="grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-3 flex-1">
                            <Dato label="Arete" value={animal.arete} />
                            <Dato label="Alias" value={animal.alias} />
                            <Dato label="Raza" value={animal.raza} />
                            <Dato label="Edad" value={calcularEdad(animal.fecha_nac)} />
                            <Dato label="Sexo" value={animal.sexo === 'M' ? 'Macho' : 'Hembra'} />
                            <Dato label="Lote" value={animal.lote?.nombre} />
                            <Dato label="Madre" value={animal.madre?.arete} />
                            <Dato
                                label="Padre"
                                value={animal.padre?.arete || animal.padre_externo?.nombre}
                            />
                            <Dato label="Microchip / QR" value={animal.microchip_codigo} />
                            <Dato
                                label="Estado reproductivo"
                                value={calculo?.estado_reproductivo_valuacion?.replaceAll('_', ' ')}
                            />
                            <Dato label="N° de registro" value={animal.genetica?.numero_registro} />
                            <Dato
                                label="Pureza"
                                value={animal.genetica?.porcentaje_pureza != null
                                    ? `${Number(animal.genetica.porcentaje_pureza).toFixed(2)} %`
                                    : null}
                            />
                        </dl>
                    </div>
                </div>

                {/* Desglose */}
                <DesgloseCostos calculo={calculo} />

                {/* Línea de tiempo + historial */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <LineaTiempo hitos={lineaTiempo} precioEstimado={calculo?.precio_estimado} />
                    <HistorialPrecio
                        animal={animal}
                        historial={historial}
                        tiposMovimiento={tiposMovimiento}
                        filtros={filtrosHistorial}
                    />
                </div>
            </div>

            <SimuladorModal
                show={showSimulador}
                onClose={() => setShowSimulador(false)}
                animal={animal}
                calculo={calculo}
                estadosReproductivos={estadosReproductivos}
                puedeMargenExtendido={puedeMargenExtendido}
            />

            <ConfirmarVentaModal
                show={showConfirmar}
                onClose={() => setShowConfirmar(false)}
                animal={animal}
                valuacion={valuacion}
            />
        </AuthenticatedLayout>
    );
}
