// resources/js/Pages/Genealogias/Show.jsx
import { Head, Link } from '@inertiajs/react'
import { useState } from 'react'

// ─── Helpers ──────────────────────────────────────────────────────────────────

const GEN_LABELS = ['Animal', 'Padres', 'Abuelos', 'Bisabuelos', 'Tatarabuelos']

function sexClasses(sexo) {
    if (!sexo) return { border: 'border-gray-200', bg: 'bg-gray-50', text: 'text-gray-400', dot: 'bg-gray-300' }
    return sexo === 'M'
        ? { border: 'border-sky-300', bg: 'bg-sky-50', text: 'text-sky-700', dot: 'bg-sky-400' }
        : { border: 'border-rose-300', bg: 'bg-rose-50', text: 'text-rose-600', dot: 'bg-rose-400' }
}

// ─── AnimalCard ───────────────────────────────────────────────────────────────

function AnimalCard({ animal, root = false }) {
    if (!animal) {
        return (
            <div className="border border-dashed border-gray-200 rounded-lg px-3 py-2 w-[130px] bg-white/40">
                <span className="text-[11px] text-gray-300 italic">Desconocido</span>
            </div>
        )
    }

    const c = sexClasses(animal.sexo)

    return (
        <Link
            href={route('animales.show', animal.id)}
            className={`block border ${c.border} ${c.bg} rounded-lg px-3 py-2 w-[130px]
                        hover:shadow-md hover:scale-[1.02] transition-all duration-150
                        ${root ? 'ring-2 ring-amber-400/60 ring-offset-1' : ''}`}
        >
            {/* Arete */}
            <div className={`text-[11px] font-black font-mono tracking-tight ${c.text}`}>
                {animal.arete ?? '—'}
            </div>

            {/* Alias */}
            {animal.alias && (
                <div className="text-xs text-gray-600 leading-tight mt-0.5 truncate">
                    {animal.alias}
                </div>
            )}

            {/* Raza */}
            <div className="text-[10px] text-gray-400 mt-1 leading-none">
                {animal.raza ?? '—'}
            </div>

            {/* Fecha */}
            {animal.fecha_nac && (
                <div className="text-[10px] text-gray-300 mt-0.5 leading-none">
                    {animal.fecha_nac}
                </div>
            )}
        </Link>
    )
}

// ─── PedigreeNode ─────────────────────────────────────────────────────────────
// Recursive: renders a node card + its ancestors branching to the right.
// gen=1 → padres, gen=2 → abuelos, etc.

function PedigreeNode({ nodo, gen, maxGen }) {
    const mostrarRamas = gen < maxGen

    return (
        <div className="flex items-center">
            {/* Card */}
            <div className="flex-shrink-0">
                <AnimalCard animal={nodo} />
            </div>

            {/* Connector + sub-branches */}
            {mostrarRamas && (
                <>
                    {/* Horizontal line from card to bracket */}
                    <div className="w-5 h-px bg-gray-200 flex-shrink-0" />

                    {/* Vertical bracket */}
                    <div className="relative flex flex-col">
                        {/* The vertical bar */}
                        <div
                            className="absolute left-0 bg-gray-200"
                            style={{
                                width: 1,
                                top: '25%',
                                bottom: '25%',
                            }}
                        />

                        {/* Padre branch */}
                        <div className="flex items-center py-1">
                            <div className="w-4 h-px bg-gray-200 flex-shrink-0" />
                            <PedigreeNode
                                nodo={nodo?.padre ?? null}
                                gen={gen + 1}
                                maxGen={maxGen}
                            />
                        </div>

                        {/* Madre branch */}
                        <div className="flex items-center py-1">
                            <div className="w-4 h-px bg-gray-200 flex-shrink-0" />
                            <PedigreeNode
                                nodo={nodo?.madre ?? null}
                                gen={gen + 1}
                                maxGen={maxGen}
                            />
                        </div>
                    </div>
                </>
            )}
        </div>
    )
}

// ─── PedigreeChart ────────────────────────────────────────────────────────────

function PedigreeChart({ animal, ancestros }) {
    const tienePadre = !!ancestros?.padre
    const tieneMadre = !!ancestros?.madre

    if (!tienePadre && !tieneMadre) {
        return (
            <div className="flex flex-col items-center justify-center py-16 text-gray-400 gap-2">
                <svg className="w-10 h-10 opacity-30" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.5}>
                    <path strokeLinecap="round" strokeLinejoin="round"
                        d="M12 9v3m0 3h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                </svg>
                <p className="text-sm italic">Sin ascendencia registrada</p>
            </div>
        )
    }

    return (
        <div className="overflow-x-auto pb-2">
            <div className="flex items-center min-w-max gap-0">
                {/* Root animal */}
                <AnimalCard animal={animal} root />

                {/* Horizontal connector to bracket */}
                <div className="w-5 h-px bg-gray-300 flex-shrink-0" />

                {/* Bracket: padre arriba, madre abajo */}
                <div className="relative flex flex-col">
                    {/* Vertical bracket line */}
                    <div
                        className="absolute left-0 bg-gray-300"
                        style={{ width: 1, top: '25%', bottom: '25%' }}
                    />

                    {/* Padre */}
                    <div className="flex items-center py-2">
                        <div className="w-4 h-px bg-gray-300 flex-shrink-0" />
                        <PedigreeNode
                            nodo={ancestros?.padre ?? null}
                            gen={1}
                            maxGen={4}
                        />
                    </div>

                    {/* Madre */}
                    <div className="flex items-center py-2">
                        <div className="w-4 h-px bg-gray-300 flex-shrink-0" />
                        <PedigreeNode
                            nodo={ancestros?.madre ?? null}
                            gen={1}
                            maxGen={4}
                        />
                    </div>
                </div>
            </div>
        </div>
    )
}

// ─── DescendantNode ───────────────────────────────────────────────────────────

function DescendantNode({ nodo, nivel = 0 }) {
    const [expanded, setExpanded] = useState(nivel === 0)
    const tieneHijos = Array.isArray(nodo.hijos) && nodo.hijos.length > 0
    const c = sexClasses(nodo.sexo)

    return (
        <div>
            {/* Row */}
            <div
                className="flex items-center gap-2 py-1 rounded-lg hover:bg-gray-50 transition-colors"
                style={{ paddingLeft: `${nivel * 28 + 4}px` }}
            >
                {/* Toggle */}
                <button
                    onClick={() => tieneHijos && setExpanded(v => !v)}
                    className={`w-5 h-5 flex items-center justify-center rounded transition-colors flex-shrink-0
                        ${tieneHijos ? 'text-gray-400 hover:text-gray-700 hover:bg-gray-200' : 'cursor-default'}`}
                >
                    {tieneHijos ? (
                        <svg
                            viewBox="0 0 16 16"
                            className={`w-3 h-3 transition-transform duration-150 ${expanded ? 'rotate-90' : ''}`}
                            fill="currentColor"
                        >
                            <path d="M6 4l4 4-4 4V4z" />
                        </svg>
                    ) : (
                        <div className={`w-1.5 h-1.5 rounded-full ${c.dot}`} />
                    )}
                </button>

                {/* Inline card */}
                <Link
                    href={route('animales.show', nodo.id)}
                    className={`flex items-center gap-3 border ${c.border} ${c.bg}
                                rounded-lg px-3 py-1.5 hover:opacity-80 transition-opacity`}
                >
                    <div className={`w-1.5 h-1.5 rounded-full ${c.dot} flex-shrink-0`} />
                    <span className={`text-xs font-black font-mono ${c.text}`}>
                        {nodo.arete ?? '—'}
                    </span>
                    {nodo.alias && (
                        <span className="text-xs text-gray-600">{nodo.alias}</span>
                    )}
                    <span className="text-xs text-gray-400">{nodo.raza}</span>
                    {nodo.fecha_nac && (
                        <span className="text-xs text-gray-300">{nodo.fecha_nac}</span>
                    )}
                </Link>

                {/* Hijo count badge */}
                {tieneHijos && (
                    <span className="text-[10px] text-gray-400 font-medium">
                        {nodo.hijos.length} {nodo.hijos.length === 1 ? 'hijo' : 'hijos'}
                    </span>
                )}
            </div>

            {/* Children */}
            {expanded && tieneHijos && (
                <div>
                    {nodo.hijos.map(hijo => (
                        <DescendantNode key={hijo.id} nodo={hijo} nivel={nivel + 1} />
                    ))}
                </div>
            )}
        </div>
    )
}

function DescendantTree({ descendientes }) {
    if (!descendientes?.length) {
        return (
            <div className="flex flex-col items-center justify-center py-16 text-gray-400 gap-2">
                <svg className="w-10 h-10 opacity-30" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.5}>
                    <path strokeLinecap="round" strokeLinejoin="round"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p className="text-sm italic">Sin descendientes registrados</p>
            </div>
        )
    }

    return (
        <div className="space-y-0.5">
            {descendientes.map(nodo => (
                <DescendantNode key={nodo.id} nodo={nodo} nivel={0} />
            ))}
        </div>
    )
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function Show({ animal, arbol_ancestros, descendientes }) {
    const [tab, setTab] = useState('ancestros')
    const c = sexClasses(animal.sexo)

    const totalDescendientes = countDescendientes(descendientes)

    return (
        <>
            <Head title={`Genealogía — ${animal.arete ?? animal.alias ?? `#${animal.id}`}`} />

            <div className="min-h-screen bg-stone-50">
                <div className="max-w-screen-2xl mx-auto px-6 py-8 space-y-6">

                    {/* ── Breadcrumb ── */}
                    <div className="flex items-center gap-1.5 text-xs text-gray-400">
                        <Link href={route('animales.index')} className="hover:text-gray-600 transition-colors">
                            Animales
                        </Link>
                        <span>/</span>
                        <Link href={route('animales.show', animal.id)} className="hover:text-gray-600 transition-colors">
                            {animal.arete ?? animal.alias ?? `#${animal.id}`}
                        </Link>
                        <span>/</span>
                        <span className="text-gray-600 font-medium">Genealogía</span>
                    </div>

                    {/* ── Header ── */}
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-black text-gray-800 tracking-tight">
                                Genealogía
                            </h1>
                            <p className="text-sm text-gray-400 mt-0.5">
                                {animal.especie} · {animal.raza}
                            </p>
                        </div>

                        {/* Animal badge */}
                        <div className={`border-2 ${c.border} ${c.bg} rounded-2xl px-5 py-3 text-right shadow-sm`}>
                            <div className={`text-xl font-black font-mono tracking-tight ${c.text}`}>
                                {animal.arete ?? '—'}
                            </div>
                            {animal.alias && (
                                <div className="text-sm text-gray-600 font-medium">{animal.alias}</div>
                            )}
                            <div className="text-xs text-gray-400 mt-1 space-x-2">
                                <span>{animal.sexo === 'M' ? '♂ Macho' : '♀ Hembra'}</span>
                                {animal.fecha_nac && <span>· {animal.fecha_nac}</span>}
                            </div>
                        </div>
                    </div>

                    {/* ── Stats ── */}
                    <div className="grid grid-cols-3 gap-4">
                        {[
                            {
                                label: 'Generaciones ascendencia',
                                value: 4,
                                sub: arbol_ancestros?.padre || arbol_ancestros?.madre ? 'registradas' : 'sin datos',
                            },
                            {
                                label: 'Descendientes directos',
                                value: descendientes?.length ?? 0,
                                sub: 'hijos registrados',
                            },
                            {
                                label: 'Total descendencia',
                                value: totalDescendientes,
                                sub: 'en 3 generaciones',
                            },
                        ].map(stat => (
                            <div key={stat.label}
                                className="bg-white border border-gray-100 rounded-xl px-5 py-4 shadow-sm">
                                <div className="text-2xl font-black text-gray-800">{stat.value}</div>
                                <div className="text-xs font-medium text-gray-600 mt-0.5">{stat.label}</div>
                                <div className="text-[11px] text-gray-400 mt-0.5">{stat.sub}</div>
                            </div>
                        ))}
                    </div>

                    {/* ── Tabs ── */}
                    <div className="border-b border-gray-200">
                        <div className="flex gap-6">
                            {[
                                { key: 'ancestros', label: 'Árbol de ancestros' },
                                { key: 'descendientes', label: `Descendientes (${totalDescendientes})` },
                            ].map(t => (
                                <button
                                    key={t.key}
                                    onClick={() => setTab(t.key)}
                                    className={`pb-3 text-sm font-semibold border-b-2 transition-colors -mb-px ${
                                        tab === t.key
                                            ? 'border-amber-500 text-amber-700'
                                            : 'border-transparent text-gray-400 hover:text-gray-600'
                                    }`}
                                >
                                    {t.label}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* ── Main content ── */}
                    <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">

                        {tab === 'ancestros' && (
                            <div>
                                {/* Generation legend */}
                                <div className="flex items-center justify-between mb-6">
                                    <h2 className="text-sm font-semibold text-gray-500">
                                        Ascendencia — hasta 4 generaciones
                                    </h2>
                                    <div className="flex items-center gap-4">
                                        {GEN_LABELS.map((label, i) => (
                                            <span key={i} className="flex items-center gap-1 text-[11px] text-gray-400">
                                                <span className="font-bold text-gray-500">G{i}</span>
                                                <span>{label}</span>
                                            </span>
                                        ))}
                                        <div className="w-px h-4 bg-gray-200" />
                                        <div className="flex items-center gap-2 text-[11px] text-gray-400">
                                            <span className="inline-block w-3 h-3 rounded border-2 border-sky-300 bg-sky-50" />
                                            Macho
                                            <span className="inline-block w-3 h-3 rounded border-2 border-rose-300 bg-rose-50" />
                                            Hembra
                                        </div>
                                    </div>
                                </div>

                                <PedigreeChart animal={animal} ancestros={arbol_ancestros} />
                            </div>
                        )}

                        {tab === 'descendientes' && (
                            <div>
                                <h2 className="text-sm font-semibold text-gray-500 mb-6">
                                    Descendencia — hasta 3 generaciones
                                </h2>
                                <DescendantTree descendientes={descendientes} />
                            </div>
                        )}
                    </div>

                </div>
            </div>
        </>
    )
}

// ─── Utils ────────────────────────────────────────────────────────────────────

function countDescendientes(nodos) {
    if (!nodos?.length) return 0
    return nodos.reduce((acc, n) => acc + 1 + countDescendientes(n.hijos), 0)
}