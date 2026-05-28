import { Head, router, usePage, useForm } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import {
    DollarSign,
    PlusCircle,
    Trash2,
    Wheat,
    Heart,
    Settings,
    MoreHorizontal,
    TrendingUp,
    ChevronDown,
    X,
    CheckCircle,
} from 'lucide-react';

// ─── Colores por categoría ─────────────────────────────────────────────────
const CAT_CONFIG = {
    alimentacion: { label: 'Alimentación', icon: Wheat,        color: 'bg-amber-50 border-amber-400',  badge: 'bg-amber-100 text-amber-800',  dot: '#f59e0b' },
    salud:        { label: 'Salud',        icon: Heart,        color: 'bg-rose-50 border-rose-400',    badge: 'bg-rose-100 text-rose-800',    dot: '#f43f5e' },
    manejo:       { label: 'Manejo',       icon: Settings,     color: 'bg-sky-50 border-sky-400',      badge: 'bg-sky-100 text-sky-800',      dot: '#0ea5e9' },
    otros:        { label: 'Otros',        icon: MoreHorizontal,color:'bg-violet-50 border-violet-400',badge: 'bg-violet-100 text-violet-800',dot: '#8b5cf6' },
};

// ─── Gráfica de barras simple (SVG) ────────────────────────────────────────
function BarChart({ data, labelKey, valueKey, colorFn }) {
    if (!data || data.length === 0) {
        return <p className="text-center text-gray-400 text-sm py-8">Sin datos</p>;
    }
    const max = Math.max(...data.map(d => d[valueKey]), 1);
    return (
        <div className="flex items-end gap-2 h-40 px-2">
            {data.map((d, i) => {
                const pct = (d[valueKey] / max) * 100;
                return (
                    <div key={i} className="flex flex-col items-center flex-1 min-w-0">
                        <span className="text-[10px] text-gray-500 mb-1 truncate w-full text-center">
                            ${d[valueKey].toLocaleString('es-MX', { maximumFractionDigits: 0 })}
                        </span>
                        <div
                            className="w-full rounded-t-md transition-all"
                            style={{
                                height: `${Math.max(pct, 3)}%`,
                                backgroundColor: colorFn ? colorFn(d, i) : '#10b981',
                            }}
                        />
                        <span className="text-[9px] text-gray-500 mt-1 truncate w-full text-center">
                            {d[labelKey]}
                        </span>
                    </div>
                );
            })}
        </div>
    );
}

// ─── Gráfica de línea simple (SVG) ─────────────────────────────────────────
function LineChart({ data }) {
    if (!data || data.length < 2) {
        return <p className="text-center text-gray-400 text-sm py-8">
            {data?.length === 1 ? 'Solo hay datos de un mes' : 'Sin datos suficientes'}
        </p>;
    }
    const W = 500, H = 120, PAD = 30;
    const max = Math.max(...data.map(d => d.total), 1);
    const pts = data.map((d, i) => {
        const x = PAD + (i / (data.length - 1)) * (W - PAD * 2);
        const y = H - PAD - ((d.total / max) * (H - PAD * 2));
        return { x, y, ...d };
    });
    const path = pts.map((p, i) => `${i === 0 ? 'M' : 'L'}${p.x},${p.y}`).join(' ');
    return (
        <svg viewBox={`0 0 ${W} ${H}`} className="w-full h-auto">
            <polyline fill="none" stroke="#10b981" strokeWidth="2.5" points={pts.map(p => `${p.x},${p.y}`).join(' ')} />
            {pts.map((p, i) => (
                <g key={i}>
                    <circle cx={p.x} cy={p.y} r="4" fill="#10b981" />
                    <text x={p.x} y={H - 4} textAnchor="middle" fontSize="9" fill="#6b7280">{p.mes}</text>
                </g>
            ))}
        </svg>
    );
}

// ─── Modal formulario de nuevo gasto ───────────────────────────────────────
function ModalNuevoGasto({ isOpen, onClose, animales, animalId, etapas, categorias }) {
    const { data, setData, post, processing, reset, errors } = useForm({
        animal_id:    animalId ? String(animalId) : '',
        fecha:        new Date().toISOString().slice(0, 10),
        etapa:        'adulta_mantenimiento',
        categoria:    'alimentacion',
        concepto:     '',
        costo:        '',
        observaciones:'',
        num_dias:     '',
        es_por_dia:   false,
    });

    function submit(e) {
        e.preventDefault();
        post(route('costos.store'), {
            preserveScroll: true,
            onSuccess: () => { reset(); onClose(); },
        });
    }

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div className="flex items-center justify-between px-6 py-4 border-b">
                    <h2 className="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <PlusCircle className="w-5 h-5 text-emerald-600" />
                        Registrar Gasto
                    </h2>
                    <button onClick={onClose} className="text-gray-400 hover:text-gray-600">
                        <X className="w-5 h-5" />
                    </button>
                </div>

                <form onSubmit={submit} className="px-6 py-4 space-y-4">
                    {/* Borrega */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Borrega *</label>
                        <select
                            value={data.animal_id}
                            onChange={e => setData('animal_id', e.target.value)}
                            className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
                        >
                            <option value="">-- Selecciona --</option>
                            {animales.map(a => (
                                <option key={a.id} value={a.id}>{a.label}</option>
                            ))}
                        </select>
                        {errors.animal_id && <p className="text-red-500 text-xs mt-1">{errors.animal_id}</p>}
                    </div>

                    {/* Fecha */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                        <input
                            type="date"
                            value={data.fecha}
                            onChange={e => setData('fecha', e.target.value)}
                            className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
                        />
                        {errors.fecha && <p className="text-red-500 text-xs mt-1">{errors.fecha}</p>}
                    </div>

                    {/* Etapa */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Etapa *</label>
                        <select
                            value={data.etapa}
                            onChange={e => setData('etapa', e.target.value)}
                            className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
                        >
                            {Object.entries(etapas).map(([k, v]) => (
                                <option key={k} value={k}>{v}</option>
                            ))}
                        </select>
                    </div>

                    {/* Categoría */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Categoría *</label>
                        <div className="grid grid-cols-2 gap-2">
                            {Object.entries(CAT_CONFIG).map(([k, c]) => {
                                const Icon = c.icon;
                                return (
                                    <button
                                        key={k}
                                        type="button"
                                        onClick={() => setData('categoria', k)}
                                        className={`flex items-center gap-2 px-3 py-2 rounded-lg border-2 text-sm font-medium transition-all ${
                                            data.categoria === k
                                                ? 'border-emerald-500 bg-emerald-50 text-emerald-700'
                                                : 'border-gray-200 text-gray-600 hover:border-gray-300'
                                        }`}
                                    >
                                        <Icon className="w-4 h-4" />
                                        {c.label}
                                    </button>
                                );
                            })}
                        </div>
                    </div>

                    {/* Concepto */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Concepto *</label>
                        <input
                            type="text"
                            value={data.concepto}
                            onChange={e => setData('concepto', e.target.value)}
                            placeholder="Ej: Alfalfa, Antibiótico, Sal mineral..."
                            className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
                        />
                        {errors.concepto && <p className="text-red-500 text-xs mt-1">{errors.concepto}</p>}
                    </div>

                    {/* Costo */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                            {data.es_por_dia ? 'Costo por día *' : 'Costo total *'}
                        </label>
                        <div className="flex gap-2">
                            <div className="relative flex-1">
                                <span className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={data.costo}
                                    onChange={e => setData('costo', e.target.value)}
                                    placeholder="0.00"
                                    className="w-full border border-gray-300 rounded-lg pl-7 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
                                />
                            </div>
                            <button
                                type="button"
                                onClick={() => setData('es_por_dia', !data.es_por_dia)}
                                className={`px-3 py-2 rounded-lg border text-xs font-medium transition-all ${
                                    data.es_por_dia
                                        ? 'bg-emerald-600 border-emerald-600 text-white'
                                        : 'border-gray-300 text-gray-600 hover:bg-gray-50'
                                }`}
                            >
                                Por día
                            </button>
                        </div>
                        {errors.costo && <p className="text-red-500 text-xs mt-1">{errors.costo}</p>}
                    </div>

                    {/* Número de días (solo si es_por_dia) */}
                    {data.es_por_dia && (
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Número de días *</label>
                            <input
                                type="number"
                                min="1"
                                value={data.num_dias}
                                onChange={e => setData('num_dias', e.target.value)}
                                placeholder="Ej: 30"
                                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
                            />
                            {data.costo && data.num_dias && (
                                <p className="text-emerald-600 text-xs mt-1">
                                    Total: ${(parseFloat(data.costo || 0) * parseInt(data.num_dias || 0)).toLocaleString('es-MX', { minimumFractionDigits: 2 })}
                                </p>
                            )}
                        </div>
                    )}

                    {/* Observaciones */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                        <textarea
                            value={data.observaciones}
                            onChange={e => setData('observaciones', e.target.value)}
                            rows={2}
                            placeholder="Notas adicionales..."
                            className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 resize-none"
                        />
                    </div>

                    <div className="flex gap-3 pt-2">
                        <button
                            type="button"
                            onClick={onClose}
                            className="flex-1 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="flex-1 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 disabled:opacity-50"
                        >
                            {processing ? 'Guardando...' : 'Guardar Gasto'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

// ─── Tarjeta de total ────────────────────────────────────────────────────────
function TarjetaTotal({ label, valor, icon: Icon, color, textColor }) {
    return (
        <div className={`rounded-xl border-l-4 p-4 ${color} flex items-center justify-between`}>
            <div>
                <p className="text-xs text-gray-500 font-medium uppercase tracking-wide">{label}</p>
                <p className={`text-xl font-bold ${textColor}`}>
                    ${valor.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                </p>
            </div>
            <Icon className={`w-8 h-8 ${textColor} opacity-60`} />
        </div>
    );
}

// ─── Componente principal ────────────────────────────────────────────────────
export default function CostosIndex({
    animales = [],
    animalId = null,
    animalInfo = null,
    costos = [],
    totales = {},
    totalGeneral = 0,
    porEtapa = [],
    porCategoria = [],
    porFecha = [],
    etapas = {},
    categorias = {},
}) {
    const { flash } = usePage().props || {};
    const [modalOpen, setModalOpen] = useState(false);
    const [filtroCat, setFiltroCat] = useState('todos');
    const [confirmarEliminar, setConfirmarEliminar] = useState(null);

    function seleccionarAnimal(id) {
        router.get(route('costos.index'), { animal_id: id }, { preserveScroll: false });
    }

    function eliminarCosto(id) {
        router.delete(route('costos.destroy', id), {
            preserveScroll: true,
            onSuccess: () => setConfirmarEliminar(null),
        });
    }

    const costosFiltrados = useMemo(() => {
        if (filtroCat === 'todos') return costos;
        return costos.filter(c => c.categoria === filtroCat);
    }, [costos, filtroCat]);

    return (
        <>
            <Head title="Control de Costos" />

            <div className="py-8 px-4 max-w-7xl mx-auto space-y-6">

                {/* ── ENCABEZADO ───────────────────────────────────────────── */}
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-800 flex items-center gap-2">
                            <DollarSign className="w-7 h-7 text-emerald-600" />
                            Control de Costos
                        </h1>
                        <p className="text-sm text-gray-500 mt-0.5">
                            Gastos reales por borrega — alimentación, salud, manejo y más
                        </p>
                    </div>
                    <button
                        onClick={() => setModalOpen(true)}
                        className="flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700 shadow-sm"
                    >
                        <PlusCircle className="w-4 h-4" />
                        Registrar Gasto
                    </button>
                </div>

                {/* ── FLASH ────────────────────────────────────────────────── */}
                {flash?.success && (
                    <div className="flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">
                        <CheckCircle className="w-4 h-4 flex-shrink-0" />
                        {flash.success}
                    </div>
                )}

                {/* ── SELECTOR DE BORREGA ──────────────────────────────────── */}
                <div className="bg-white rounded-2xl shadow-sm border p-4">
                    <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        Seleccionar Borrega
                    </label>
                    {animales.length === 0 ? (
                        <p className="text-gray-400 text-sm">No hay animales registrados.</p>
                    ) : (
                        <div className="relative">
                            <select
                                value={animalId || ''}
                                onChange={e => seleccionarAnimal(e.target.value)}
                                className="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm appearance-none bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-400 pr-10"
                            >
                                {animales.map(a => (
                                    <option key={a.id} value={a.id}>{a.label}</option>
                                ))}
                            </select>
                            <ChevronDown className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                        </div>
                    )}
                </div>

                {animalId && (
                    <>
                        {/* ── TARJETAS DE TOTALES ──────────────────────────────── */}
                        <div className="grid grid-cols-2 lg:grid-cols-5 gap-3">
                            <TarjetaTotal
                                label="Alimentación"
                                valor={totales.alimentacion || 0}
                                icon={Wheat}
                                color="bg-amber-50 border-amber-400"
                                textColor="text-amber-700"
                            />
                            <TarjetaTotal
                                label="Salud"
                                valor={totales.salud || 0}
                                icon={Heart}
                                color="bg-rose-50 border-rose-400"
                                textColor="text-rose-700"
                            />
                            <TarjetaTotal
                                label="Manejo"
                                valor={totales.manejo || 0}
                                icon={Settings}
                                color="bg-sky-50 border-sky-400"
                                textColor="text-sky-700"
                            />
                            <TarjetaTotal
                                label="Otros"
                                valor={totales.otros || 0}
                                icon={MoreHorizontal}
                                color="bg-violet-50 border-violet-400"
                                textColor="text-violet-700"
                            />
                            <div className="col-span-2 lg:col-span-1 rounded-xl border-l-4 border-emerald-500 bg-emerald-50 p-4 flex items-center justify-between lg:col-start-5">
                                <div>
                                    <p className="text-xs text-gray-500 font-medium uppercase tracking-wide">Total General</p>
                                    <p className="text-xl font-bold text-emerald-700">
                                        ${totalGeneral.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                    </p>
                                </div>
                                <TrendingUp className="w-8 h-8 text-emerald-600 opacity-60" />
                            </div>
                        </div>

                        {/* ── GRÁFICAS ─────────────────────────────────────────── */}
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {/* Por etapa */}
                            <div className="bg-white rounded-2xl shadow-sm border p-4">
                                <h3 className="text-sm font-semibold text-gray-700 mb-3">Gasto por Etapa</h3>
                                <BarChart
                                    data={porEtapa}
                                    labelKey="etapa"
                                    valueKey="total"
                                    colorFn={(_, i) => {
                                        const colors = ['#10b981','#3b82f6','#f59e0b','#f43f5e','#8b5cf6','#06b6d4','#84cc16','#ec4899'];
                                        return colors[i % colors.length];
                                    }}
                                />
                            </div>

                            {/* Por categoría */}
                            <div className="bg-white rounded-2xl shadow-sm border p-4">
                                <h3 className="text-sm font-semibold text-gray-700 mb-3">Gasto por Categoría</h3>
                                <BarChart
                                    data={porCategoria}
                                    labelKey="categoria"
                                    valueKey="total"
                                    colorFn={(d) => {
                                        const key = Object.keys(CAT_CONFIG).find(k => CAT_CONFIG[k].label === d.categoria);
                                        return key ? CAT_CONFIG[key].dot : '#10b981';
                                    }}
                                />
                            </div>

                            {/* Por fecha */}
                            <div className="bg-white rounded-2xl shadow-sm border p-4">
                                <h3 className="text-sm font-semibold text-gray-700 mb-3">Gasto por Mes</h3>
                                <LineChart data={porFecha} />
                            </div>
                        </div>

                        {/* ── HISTORIAL ────────────────────────────────────────── */}
                        <div className="bg-white rounded-2xl shadow-sm border">
                            <div className="flex items-center justify-between px-6 py-4 border-b flex-wrap gap-3">
                                <h3 className="text-base font-bold text-gray-800">
                                    Historial de Gastos
                                    {animalInfo && (
                                        <span className="ml-2 text-sm font-normal text-gray-500">{animalInfo.label}</span>
                                    )}
                                </h3>
                                {/* Filtro por categoría */}
                                <div className="flex gap-1.5 flex-wrap">
                                    {['todos', 'alimentacion', 'salud', 'manejo', 'otros'].map(cat => (
                                        <button
                                            key={cat}
                                            onClick={() => setFiltroCat(cat)}
                                            className={`px-3 py-1 rounded-full text-xs font-medium transition-all ${
                                                filtroCat === cat
                                                    ? 'bg-emerald-600 text-white'
                                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                            }`}
                                        >
                                            {cat === 'todos' ? 'Todos' : CAT_CONFIG[cat]?.label}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            {costosFiltrados.length === 0 ? (
                                <div className="text-center py-12 text-gray-400">
                                    <DollarSign className="w-10 h-10 mx-auto mb-2 opacity-40" />
                                    <p className="text-sm">No hay gastos registrados{filtroCat !== 'todos' ? ' en esta categoría' : ''}.</p>
                                    <p className="text-xs mt-1">
                                        {filtroCat !== 'todos'
                                            ? 'Prueba otro filtro o registra un gasto.'
                                            : 'Usa el botón "Registrar Gasto" para empezar.'}
                                    </p>
                                </div>
                            ) : (
                                <div className="divide-y">
                                    {costosFiltrados.map(c => {
                                        const cfg = CAT_CONFIG[c.categoria] || CAT_CONFIG.otros;
                                        const Icon = cfg.icon;
                                        return (
                                            <div key={c.id} className="flex items-center gap-4 px-6 py-3 hover:bg-gray-50 group">
                                                <div className={`w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 ${cfg.badge}`}>
                                                    <Icon className="w-4 h-4" />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center gap-2 flex-wrap">
                                                        <span className="text-sm font-semibold text-gray-800 truncate">{c.concepto}</span>
                                                        {c.origen !== 'manual' && (
                                                            <span className="text-[10px] bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded-full">
                                                                Importado de Salud
                                                            </span>
                                                        )}
                                                    </div>
                                                    <div className="flex items-center gap-3 mt-0.5 flex-wrap">
                                                        <span className="text-xs text-gray-400">{c.fecha}</span>
                                                        <span className={`text-[10px] px-1.5 py-0.5 rounded-full ${cfg.badge}`}>{cfg.label}</span>
                                                        <span className="text-[10px] text-gray-400">{etapas[c.etapa] || c.etapa}</span>
                                                    </div>
                                                    {c.observaciones && (
                                                        <p className="text-xs text-gray-400 mt-0.5 truncate">{c.observaciones}</p>
                                                    )}
                                                </div>
                                                <div className="flex items-center gap-3 flex-shrink-0">
                                                    <span className="text-base font-bold text-gray-800">
                                                        ${c.costo.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                    </span>
                                                    {c.origen === 'manual' && (
                                                        confirmarEliminar === c.id ? (
                                                            <div className="flex gap-1">
                                                                <button
                                                                    onClick={() => eliminarCosto(c.id)}
                                                                    className="text-xs bg-red-500 text-white px-2 py-1 rounded-lg hover:bg-red-600"
                                                                >
                                                                    Sí, eliminar
                                                                </button>
                                                                <button
                                                                    onClick={() => setConfirmarEliminar(null)}
                                                                    className="text-xs bg-gray-200 text-gray-700 px-2 py-1 rounded-lg hover:bg-gray-300"
                                                                >
                                                                    Cancelar
                                                                </button>
                                                            </div>
                                                        ) : (
                                                            <button
                                                                onClick={() => setConfirmarEliminar(c.id)}
                                                                className="opacity-0 group-hover:opacity-100 text-gray-300 hover:text-red-500 transition-opacity"
                                                            >
                                                                <Trash2 className="w-4 h-4" />
                                                            </button>
                                                        )
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}

                            {/* Subtotal visible al filtrar */}
                            {filtroCat !== 'todos' && costosFiltrados.length > 0 && (
                                <div className="px-6 py-3 bg-gray-50 border-t flex justify-between items-center">
                                    <span className="text-sm text-gray-500">
                                        Subtotal ({CAT_CONFIG[filtroCat]?.label})
                                    </span>
                                    <span className="text-base font-bold text-gray-800">
                                        ${costosFiltrados.reduce((s, c) => s + c.costo, 0)
                                            .toLocaleString('es-MX', { minimumFractionDigits: 2 })}
                                    </span>
                                </div>
                            )}
                        </div>
                    </>
                )}
            </div>

            {/* ── MODAL ──────────────────────────────────────────────────────── */}
            <ModalNuevoGasto
                isOpen={modalOpen}
                onClose={() => setModalOpen(false)}
                animales={animales}
                animalId={animalId}
                etapas={etapas}
                categorias={categorias}
            />
        </>
    );
}

CostosIndex.layout = page => <AppLayout>{page}</AppLayout>;
